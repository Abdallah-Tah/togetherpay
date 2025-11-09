import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';

import '../env.dart';
import 'api_service.dart';

class ReportingService {
  ReportingService() : _dio = ApiService().dio;

  final Dio _dio;

  Future<List<Map<String, dynamic>>> fetchOwnedGroups() async {
    final response = await _dio.get('/groups');
    final data = response.data;

    if (data is! Map<String, dynamic>) {
      throw Exception('Unexpected groups response');
    }

    final ownedGroups = data['owned_groups'];

    if (ownedGroups is! List) {
      return [];
    }

    return ownedGroups
        .map((group) => Map<String, dynamic>.from(group as Map))
        .toList();
  }

  Future<Map<String, dynamic>> fetchSummary({
    required int groupId,
    required String startDate,
    required String endDate,
  }) async {
    final response = await _dio.get(
      '/reports/summary',
      queryParameters: {
        'group_id': groupId,
        'start_date': startDate,
        'end_date': endDate,
      },
    );

    if (response.data is! Map<String, dynamic>) {
      throw Exception('Unexpected summary response');
    }

    return Map<String, dynamic>.from(response.data as Map);
  }

  Future<File> downloadReport({
    required int groupId,
    required String startDate,
    required String endDate,
    required String format,
  }) async {
    final dir = await getApplicationDocumentsDirectory();
    final timestamp = DateTime.now().millisecondsSinceEpoch;
    final fileName = 'togetherpay-report-$timestamp.$format';
    final filePath = p.join(dir.path, fileName);

    if (Env.debug) {
      debugPrint('Downloading report ($format) to $filePath');
    }

    await _dio.download(
      '/reports/export/$format',
      filePath,
      queryParameters: {
        'group_id': groupId,
        'start_date': startDate,
        'end_date': endDate,
      },
      options: Options(responseType: ResponseType.bytes),
    );

    return File(filePath);
  }
}
