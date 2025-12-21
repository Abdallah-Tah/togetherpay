import 'dart:ui' as ui;

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../env.dart';
import '../services/api_service.dart';
import '../utils/theme.dart';
import 'payments/payment_request_details_sheet.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({
    super.key,
    required this.onNavigateToTab,
  });

  /// Callback for switching tabs in the parent scaffold.
  /// Expected indices: 0=Home, 1=Groups, 2=Requests, 3=Profile.
  final ValueChanged<int> onNavigateToTab;

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final Dio _dio = ApiService().dio;
  final NumberFormat _currency = NumberFormat.simpleCurrency();

  bool _loading = true;
  String? _error;

  List<Map<String, dynamic>> _groups = [];
  List<Map<String, dynamic>> _paymentRequests = [];

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final responses = await Future.wait([
        _dio.get('/groups'),
        _dio.get('/payment-requests'),
      ]);

      final groups = _parseGroups(responses[0].data);
      final prs = _parsePaymentRequests(responses[1].data);

      if (!mounted) return;
      setState(() {
        _groups = groups;
        _paymentRequests = prs;
        _loading = false;
      });
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Dashboard load error: ${e.response?.data}');
      }
      if (!mounted) return;
      setState(() {
        _error =
            _extractErrorMessage(e.response?.data) ?? 'Failed to load home';
        _loading = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected dashboard error: $e');
      }
      if (!mounted) return;
      setState(() {
        _error = 'An unexpected error occurred';
        _loading = false;
      });
    }
  }

  String? _extractErrorMessage(dynamic payload) {
    if (payload == null) return null;
    if (payload is String && payload.isNotEmpty) return payload;
    if (payload is Map) {
      final message = payload['message'];
      if (message is String && message.isNotEmpty) return message;
      final error = payload['error'];
      if (error is String && error.isNotEmpty) return error;
    }
    return null;
  }

  List<Map<String, dynamic>> _parseGroups(dynamic payload) {
    final groups = <Map<String, dynamic>>[];

    if (payload is Map<String, dynamic>) {
      final owned = payload['owned_groups'];
      final member = payload['member_groups'];
      final data = payload['data'];

      if (owned is List) {
        for (final item in owned) {
          if (item is Map) groups.add(Map<String, dynamic>.from(item));
        }
      }
      if (member is List) {
        for (final item in member) {
          if (item is Map) groups.add(Map<String, dynamic>.from(item));
        }
      }
      if (groups.isEmpty && data is List) {
        for (final item in data) {
          if (item is Map) groups.add(Map<String, dynamic>.from(item));
        }
      }
      return groups;
    }

    if (payload is List) {
      for (final item in payload) {
        if (item is Map) groups.add(Map<String, dynamic>.from(item));
      }
    }

    return groups;
  }

  List<Map<String, dynamic>> _parsePaymentRequests(dynamic payload) {
    final list = _extractRequestList(payload);
    return list
        .map((item) => item is Map ? Map<String, dynamic>.from(item) : null)
        .whereType<Map<String, dynamic>>()
        .toList();
  }

  List<dynamic> _extractRequestList(dynamic payload) {
    if (payload is List) return payload;

    if (payload is Map<String, dynamic>) {
      final candidates = [
        payload['data'],
        payload['payment_requests'],
        payload['items'],
      ];
      for (final candidate in candidates) {
        if (candidate is List) return candidate;
      }
    }

    return const <dynamic>[];
  }

  bool _isPaidStatus(dynamic statusValue) {
    final status = (statusValue as String?)?.toLowerCase();
    return status == 'paid' ||
        status == 'owner_confirmed' ||
        status == 'confirmed' ||
        status == 'auto_verified';
  }

  bool _isAwaitingConfirmationStatus(dynamic statusValue) {
    final status = (statusValue as String?)?.toLowerCase();
    return status == 'member_marked';
  }

  bool _isPendingStatus(dynamic statusValue) {
    final status = (statusValue as String?)?.toLowerCase();
    return status == 'pending' || status == 'sent' || status == 'member_marked';
  }

  bool _isOverdueRequest(Map<String, dynamic> pr) {
    if (_isPaidStatus(pr['status'])) return false;
    if (_isAwaitingConfirmationStatus(pr['status'])) return false;
    final dueDateStr = pr['due_date'] as String?;
    if (dueDateStr == null || dueDateStr.isEmpty) return false;
    final due = _parseDate(dueDateStr);
    if (due == null) return false;
    return due.isBefore(DateTime.now());
  }

  DateTime? _parseDate(String raw) {
    try {
      return DateTime.parse(raw);
    } catch (_) {
      return null;
    }
  }

  DateTime? _activityDate(Map<String, dynamic> pr) {
    final candidates = [
      pr['paid_at'],
      pr['updated_at'],
      pr['sent_at'],
      pr['created_at'],
      pr['due_date'],
    ];

    for (final candidate in candidates) {
      if (candidate is String && candidate.isNotEmpty) {
        final parsed = _parseDate(candidate);
        if (parsed != null) return parsed;
      }
    }
    return null;
  }

  String _memberName(Map<String, dynamic> pr) {
    final member = pr['member'];
    if (member is Map) {
      final name = member['name'];
      if (name is String && name.trim().isNotEmpty) return name.trim();
      final user = member['user'];
      if (user is Map) {
        final userName = user['name'];
        if (userName is String && userName.trim().isNotEmpty) {
          return userName.trim();
        }
      }
    }
    return 'Member';
  }

  String _groupName(Map<String, dynamic> pr) {
    final cycle = pr['cycle'];
    if (cycle is Map) {
      final group = cycle['group'];
      if (group is Map) {
        final name = group['name'];
        if (name is String && name.trim().isNotEmpty) return name.trim();
      }
    }

    final group = pr['group'];
    if (group is Map) {
      final name = group['name'];
      if (name is String && name.trim().isNotEmpty) return name.trim();
    }

    return 'Group';
  }

  int _amountCents(Map<String, dynamic> pr) {
    final cents = pr['amount_cents'];
    if (cents is int) return cents;
    if (cents is num) return cents.toInt();
    return 0;
  }

  Future<void> _openRequestsTab() async {
    widget.onNavigateToTab(2);
  }

  Future<void> _openGroupsTab() async {
    widget.onNavigateToTab(1);
  }

  Future<void> _showPaymentRequestDetails(Map<String, dynamic> pr) async {
    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => PaymentRequestDetailsSheet(paymentRequest: pr),
    );
    await _loadDashboard();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    final openRequests =
        _paymentRequests.where((pr) => _isPendingStatus(pr['status'])).toList();

    openRequests.sort((a, b) {
      final aOverdue = _isOverdueRequest(a);
      final bOverdue = _isOverdueRequest(b);
      if (aOverdue != bOverdue) return aOverdue ? -1 : 1;
      final aDue = (a['due_date'] as String?) ?? '';
      final bDue = (b['due_date'] as String?) ?? '';
      return aDue.compareTo(bDue);
    });

    final overdueCount =
        _paymentRequests.where((pr) => _isOverdueRequest(pr)).length;
    final awaitingConfirmCount = _paymentRequests
        .where((pr) => _isAwaitingConfirmationStatus(pr['status']))
        .length;

    var openCents = 0;
    for (final pr in openRequests) {
      openCents += _amountCents(pr);
    }

    final now = DateTime.now();
    final monthStart = DateTime(now.year, now.month, 1);
    final nextMonthStart = DateTime(now.year, now.month + 1, 1);

    final monthRequests = _paymentRequests.where((pr) {
      final dt = _activityDate(pr);
      if (dt == null) return false;
      return !dt.isBefore(monthStart) && dt.isBefore(nextMonthStart);
    }).toList();

    var monthCents = 0;
    var monthPaidCount = 0;
    for (final pr in monthRequests) {
      monthCents += _amountCents(pr);
      if (_isPaidStatus(pr['status'])) monthPaidCount += 1;
    }

    final monthTotalCount = monthRequests.length;
    final paidRate =
        monthTotalCount == 0 ? 0.0 : monthPaidCount / monthTotalCount;

    final recent = [..._paymentRequests];
    recent.sort((a, b) {
      final aDt = _activityDate(a);
      final bDt = _activityDate(b);
      if (aDt == null && bDt == null) return 0;
      if (aDt == null) return 1;
      if (bDt == null) return -1;
      return bDt.compareTo(aDt);
    });

    final topInset = MediaQuery.of(context).padding.top;
    final listTopPadding =
        (isDark ? topInset + kToolbarHeight : AppTheme.spaceM);

    return Scaffold(
      extendBodyBehindAppBar: isDark,
      appBar: AppBar(
        title: const Text('Home'),
        backgroundColor:
            isDark ? Colors.transparent : Theme.of(context).colorScheme.surface,
        elevation: isDark ? 0 : null,
        flexibleSpace: isDark
            ? ClipRect(
                child: BackdropFilter(
                  filter: ui.ImageFilter.blur(sigmaX: 10, sigmaY: 10),
                  child: Container(
                    color: Colors.black.withValues(alpha: 0.25),
                  ),
                ),
              )
            : null,
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _loading ? null : _loadDashboard,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: Container(
        decoration: BoxDecoration(
          color: isDark ? null : Theme.of(context).scaffoldBackgroundColor,
          gradient: isDark
              ? const LinearGradient(
                  colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                )
              : null,
        ),
        child: RefreshIndicator(
          onRefresh: _loadDashboard,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: EdgeInsets.fromLTRB(
              AppTheme.spaceM,
              listTopPadding,
              AppTheme.spaceM,
              AppTheme.spaceM + MediaQuery.of(context).padding.bottom + 72,
            ),
            children: [
              if (_loading) const LinearProgressIndicator(minHeight: 2),
              if (_error != null) ...[
                const SizedBox(height: AppTheme.spaceM),
                _ErrorCard(
                  message: _error!,
                  onRetry: _loadDashboard,
                ),
              ],
              const SizedBox(height: AppTheme.spaceM),
              _buildOverviewCard(
                context,
                groupCount: _groups.length,
                openCount: openRequests.length,
                openCents: openCents,
                overdueCount: overdueCount,
                awaitingConfirmCount: awaitingConfirmCount,
              ),
              const SizedBox(height: AppTheme.spaceM),
              _buildThisMonthCard(
                context,
                totalCount: monthTotalCount,
                totalCents: monthCents,
                paidCount: monthPaidCount,
                paidRate: paidRate,
              ),
              const SizedBox(height: AppTheme.spaceM),
              _buildOpenRequestsCard(
                context,
                openRequests.take(4).toList(),
              ),
              const SizedBox(height: AppTheme.spaceM),
              _buildRecentActivityCard(
                context,
                recent.take(4).toList(),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildOverviewCard(
    BuildContext context, {
    required int groupCount,
    required int openCount,
    required int openCents,
    required int overdueCount,
    required int awaitingConfirmCount,
  }) {
    final onSurface = Theme.of(context).colorScheme.onSurface;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppTheme.spaceL),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Overview',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    color: onSurface.withValues(alpha: 0.7),
                    fontWeight: FontWeight.w600,
                  ),
            ),
            const SizedBox(height: 6),
            Text(
              openCount == 0
                  ? 'No open requests'
                  : '${openCount.toString()} open requests',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 8),
            Text(
              openCount == 0
                  ? 'Create a group to start splitting bills.'
                  : '${_currency.format(openCents / 100)} total pending',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: onSurface.withValues(alpha: 0.75),
                  ),
            ),
            const SizedBox(height: AppTheme.spaceM),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                _miniStatChip(
                  context,
                  label: 'Groups',
                  value: groupCount.toString(),
                  icon: Icons.groups_rounded,
                  accent: AppTheme.primary,
                ),
                _miniStatChip(
                  context,
                  label: 'Overdue',
                  value: overdueCount.toString(),
                  icon: Icons.warning_rounded,
                  accent: AppTheme.error,
                ),
                _miniStatChip(
                  context,
                  label: 'Awaiting',
                  value: awaitingConfirmCount.toString(),
                  icon: Icons.hourglass_bottom_rounded,
                  accent: AppTheme.warning,
                ),
              ],
            ),
            const SizedBox(height: AppTheme.spaceM),
            Row(
              children: [
                Expanded(
                  child: FilledButton.icon(
                    onPressed: _openRequestsTab,
                    icon: const Icon(Icons.payment_rounded),
                    label: const Text('Requests'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _openGroupsTab,
                    icon: const Icon(Icons.groups_rounded),
                    label: const Text('Groups'),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _miniStatChip(
    BuildContext context, {
    required String label,
    required String value,
    required IconData icon,
    required Color accent,
  }) {
    final onSurface = Theme.of(context).colorScheme.onSurface;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: accent.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(AppTheme.radiusM),
        border: Border.all(color: accent.withValues(alpha: 0.14)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: accent, size: 18),
          const SizedBox(width: 8),
          Text(
            '$label  ',
            style: Theme.of(context).textTheme.labelMedium?.copyWith(
                  color: onSurface.withValues(alpha: 0.75),
                ),
          ),
          Text(
            value,
            style: Theme.of(context).textTheme.labelMedium?.copyWith(
                  color: onSurface,
                  fontWeight: FontWeight.bold,
                ),
          ),
        ],
      ),
    );
  }

  Widget _buildThisMonthCard(
    BuildContext context, {
    required int totalCount,
    required int totalCents,
    required int paidCount,
    required double paidRate,
  }) {
    final onSurface = Theme.of(context).colorScheme.onSurface;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppTheme.spaceL),
        child: Row(
          children: [
            SizedBox(
              width: 92,
              height: 92,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  Positioned.fill(
                    child: CircularProgressIndicator(
                      value: paidRate.clamp(0.0, 1.0),
                      strokeWidth: 9,
                      backgroundColor: Theme.of(context)
                          .colorScheme
                          .outlineVariant
                          .withValues(alpha: 0.6),
                      valueColor:
                          const AlwaysStoppedAnimation<Color>(AppTheme.coral),
                    ),
                  ),
                  Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        totalCount.toString(),
                        textAlign: TextAlign.center,
                        textHeightBehavior: const TextHeightBehavior(
                          applyHeightToFirstAscent: false,
                          applyHeightToLastDescent: false,
                        ),
                        style:
                            Theme.of(context).textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.bold,
                                  height: 1.0,
                                ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Requests',
                        textAlign: TextAlign.center,
                        textHeightBehavior: const TextHeightBehavior(
                          applyHeightToFirstAscent: false,
                          applyHeightToLastDescent: false,
                        ),
                        style: Theme.of(context).textTheme.labelSmall?.copyWith(
                              color: onSurface.withValues(alpha: 0.7),
                              height: 1.0,
                            ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(width: AppTheme.spaceL),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'This Month',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    totalCount == 0
                        ? 'No activity yet'
                        : '${_currency.format(totalCents / 100)} total',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: onSurface.withValues(alpha: 0.75),
                        ),
                  ),
                  const SizedBox(height: AppTheme.spaceM),
                  Row(
                    children: [
                      _pill(
                        context,
                        label: 'Paid',
                        value: paidCount.toString(),
                        bg: AppTheme.success.withValues(alpha: 0.12),
                        fg: AppTheme.success,
                      ),
                      const SizedBox(width: 10),
                      _pill(
                        context,
                        label: 'Open',
                        value: (totalCount - paidCount)
                            .clamp(0, totalCount)
                            .toString(),
                        bg: AppTheme.primary.withValues(alpha: 0.10),
                        fg: AppTheme.primary,
                      ),
                    ],
                  ),
                  const SizedBox(height: AppTheme.spaceM),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: _openRequestsTab,
                      child: const Text('View requests'),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _pill(
    BuildContext context, {
    required String label,
    required String value,
    required Color bg,
    required Color fg,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            label,
            style: Theme.of(context).textTheme.labelSmall?.copyWith(
                  color: fg.withValues(alpha: 0.9),
                  fontWeight: FontWeight.w600,
                ),
          ),
          const SizedBox(width: 6),
          Text(
            value,
            style: Theme.of(context).textTheme.labelSmall?.copyWith(
                  color: fg,
                  fontWeight: FontWeight.bold,
                ),
          ),
        ],
      ),
    );
  }

  Widget _buildOpenRequestsCard(
    BuildContext context,
    List<Map<String, dynamic>> openRequests,
  ) {
    final onSurface = Theme.of(context).colorScheme.onSurface;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppTheme.spaceL),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Open Requests',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                TextButton(
                  onPressed: _openRequestsTab,
                  child: const Text('View all'),
                ),
              ],
            ),
            const SizedBox(height: AppTheme.spaceM),
            if (openRequests.isEmpty)
              Text(
                'No open payment requests right now.',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: onSurface.withValues(alpha: 0.75),
                    ),
              )
            else
              ...openRequests.map(
                (pr) => _OpenRequestRow(
                  memberName: _memberName(pr),
                  groupName: _groupName(pr),
                  amount: _currency.format(_amountCents(pr) / 100),
                  status: (pr['status'] as String?) ?? 'pending',
                  dueDate: pr['due_date'] as String?,
                  isOverdue: _isOverdueRequest(pr),
                  onTap: () => _showPaymentRequestDetails(pr),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildRecentActivityCard(
    BuildContext context,
    List<Map<String, dynamic>> recent,
  ) {
    final onSurface = Theme.of(context).colorScheme.onSurface;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppTheme.spaceL),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Recent Activity',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: AppTheme.spaceM),
            if (recent.isEmpty)
              Text(
                'Activity will appear here as your groups send requests.',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: onSurface.withValues(alpha: 0.75),
                    ),
              )
            else
              ...recent.map((pr) {
                final status = (pr['status'] as String?) ?? 'pending';
                final isPaid = _isPaidStatus(status);
                final isOverdue = _isOverdueRequest(pr);
                final isAwaiting = _isAwaitingConfirmationStatus(status);

                IconData icon;
                Color color;
                String label;

                if (isPaid) {
                  icon = Icons.check_circle_rounded;
                  color = AppTheme.success;
                  label = 'Paid';
                } else if (isAwaiting) {
                  icon = Icons.hourglass_bottom_rounded;
                  color = AppTheme.warning;
                  label = 'Awaiting confirm';
                } else if (isOverdue) {
                  icon = Icons.warning_rounded;
                  color = AppTheme.error;
                  label = 'Overdue';
                } else {
                  icon = Icons.pending_rounded;
                  color = AppTheme.primary;
                  label = 'Pending';
                }

                final when = _activityDate(pr);
                final whenLabel =
                    when == null ? '' : DateFormat('MMM d').format(when);

                return _ActivityRow(
                  icon: icon,
                  color: color,
                  title: '$label • ${_memberName(pr)} • ${_groupName(pr)}',
                  subtitle:
                      '${_currency.format(_amountCents(pr) / 100)}${whenLabel.isEmpty ? '' : ' • $whenLabel'}',
                  onTap: () => _showPaymentRequestDetails(pr),
                );
              }),
          ],
        ),
      ),
    );
  }
}

