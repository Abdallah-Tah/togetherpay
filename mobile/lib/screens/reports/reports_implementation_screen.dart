import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../main.dart';
import '../../env.dart';

/// Actual implementation of Reports & Analytics screen
class ReportsImplementationScreen extends StatefulWidget {
  const ReportsImplementationScreen({super.key});

  @override
  State<ReportsImplementationScreen> createState() =>
      _ReportsImplementationScreenState();
}

class _ReportsImplementationScreenState
    extends State<ReportsImplementationScreen> {
  bool _loading = true;
  String? _error;
  List<Map<String, dynamic>> _groups = [];
  int? _selectedGroupId;
  DateTime _startDate = DateTime.now().subtract(const Duration(days: 30));
  DateTime _endDate = DateTime.now();
  Map<String, dynamic>? _stats;

  @override
  void initState() {
    super.initState();
    _loadGroups();
  }

  Future<void> _loadGroups() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await dio.get('/groups');
      final data = response.data;

      if (!mounted) return;

      List<Map<String, dynamic>> groups = [];
      if (data is List) {
        groups = data.map((g) => g as Map<String, dynamic>).toList();
      } else if (data is Map && data.containsKey('data')) {
        final groupsData = data['data'] as List;
        groups = groupsData.map((g) => g as Map<String, dynamic>).toList();
      }

      setState(() {
        _groups = groups;
        _loading = false;
        if (groups.isNotEmpty) {
          _selectedGroupId = groups.first['id'] as int;
          _loadStats();
        }
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Error loading groups: $e');
      }

      if (!mounted) return;

      setState(() {
        _error = 'Failed to load groups';
        _loading = false;
      });
    }
  }

  Future<void> _loadStats() async {
    if (_selectedGroupId == null) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await dio.get('/reports/summary', queryParameters: {
        'group_id': _selectedGroupId,
        'start_date': DateFormat('yyyy-MM-dd').format(_startDate),
        'end_date': DateFormat('yyyy-MM-dd').format(_endDate),
      });

      if (!mounted) return;

      setState(() {
        _stats = response.data['stats'] as Map<String, dynamic>?;
        _loading = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Error loading stats: $e');
      }

      if (!mounted) return;

      setState(() {
        _error = 'Failed to load statistics';
        _loading = false;
      });
    }
  }

  Future<void> _selectDateRange() async {
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDateRange: DateTimeRange(start: _startDate, end: _endDate),
    );

    if (picked != null) {
      setState(() {
        _startDate = picked.start;
        _endDate = picked.end;
      });
      _loadStats();
    }
  }

  Future<void> _exportCsv() async {
    if (_selectedGroupId == null) return;

    try {
      final url =
          '${Env.apiBase}/reports/export/csv?group_id=$_selectedGroupId&start_date=${DateFormat('yyyy-MM-dd').format(_startDate)}&end_date=${DateFormat('yyyy-MM-dd').format(_endDate)}';

      final uri = Uri.parse(url);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('CSV export started'),
              backgroundColor: Colors.green,
            ),
          );
        }
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Error exporting CSV: $e');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Failed to export CSV'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _exportPdf() async {
    if (_selectedGroupId == null) return;

    try {
      final url =
          '${Env.apiBase}/reports/export/pdf?group_id=$_selectedGroupId&start_date=${DateFormat('yyyy-MM-dd').format(_startDate)}&end_date=${DateFormat('yyyy-MM-dd').format(_endDate)}';

      final uri = Uri.parse(url);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('PDF report started'),
              backgroundColor: Colors.green,
            ),
          );
        }
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Error exporting PDF: $e');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Failed to export PDF'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF1A1F2E),
      appBar: AppBar(
        title: const Text('Reports & Analytics'),
        backgroundColor: const Color(0xFF1A1F2E),
      ),
      body: _loading && _groups.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.error_outline,
                          size: 64, color: Colors.red.shade300),
                      const SizedBox(height: 16),
                      Text(_error!,
                          style: const TextStyle(color: Colors.white70)),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadGroups,
                        child: const Text('Retry'),
                      ),
                    ],
                  ),
                )
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Header
                      const Text(
                        'Payment Insights',
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Track your group payment performance and export detailed reports.',
                        style: TextStyle(
                          color: Colors.white60,
                          fontSize: 14,
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Group Selection
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: const Color(0xFF252B3B),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Select Group',
                              style: TextStyle(
                                color: Colors.white70,
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 16,
                                vertical: 4,
                              ),
                              decoration: BoxDecoration(
                                color: const Color(0xFF1A1F2E),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: DropdownButton<int>(
                                value: _selectedGroupId,
                                isExpanded: true,
                                dropdownColor: const Color(0xFF252B3B),
                                style: const TextStyle(color: Colors.white),
                                underline: const SizedBox(),
                                items: _groups.map((group) {
                                  return DropdownMenuItem<int>(
                                    value: group['id'] as int,
                                    child: Text(group['name'] as String),
                                  );
                                }).toList(),
                                onChanged: (value) {
                                  setState(() {
                                    _selectedGroupId = value;
                                  });
                                  _loadStats();
                                },
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Date Range
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: const Color(0xFF252B3B),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Date Range',
                                  style: TextStyle(
                                    color: Colors.white70,
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '${DateFormat('MMM d, yyyy').format(_startDate)} - ${DateFormat('MMM d, yyyy').format(_endDate)}',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 14,
                                  ),
                                ),
                              ],
                            ),
                            IconButton(
                              onPressed: _selectDateRange,
                              icon: const Icon(Icons.calendar_today,
                                  color: Colors.white70),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Stats Cards
                      if (_stats != null) ...[
                        Row(
                          children: [
                            Expanded(
                              child: _buildStatCard(
                                'Collected',
                                '\$${(_stats!['total_collected'] as num).toStringAsFixed(2)}',
                                Icons.check_circle,
                                Colors.green,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: _buildStatCard(
                                'Pending',
                                '\$${(_stats!['total_pending'] as num).toStringAsFixed(2)}',
                                Icons.pending,
                                Colors.orange,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: _buildStatCard(
                                'Paid',
                                '${_stats!['paid_requests']}/${_stats!['total_requests']}',
                                Icons.receipt,
                                Colors.blue,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: _buildStatCard(
                                'Success Rate',
                                '${(_stats!['success_rate'] as num).toStringAsFixed(1)}%',
                                Icons.trending_up,
                                Colors.purple,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 24),

                        // Export Buttons
                        const Text(
                          'Export Reports',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed: _exportCsv,
                                icon: const Icon(Icons.download,
                                    color: Colors.white),
                                label: const Text('CSV Export',
                                    style: TextStyle(color: Colors.white)),
                                style: OutlinedButton.styleFrom(
                                  padding:
                                      const EdgeInsets.symmetric(vertical: 16),
                                  side: const BorderSide(
                                      color: Colors.white24),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: ElevatedButton.icon(
                                onPressed: _exportPdf,
                                icon: const Icon(Icons.picture_as_pdf),
                                label: const Text('PDF Report'),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF4F46E5),
                                  padding:
                                      const EdgeInsets.symmetric(vertical: 16),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ] else if (_selectedGroupId != null && !_loading)
                        Center(
                          child: Padding(
                            padding: const EdgeInsets.all(32.0),
                            child: Column(
                              children: [
                                Icon(Icons.analytics_outlined,
                                    size: 64, color: Colors.white24),
                                const SizedBox(height: 16),
                                const Text(
                                  'No data available for this date range',
                                  style: TextStyle(color: Colors.white60),
                                  textAlign: TextAlign.center,
                                ),
                              ],
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildStatCard(
      String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            color.withOpacity(0.2),
            color.withOpacity(0.05),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 12),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              color: Colors.white.withOpacity(0.6),
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }
}
