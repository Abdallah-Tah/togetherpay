import 'dart:async';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:open_filex/open_filex.dart';

import '../../services/reporting_service.dart';
import '../../utils/theme.dart';

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key});

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  final ReportingService _service = ReportingService();
  final NumberFormat _currencyFormat = NumberFormat.simpleCurrency();

  List<Map<String, dynamic>> _groups = [];
  Map<String, dynamic>? _summary;
  int? _selectedGroupId;
  String? _error;

  bool _loadingGroups = true;
  bool _loadingStats = false;
  bool _downloadingCsv = false;
  bool _downloadingPdf = false;

  late DateTimeRange _range;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _range = DateTimeRange(
      start: DateTime(now.year, now.month, 1),
      end: DateTime(now.year, now.month + 1, 0),
    );
    unawaited(_loadGroups());
  }

  Future<void> _loadGroups() async {
    setState(() {
      _loadingGroups = true;
      _error = null;
    });

    try {
      final groups = await _service.fetchOwnedGroups();

      if (!mounted) return;

      setState(() {
        _groups = groups;
        _loadingGroups = false;
        _selectedGroupId ??= groups.isNotEmpty ? groups.first['id'] as int : null;
      });

      if (_selectedGroupId != null) {
        await _loadStats();
      }
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = 'Failed to load groups';
        _loadingGroups = false;
      });
    }
  }

  Future<void> _loadStats() async {
    final groupId = _selectedGroupId;
    if (groupId == null) return;

    setState(() {
      _loadingStats = true;
      _error = null;
    });

    try {
      final summary = await _service.fetchSummary(
        groupId: groupId,
        startDate: _formatDate(_range.start),
        endDate: _formatDate(_range.end),
      );

      if (!mounted) return;

      setState(() {
        _summary = summary;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = 'Failed to load stats';
      });
    } finally {
      if (mounted) {
        setState(() {
          _loadingStats = false;
        });
      }
    }
  }

  Future<void> _pickDateRange() async {
    final today = DateTime.now();
    final firstDate =
        DateTime(today.year - 1, today.month, today.day); // lookback 1 year
    final lastDate = DateTime(today.year + 1, today.month, today.day);

    DateTime adjustedStart = _range.start;
    DateTime adjustedEnd = _range.end;
    if (adjustedStart.isBefore(firstDate)) adjustedStart = firstDate;
    if (adjustedEnd.isAfter(lastDate)) adjustedEnd = lastDate;
    if (adjustedStart.isAfter(adjustedEnd)) {
      adjustedStart = adjustedEnd;
    }

    final picked = await showDateRangePicker(
      context: context,
      firstDate: firstDate,
      lastDate: lastDate,
      initialDateRange: DateTimeRange(
        start: adjustedStart,
        end: adjustedEnd,
      ),
    );

    if (picked != null) {
      setState(() {
        _range = picked;
      });
      await _loadStats();
    }
  }

  Future<void> _download(String format) async {
    final groupId = _selectedGroupId;
    if (groupId == null) return;

    setState(() {
      if (format == 'csv') {
        _downloadingCsv = true;
      } else {
        _downloadingPdf = true;
      }
    });

    try {
      final file = await _service.downloadReport(
        groupId: groupId,
        startDate: _formatDate(_range.start),
        endDate: _formatDate(_range.end),
        format: format,
      );

      if (!mounted) return;

      final fileName = file.path.split('/').last;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('${format.toUpperCase()} saved to $fileName'),
          action: SnackBarAction(
            label: 'Open',
            onPressed: () => unawaited(_openDownloadedFile(file.path)),
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to download $format report'),
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          if (format == 'csv') {
            _downloadingCsv = false;
          } else {
            _downloadingPdf = false;
          }
        });
      }
    }
  }

  String _formatDate(DateTime date) => DateFormat('yyyy-MM-dd').format(date);

  String _displayDateRange() {
    final start = DateFormat('MMM d, yyyy').format(_range.start);
    final end = DateFormat('MMM d, yyyy').format(_range.end);
    return '$start – $end';
  }

  String _formatCurrency(num value) {
    final currency = _summary?['group']?['currency'] as String?;
    if (currency != null && currency.isNotEmpty) {
      return NumberFormat.currency(name: currency).format(value);
    }
    return _currencyFormat.format(value);
  }

  double _successRate() {
    final stats = _summary?['stats'] as Map<String, dynamic>?;
    final value = stats?['success_rate'];
    if (value is num) {
      return value.toDouble();
    }
    return 0;
  }

  Map<String, dynamic>? get _stats =>
      _summary?['stats'] as Map<String, dynamic>?;

  Future<void> _openDownloadedFile(String path) async {
    try {
      await OpenFilex.open(path);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Unable to open file. It is saved at $path'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasGroups = _groups.isNotEmpty;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Reports & Analytics'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
            onPressed: _loadingGroups ? null : _loadGroups,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          await _loadGroups();
        },
        child: ListView(
          padding: const EdgeInsets.all(AppTheme.spaceMedium),
          children: [
            Text(
              'Payment Insights',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: AppTheme.spaceSmall),
            Text(
              'Track collection performance, monitor outstanding payments, and export details for your records.',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Colors.grey.shade600,
                  ),
            ),
            const SizedBox(height: AppTheme.spaceLarge),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(AppTheme.spaceMedium),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Filters',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: AppTheme.spaceMedium),
                    SizedBox(
                      width: double.infinity,
                      child: DropdownMenu<int>(
                        initialSelection: _selectedGroupId,
                        label: const Text('Group'),
                        dropdownMenuEntries: _groups
                            .map(
                              (group) => DropdownMenuEntry<int>(
                                value: group['id'] as int,
                                label: group['name'] as String? ?? 'Unnamed',
                              ),
                            )
                            .toList(),
                        onSelected: _loadingGroups
                            ? null
                            : (value) {
                                setState(() {
                                  _selectedGroupId = value;
                                });
                                _loadStats();
                              },
                      ),
                    ),
                    const SizedBox(height: AppTheme.spaceMedium),
                    OutlinedButton.icon(
                      onPressed: _pickDateRange,
                      icon: const Icon(Icons.date_range),
                      label: Text(_displayDateRange()),
                    ),
                  ],
                ),
              ),
            ),
            if (_error != null) ...[
              const SizedBox(height: AppTheme.spaceMedium),
              Container(
                padding: const EdgeInsets.all(AppTheme.spaceMedium),
                decoration: BoxDecoration(
                  color: Colors.orange.shade50,
                  borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
                  border: Border.all(color: Colors.orange.shade200),
                ),
                child: Row(
                  children: [
                    Icon(Icons.info_outline, color: Colors.orange.shade800),
                    const SizedBox(width: AppTheme.spaceSmall),
                    Expanded(
                      child: Text(
                        _error!,
                        style: TextStyle(color: Colors.orange.shade900),
                      ),
                    ),
                  ],
                ),
              ),
            ],
            const SizedBox(height: AppTheme.spaceLarge),
            if (_loadingGroups)
              const Center(child: CircularProgressIndicator())
            else if (!hasGroups)
              _buildEmptyState(context)
            else
              _buildStatsSection(context),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppTheme.spaceLarge),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
        color: Theme.of(context).colorScheme.surface,
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        children: [
          Icon(Icons.group_outlined, size: 48, color: Colors.grey.shade500),
          const SizedBox(height: AppTheme.spaceMedium),
          Text(
            'No groups yet',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: AppTheme.spaceSmall),
          Text(
            'Create a group on the dashboard to start tracking payments.',
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Colors.grey.shade600,
                ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsSection(BuildContext context) {
    if (_loadingStats) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_summary == null || _stats == null) {
      return Container(
        padding: const EdgeInsets.all(AppTheme.spaceLarge),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
          color: Theme.of(context).colorScheme.surface,
          border: Border.all(color: Colors.grey.shade200),
        ),
        child: const Text('Select a group to view analytics'),
      );
    }

    final stats = _stats!;
    final successRate = _successRate();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppTheme.spaceMedium),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Performance Overview',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: AppTheme.spaceMedium),
                Row(
                  children: [
                    Expanded(
                      child: _MetricTile(
                        label: 'Collected',
                        value: _formatCurrency(stats['total_collected'] as num? ?? 0),
                        icon: Icons.payments_outlined,
                        color: Colors.green,
                      ),
                    ),
                    const SizedBox(width: AppTheme.spaceMedium),
                    Expanded(
                      child: _MetricTile(
                        label: 'Pending',
                        value: _formatCurrency(stats['total_pending'] as num? ?? 0),
                        icon: Icons.pending_actions_outlined,
                        color: Colors.orange,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: AppTheme.spaceLarge),
                Text(
                  'Success Rate',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                const SizedBox(height: AppTheme.spaceSmall),
                LinearProgressIndicator(
                  value: successRate / 100,
                  minHeight: 10,
                  borderRadius: BorderRadius.circular(8),
                  backgroundColor: Colors.grey.shade200,
                  color: Colors.green,
                ),
                const SizedBox(height: AppTheme.spaceSmall),
                Text(
                  '$successRate%',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        color: Colors.green.shade700,
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: AppTheme.spaceLarge),
                Wrap(
                  spacing: AppTheme.spaceMedium,
                  runSpacing: AppTheme.spaceMedium,
                  children: [
                    _pill(
                      context,
                      label: 'Paid requests',
                      value: stats['paid_requests']?.toString() ?? '0',
                      icon: Icons.check_circle_outline,
                      color: Colors.green,
                    ),
                    _pill(
                      context,
                      label: 'Pending',
                      value: stats['pending_requests']?.toString() ?? '0',
                      icon: Icons.timelapse,
                      color: Colors.orange,
                    ),
                    _pill(
                      context,
                      label: 'Failed',
                      value: stats['failed_requests']?.toString() ?? '0',
                      icon: Icons.error_outline,
                      color: Colors.red,
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: AppTheme.spaceLarge),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppTheme.spaceMedium),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Exports',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: AppTheme.spaceSmall),
                Text(
                  'Download detailed payment activity for accounting or record keeping.',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Colors.grey.shade600,
                      ),
                ),
                const SizedBox(height: AppTheme.spaceLarge),
                FilledButton.icon(
                  onPressed: _downloadingPdf ? null : () => _download('pdf'),
                  icon: _downloadingPdf
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.picture_as_pdf_outlined),
                  label: const Text('Download PDF Summary'),
                ),
                const SizedBox(height: AppTheme.spaceSmall),
                OutlinedButton.icon(
                  onPressed: _downloadingCsv ? null : () => _download('csv'),
                  icon: _downloadingCsv
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.table_chart_outlined),
                  label: const Text('Download CSV Export'),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _pill(
    BuildContext context, {
    required String label,
    required String value,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppTheme.spaceMedium,
        vertical: AppTheme.spaceSmall,
      ),
      decoration: BoxDecoration(
        color: color.withValues(alpha:0.08),
        borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 18, color: color),
          const SizedBox(width: AppTheme.spaceSmall),
          Text(
            '$value $label',
            style: TextStyle(
              color: color.darken(),
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _MetricTile extends StatelessWidget {
  const _MetricTile({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppTheme.spaceMedium),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
        color: color.withValues(alpha:0.08),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color),
          const SizedBox(height: AppTheme.spaceSmall),
          Text(
            label,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Colors.grey.shade600,
                ),
          ),
          const SizedBox(height: AppTheme.spaceSmall),
          Text(
            value,
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.bold,
                  color: color.darken(),
                ),
          ),
        ],
      ),
    );
  }
}

extension on Color {
  Color darken([double amount = .2]) {
    final hsl = HSLColor.fromColor(this);
    final hslDark = hsl.withLightness((hsl.lightness - amount).clamp(0.0, 1.0));
    return hslDark.toColor();
  }
}