class _ErrorCard extends StatelessWidget {
  const _ErrorCard({
    required this.message,
    required this.onRetry,
  });

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bg = isDark
        ? AppTheme.error.withValues(alpha: 0.18)
        : AppTheme.error.withValues(alpha: 0.08);

    return Container(
      padding: const EdgeInsets.all(AppTheme.spaceM),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppTheme.radiusM),
        border: Border.all(
          color: AppTheme.error.withValues(alpha: isDark ? 0.35 : 0.2),
        ),
      ),
      child: Row(
        children: [
          Icon(Icons.error_outline, color: AppTheme.error),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
            ),
          ),
          TextButton(onPressed: onRetry, child: const Text('Retry')),
        ],
      ),
    );
  }
}

class _OpenRequestRow extends StatelessWidget {
  const _OpenRequestRow({
    required this.memberName,
    required this.groupName,
    required this.amount,
    required this.status,
    required this.dueDate,
    required this.isOverdue,
    required this.onTap,
  });

  final String memberName;
  final String groupName;
  final String amount;
  final String status;
  final String? dueDate;
  final bool isOverdue;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final normalized = status.toLowerCase();
    final statusLabel = isOverdue
        ? 'OVERDUE'
        : (normalized == 'member_marked'
            ? 'AWAITING'
            : normalized.toUpperCase());

