import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_app_badger/flutter_app_badger.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Lightweight, on-device notification inbox with badge syncing.
class NotificationCenterService {
  NotificationCenterService._();

  static final NotificationCenterService instance =
      NotificationCenterService._();

  static const _storageKey = 'tp_notification_center_v1';
  static const _maxItems = 80;

  final _unreadController = StreamController<int>.broadcast();
  List<NotificationItem> _items = [];
  bool _loaded = false;
  bool _loading = false;

  Stream<int> get unreadCountStream => _unreadController.stream;
  int get unreadCount => _items.where((n) => !n.read).length;
  List<NotificationItem> get items => List.unmodifiable(_items);

  Future<void> load() async {
    if (_loaded || _loading) return;
    _loading = true;
    try {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString(_storageKey);
      if (raw != null && raw.isNotEmpty) {
        final decoded = json.decode(raw);
        if (decoded is List) {
          _items = decoded
              .whereType<Map>()
              .map((m) => NotificationItem.fromJson(Map<String, dynamic>.from(m)))
              .whereType<NotificationItem>()
              .toList();
        }
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('Notification load failed: $e');
      }
    } finally {
      _loaded = true;
      _loading = false;
      _emitUnread();
      await _syncBadge();
    }
  }

  Future<void> add(NotificationItem item) async {
    await load();
    _items.insert(0, item);
    if (_items.length > _maxItems) {
      _items = _items.take(_maxItems).toList();
    }
    await _persistAndSync();
  }

  Future<void> addFromPush({
    required String title,
    required String body,
    String? category,
  }) async {
    final item = NotificationItem(
      id: DateTime.now().microsecondsSinceEpoch.toString(),
      title: title,
      body: body,
      category: category ?? 'general',
      timestamp: DateTime.now(),
      read: false,
    );
    await add(item);
  }

  Future<void> markAllRead() async {
    await load();
    var changed = false;
    for (final n in _items) {
      if (!n.read) {
        n.read = true;
        changed = true;
      }
    }
    if (changed) {
      await _persistAndSync();
    }
  }

  Future<void> markRead(String id) async {
    await load();
    final item = _items.firstWhere(
      (n) => n.id == id,
      orElse: () => NotificationItem.empty,
    );
    if (item.id == NotificationItem.empty.id) return;
    if (item.read) return;
    item.read = true;
    await _persistAndSync();
  }

  Future<void> delete(String id) async {
    await load();
    _items.removeWhere((n) => n.id == id);
    await _persistAndSync();
  }

  Future<void> clearAll() async {
    await load();
    _items.clear();
    await _persistAndSync();
  }

  Future<void> refreshBadgeFromStore() async {
    await load();
    await _syncBadge();
  }

  Future<void> _persistAndSync() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final payload = json.encode(_items.map((n) => n.toJson()).toList());
      await prefs.setString(_storageKey, payload);
    } catch (e) {
      if (kDebugMode) {
        debugPrint('Notification persist failed: $e');
      }
    }
    _emitUnread();
    await _syncBadge();
  }

  void _emitUnread() {
    if (!_unreadController.isClosed) {
      _unreadController.add(unreadCount);
    }
  }

  Future<void> _syncBadge() async {
    if (kIsWeb) return;
    try {
      final count = unreadCount;
      final supported = await FlutterAppBadger.isAppBadgeSupported();
      if (!supported) return;
      if (count <= 0) {
        await FlutterAppBadger.removeBadge();
      } else {
        // Cap at 99 for elegance.
        final displayCount = count > 99 ? 99 : count;
        await FlutterAppBadger.updateBadgeCount(displayCount);
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('Badge sync failed: $e');
      }
    }
  }
}

class NotificationItem {
  NotificationItem({
    required this.id,
    required this.title,
    required this.body,
    required this.category,
    required this.timestamp,
    required this.read,
  });

  static NotificationItem get empty => NotificationItem(
        id: '_empty',
        title: '',
        body: '',
        category: 'general',
        timestamp: DateTime.fromMillisecondsSinceEpoch(0),
        read: true,
      );

  final String id;
  final String title;
  final String body;
  final String category;
  final DateTime timestamp;
  bool read;

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'body': body,
        'category': category,
        'timestamp': timestamp.toIso8601String(),
        'read': read,
      };

  static NotificationItem? fromJson(Map<String, dynamic> json) {
    try {
      final ts = DateTime.tryParse(json['timestamp'] as String? ?? '');
      if (ts == null) return null;
      return NotificationItem(
        id: json['id']?.toString() ?? DateTime.now().microsecondsSinceEpoch.toString(),
        title: json['title']?.toString() ?? 'Notification',
        body: json['body']?.toString() ?? '',
        category: json['category']?.toString() ?? 'general',
        timestamp: ts,
        read: json['read'] as bool? ?? false,
      );
    } catch (_) {
      return null;
    }
  }
}
