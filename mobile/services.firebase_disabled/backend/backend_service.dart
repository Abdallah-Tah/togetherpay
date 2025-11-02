import 'dart:async';

import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:cloud_functions/cloud_functions.dart';
import 'package:dio/dio.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/foundation.dart';

import '../../env.dart';
import '../../main.dart';

/// Contract for backend operations the mobile app depends on.
/// Adds an indirection so we can swap the Laravel REST API for Firebase.
abstract class BackendService {
  Future<Map<String, dynamic>> fetchGroup(int groupId);

  Future<List<Map<String, dynamic>>> fetchGroupsOverview();

  Future<Map<String, dynamic>> addMember({
    required int groupId,
    required Map<String, dynamic> payload,
  });
}

class LaravelBackendService implements BackendService {
  const LaravelBackendService();

  @override
  Future<Map<String, dynamic>> fetchGroup(int groupId) async {
    final response = await dio.get('/groups/$groupId');
    final data = response.data;

    if (data is Map<String, dynamic>) {
      if (data.containsKey('group') && data['group'] is Map<String, dynamic>) {
        return Map<String, dynamic>.from(data['group'] as Map<String, dynamic>);
      }
      return Map<String, dynamic>.from(data);
    }

    throw StateError('Unexpected response when fetching group: $data');
  }

  @override
  Future<List<Map<String, dynamic>>> fetchGroupsOverview() async {
    final response = await dio.get('/groups');
    final data = response.data;

    if (data is List) {
      return data.whereType<Map>().map((e) {
        final map = Map<String, dynamic>.from(e as Map);
        map['is_owner'] = map['is_owner'] as bool? ?? false;
        return map;
      }).toList();
    }

    if (data is Map) {
      final owned = (data['owned_groups'] as List?) ?? [];
      final member = (data['member_groups'] as List?) ?? [];
      return [
        ...owned.whereType<Map>().map((e) {
          final map = Map<String, dynamic>.from(e as Map);
          map['is_owner'] = true;
          return map;
        }),
        ...member.whereType<Map>().map((e) {
          final map = Map<String, dynamic>.from(e as Map);
          map['is_owner'] = map['is_owner'] as bool? ?? false;
          return map;
        }),
      ];
    }

    throw StateError('Unexpected response when fetching groups: $data');
  }

  @override
  Future<Map<String, dynamic>> addMember({
    required int groupId,
    required Map<String, dynamic> payload,
  }) async {
    final response = await dio.post(
      '/groups/$groupId/members',
      data: payload,
    );

    final data = response.data;
    if (data is Map) {
      return Map<String, dynamic>.from(data as Map);
    }

    throw StateError('Unexpected response when adding member: $data');
  }
}

class FirebaseBackendService implements BackendService {
  FirebaseBackendService()
      : _firestore = FirebaseFirestore.instance,
        _functions = FirebaseFunctions.instanceFor(
          region: Env.firebaseFunctionsRegion,
        ),
        _auth = FirebaseAuth.instance;

  final FirebaseFirestore _firestore;
  final FirebaseFunctions _functions;
  final FirebaseAuth _auth;

  Future<void> ensureSignedIn() async {
    if (_auth.currentUser != null) return;

    try {
      final user = await _auth.signInAnonymously();
      if (Env.debug) {
        debugPrint('Signed in anonymously: ${user.user?.uid}');
      }
    } on FirebaseAuthException catch (e) {
      if (Env.debug) {
        debugPrint('Anonymous sign-in failed: $e');
      }
      rethrow;
    }
  }

  @override
  Future<Map<String, dynamic>> fetchGroup(int groupId) async {
    await ensureSignedIn();
    final doc = await _firestore.collection('groups').doc(groupId.toString()).get();

    if (!doc.exists) {
      throw StateError('Group $groupId not found in Firestore');
    }

    final data = doc.data();
    if (data == null) {
      throw StateError('Group $groupId has no data');
    }

    return {
      'id': groupId,
      ...data,
    };
  }

  @override
  Future<List<Map<String, dynamic>>> fetchGroupsOverview() async {
    await ensureSignedIn();

    final snapshot = await _firestore.collection('groups').get();

    return snapshot.docs
        .map((doc) => {
              'id': int.tryParse(doc.id) ?? doc.id,
              ...doc.data(),
            })
        .toList();
  }

  @override
  Future<Map<String, dynamic>> addMember({
    required int groupId,
    required Map<String, dynamic> payload,
  }) async {
    await ensureSignedIn();

    final callable = _functions.httpsCallable('addGroupMember');
    final result = await callable.call({
      'groupId': groupId,
      'payload': payload,
    });

    final data = result.data;
    if (data is Map) {
      return Map<String, dynamic>.from(data as Map);
    }

    throw StateError('Unexpected Firebase response when adding member: $data');
  }
}

final BackendService backendService = Env.useFirebaseBackend
    ? FirebaseBackendService()
    : const LaravelBackendService();