    final statusColor = isOverdue
        ? AppTheme.error
        : (normalized == 'member_marked' ? AppTheme.warning : AppTheme.primary);

    var subtitle = groupName;
    if (dueDate != null && dueDate!.isNotEmpty) {
      subtitle = '$subtitle • Due ${_formatDue(dueDate!)}';
    }

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(AppTheme.radiusM),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: Row(
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: statusColor.withValues(alpha: 0.14),
                  ),
                  alignment: Alignment.center,
                  child: Text(
                    memberName.isNotEmpty
                        ? memberName.trim()[0].toUpperCase()
                        : '?',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: statusColor,
                        ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        memberName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style:
                            Theme.of(context).textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.w700,
                                ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        subtitle,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurface
                                  .withValues(alpha: 0.7),
                            ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      amount,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      statusLabel,
                      style: Theme.of(context).textTheme.labelSmall?.copyWith(
                            color: statusColor,
                            fontWeight: FontWeight.bold,
                            letterSpacing: 0.5,
                          ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String _formatDue(String raw) {
    final dt = DateTime.tryParse(raw);
    if (dt == null) return raw;
    final now = DateTime.now();
    final days = DateTime(dt.year, dt.month, dt.day)
        .difference(DateTime(now.year, now.month, now.day))
        .inDays;

    if (days == 0) return 'today';
    if (days == 1) return 'tomorrow';
    if (days == -1) return 'yesterday';
    if (days > 1 && days < 7) return 'in $days days';
    if (days < -1 && days > -7) return '${days.abs()} days ago';
    return DateFormat('MMM d').format(dt);
  }
}

class _ActivityRow extends StatelessWidget {
  const _ActivityRow({
    required this.icon,
    required this.color,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final Color color;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(AppTheme.radiusM),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: color.withValues(alpha: 0.14),
                  ),
                  child: Icon(icon, color: color, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        subtitle,
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurface
                                  .withValues(alpha: 0.7),
                            ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
