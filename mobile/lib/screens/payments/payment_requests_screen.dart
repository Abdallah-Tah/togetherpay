import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../main.dart';
import '../../env.dart';
import '../../utils/theme.dart';
import 'payment_request_details_sheet.dart';

/// Payment Requests Screen - Shows all payment requests for the authenticated user
class PaymentRequestsScreen extends StatefulWidget {
  const PaymentRequestsScreen({super.key});

  @override
  State<PaymentRequestsScreen> createState() => _PaymentRequestsScreenState();
}

class _PaymentRequestsScreenState extends State<PaymentRequestsScreen>
    with SingleTickerProviderStateMixin {
  bool _loading = true;
  String? _error;
  List<Map<String, dynamic>> _paymentRequests = [];
  Map<String, dynamic>? _stats;
  String _filterStatus = 'all'; // all, pending, paid, overdue
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        setState(() {
          switch (_tabController.index) {
            case 0:
              _filterStatus = 'all';
              break;
            case 1:
              _filterStatus = 'pending';
              break;
            case 2:
              _filterStatus = 'paid';
              break;
            case 3:
              _filterStatus = 'overdue';
              break;
          }
        });
      }
    });
    _loadPaymentRequests();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadPaymentRequests() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      // Load payment requests and stats in parallel
      final results = await Future.wait([
        dio.get('/payment-requests'),
        dio.get('/payment-requests/stats'),
      ]);

      final requestsData = results[0].data as Map<String, dynamic>;
      final statsData = results[1].data as Map<String, dynamic>;

      setState(() {
        _paymentRequests = List<Map<String, dynamic>>.from(
          (requestsData['data'] ?? requestsData['payment_requests'] ?? [])
              as List,
        );
        _stats = statsData['data'] ?? statsData;
        _loading = false;
      });
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Payment requests load error: ${e.response?.data}');
      }

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Failed to load payment requests';
        _loading = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected payment requests error: $e');
      }

      setState(() {
        _error = 'An unexpected error occurred';
        _loading = false;
      });
    }
  }

  List<Map<String, dynamic>> get _filteredPaymentRequests {
    if (_filterStatus == 'all') {
      return _paymentRequests;
    }

    return _paymentRequests.where((pr) {
      final status = pr['status'] as String?;
      final dueDate = pr['due_date'] as String?;

      if (_filterStatus == 'overdue') {
        if (status == 'paid') return false;
        if (dueDate == null) return false;
        try {
          final due = DateTime.parse(dueDate);
          return due.isBefore(DateTime.now());
        } catch (e) {
          return false;
        }
      }

      return status == _filterStatus;
    }).toList();
  }

  void _showPaymentRequestDetails(Map<String, dynamic> pr) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => PaymentRequestDetailsSheet(paymentRequest: pr),
    ).then((_) => _loadPaymentRequests());
  }

  @override
  Widget build(BuildContext context) {
    final filteredRequests = _filteredPaymentRequests;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Payment Requests'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _loading ? null : _loadPaymentRequests,
            icon: const Icon(Icons.refresh),
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          isScrollable: false,
          tabs: [
            Tab(
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text('All'),
                  if (_stats != null) ...[
                    const SizedBox(width: 4),
                    _buildBadge(_stats!['total'] as int? ?? 0, Colors.grey),
                  ],
                ],
              ),
            ),
            Tab(
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text('Pending'),
                  if (_stats != null) ...[
                    const SizedBox(width: 4),
                    _buildBadge(
                        _stats!['pending'] as int? ?? 0, Colors.orange),
                  ],
                ],
              ),
            ),
            Tab(
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text('Paid'),
                  if (_stats != null) ...[
                    const SizedBox(width: 4),
                    _buildBadge(_stats!['paid'] as int? ?? 0, Colors.green),
                  ],
                ],
              ),
            ),
            Tab(
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text('Overdue'),
                  if (_stats != null) ...[
                    const SizedBox(width: 4),
                    _buildBadge(_stats!['overdue'] as int? ?? 0, Colors.red),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _loadPaymentRequests,
        child: _buildBody(filteredRequests),
      ),
    );
  }

  Widget _buildBadge(int count, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.2),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(
        count.toString(),
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }

  Widget _buildBody(List<Map<String, dynamic>> requests) {
    if (_loading) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircularProgressIndicator(),
            SizedBox(height: 16),
            Text('Loading payment requests...'),
          ],
        ),
      );
    }

    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.error_outline,
                size: 64,
                color: Colors.red.shade300,
              ),
              const SizedBox(height: 16),
              Text(
                'Error',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 8),
              Text(
                _error!,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 24),
              FilledButton.icon(
                onPressed: _loadPaymentRequests,
                icon: const Icon(Icons.refresh),
                label: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    if (requests.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.receipt_long_outlined,
                size: 80,
                color: Colors.grey.shade300,
              ),
              const SizedBox(height: 24),
              Text(
                _filterStatus == 'all'
                    ? 'No Payment Requests'
                    : 'No ${_filterStatus.capitalize()} Requests',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 8),
              Text(
                _filterStatus == 'all'
                    ? 'Payment requests will appear here when you create groups and send payment requests to members.'
                    : 'You have no ${_filterStatus} payment requests at the moment.',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: Colors.grey.shade600,
                    ),
              ),
            ],
          ),
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(AppTheme.spaceMedium),
      itemCount: requests.length,
      itemBuilder: (context, index) {
        final pr = requests[index];
        return _buildPaymentRequestCard(pr);
      },
    );
  }

  Widget _buildPaymentRequestCard(Map<String, dynamic> pr) {
    final prCode = pr['pr_code'] as String?;
    final memberName = pr['member']?['name'] as String? ?? 'Unknown Member';
    final groupName = pr['cycle']?['group']?['name'] as String? ??
        pr['group']?['name'] as String? ??
        'Unknown Group';
    final amountCents = pr['amount_cents'] as int? ?? 0;
    final amount = amountCents / 100.0;
    final status = pr['status'] as String? ?? 'pending';
    final dueDateStr = pr['due_date'] as String?;
    final paidAtStr = pr['paid_at'] as String?;

    DateTime? dueDate;
    DateTime? paidAt;
    bool isOverdue = false;

    if (dueDateStr != null) {
      try {
        dueDate = DateTime.parse(dueDateStr);
        if (status != 'paid' && dueDate.isBefore(DateTime.now())) {
          isOverdue = true;
        }
      } catch (e) {
        if (Env.debug) {
          debugPrint('Error parsing due date: $e');
        }
      }
    }

    if (paidAtStr != null) {
      try {
        paidAt = DateTime.parse(paidAtStr);
      } catch (e) {
        if (Env.debug) {
          debugPrint('Error parsing paid at: $e');
        }
      }
    }

    Color statusColor;
    IconData statusIcon;
    String statusText;

    if (status == 'paid') {
      statusColor = Colors.green;
      statusIcon = Icons.check_circle;
      statusText = 'Paid';
    } else if (isOverdue) {
      statusColor = Colors.red;
      statusIcon = Icons.warning;
      statusText = 'Overdue';
    } else {
      statusColor = Colors.orange;
      statusIcon = Icons.schedule;
      statusText = 'Pending';
    }

    return Card(
      margin: const EdgeInsets.only(bottom: AppTheme.spaceMedium),
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
      ),
      child: InkWell(
        onTap: () => _showPaymentRequestDetails(pr),
        borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
        child: Padding(
          padding: const EdgeInsets.all(AppTheme.spaceMedium),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header row
              Row(
                children: [
                  // Member avatar
                  CircleAvatar(
                    backgroundColor:
                        Theme.of(context).colorScheme.primaryContainer,
                    child: Text(
                      memberName.isNotEmpty
                          ? memberName[0].toUpperCase()
                          : '?',
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onPrimaryContainer,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  const SizedBox(width: AppTheme.spaceSmall),
                  // Member and group info
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          memberName,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                        Text(
                          groupName,
                          style: TextStyle(
                            color: Colors.grey.shade600,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ),
                  ),
                  // Amount
                  Text(
                    '\$${amount.toStringAsFixed(2)}',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 20,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppTheme.spaceSmall),
              const Divider(),
              const SizedBox(height: AppTheme.spaceSmall),
              // Status and date row
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  // Status badge
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: statusColor.withValues(alpha: 0.3),
                      ),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          statusIcon,
                          size: 16,
                          color: statusColor,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          statusText,
                          style: TextStyle(
                            color: statusColor,
                            fontWeight: FontWeight.bold,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ),
                  // Date info
                  Text(
                    status == 'paid' && paidAt != null
                        ? 'Paid ${_formatDate(paidAt)}'
                        : dueDate != null
                            ? 'Due ${_formatDate(dueDate)}'
                            : 'No due date',
                    style: TextStyle(
                      color: Colors.grey.shade600,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
              // PR Code (for debugging in dev mode)
              if (Env.debug && prCode != null) ...[
                const SizedBox(height: AppTheme.spaceSmall),
                Text(
                  'Code: $prCode',
                  style: TextStyle(
                    color: Colors.grey.shade400,
                    fontSize: 10,
                    fontFamily: 'monospace',
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  String _formatDate(DateTime date) {
    final now = DateTime.now();
    final difference = date.difference(now);

    if (difference.inDays == 0) {
      return 'today';
    } else if (difference.inDays == 1) {
      return 'tomorrow';
    } else if (difference.inDays == -1) {
      return 'yesterday';
    } else if (difference.inDays > 0 && difference.inDays < 7) {
      return 'in ${difference.inDays} days';
    } else if (difference.inDays < 0 && difference.inDays > -7) {
      return '${difference.inDays.abs()} days ago';
    } else {
      return DateFormat('MMM d, yyyy').format(date);
    }
  }
}

extension StringExtension on String {
  String capitalize() {
    if (isEmpty) return this;
    return '${this[0].toUpperCase()}${substring(1)}';
  }
}
