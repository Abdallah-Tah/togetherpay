import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../main.dart';
import '../../env.dart';
import '../../utils/theme.dart';
import '../../widgets/skeleton_loader.dart';
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
  Map<String, int>? _stats;
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
      final response = await dio.get('/payment-requests');
      final requestsResponse = response.data;
      
      setState(() {
        _paymentRequests = _parsePaymentRequests(requestsResponse);
        _stats = _buildStats(_paymentRequests);
        _loading = false;
      });
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Payment requests load error: ${e.response?.data}');
      }

      final message = _extractErrorMessage(e.response?.data);

      setState(() {
        _error = message ?? 'Failed to load payment requests';
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

    return _paymentRequests
        .where((pr) => _matchesFilter(pr, _filterStatus))
        .toList();
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
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 0),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text('All', overflow: TextOverflow.ellipsis),
                    if (_stats != null) ...[
                      const SizedBox(width: 1),
                      _buildBadge(_stats!['total'] ?? 0, Colors.grey),
                    ],
                  ],
                ),
              ),
            ),
            Tab(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 0),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text('Pending', overflow: TextOverflow.ellipsis),
                    if (_stats != null) ...[
                      const SizedBox(width: 1),
                      _buildBadge(_stats!['pending'] ?? 0, Colors.orange),
                    ],
                  ],
                ),
              ),
            ),
            Tab(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 0),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text('Paid', overflow: TextOverflow.ellipsis),
                    if (_stats != null) ...[
                      const SizedBox(width: 1),
                      _buildBadge(_stats!['paid'] ?? 0, Colors.green),
                    ],
                  ],
                ),
              ),
            ),
            Tab(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 0),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text('Overdue', overflow: TextOverflow.ellipsis),
                    if (_stats != null) ...[
                      const SizedBox(width: 1),
                      _buildBadge(_stats!['overdue'] ?? 0, Colors.red),
                    ],
                  ],
                ),
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
      padding: const EdgeInsets.symmetric(horizontal: 3, vertical: 0),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.2),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        count.toString(),
        style: TextStyle(
          color: color,
          fontSize: 9,
          fontWeight: FontWeight.bold,
          height: 1.0,
        ),
      ),
    );
  }

  Widget _buildBody(List<Map<String, dynamic>> requests) {
    if (_loading) {
      return ListView.builder(
        padding: const EdgeInsets.all(AppTheme.spaceMedium),
        itemCount: 6,
        itemBuilder: (context, index) => const SkeletonPaymentCard(),
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
                    : 'You have no $_filterStatus payment requests at the moment.',
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
    final memberName = pr['member']?['name'] as String? ??
        pr['member']?['user']?['name'] as String? ??
        'Unknown Member';
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

    final isPaid = _isPaidStatus(status);
    final isAwaitingConfirm = _isAwaitingConfirmationStatus(status);

    if (dueDateStr != null) {
      try {
        dueDate = DateTime.parse(dueDateStr);
        if (!isPaid &&
            !isAwaitingConfirm &&
            dueDate.isBefore(DateTime.now())) {
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

    if (isPaid) {
      statusColor = Colors.green;
      statusIcon = Icons.check_circle;
      statusText = 'Paid';
    } else if (isAwaitingConfirm) {
      statusColor = Colors.blue;
      statusIcon = Icons.hourglass_top;
      statusText = 'Awaiting Confirm';
    } else if (isOverdue) {
      statusColor = Colors.red;
      statusIcon = Icons.warning;
      statusText = 'Overdue';
    } else {
      statusColor = Colors.orange;
      statusIcon = Icons.schedule;
      statusText = 'Pending';
    }

    return Container(
      margin: const EdgeInsets.only(bottom: AppTheme.spaceMedium),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
        boxShadow: AppTheme.cardShadow,
      ),
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
        clipBehavior: Clip.antiAlias,
        child: Container(
          decoration: BoxDecoration(
            border: Border.all(
              color: statusColor.withValues(alpha: 0.15),
              width: 1.5,
            ),
            borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                statusColor.withValues(alpha: 0.02),
                Colors.white,
              ],
            ),
          ),
          child: InkWell(
            onTap: () => _showPaymentRequestDetails(pr),
            child: Padding(
              padding: const EdgeInsets.all(AppTheme.spaceMedium),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Main content row
              Row(
                children: [
                  // Member avatar with accent
                  Container(
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: LinearGradient(
                        colors: [
                          statusColor.withValues(alpha: 0.3),
                          statusColor.withValues(alpha: 0.1),
                        ],
                      ),
                    ),
                    child: CircleAvatar(
                      backgroundColor: statusColor.withValues(alpha: 0.15),
                      radius: 24,
                      child: Text(
                        memberName.isNotEmpty
                            ? memberName[0].toUpperCase()
                            : '?',
                        style: TextStyle(
                          color: statusColor,
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: AppTheme.spaceMedium),
                  // Member and group info
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          memberName,
                          style: const TextStyle(
                            fontWeight: FontWeight.w600,
                            fontSize: 15,
                            height: 1.2,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 4),
                        Text(
                          groupName,
                          style: TextStyle(
                            color: Colors.grey.shade600,
                            fontSize: 12,
                            height: 1.2,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: AppTheme.spaceSmall),
                  // Amount with accent
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(AppTheme.radiusSmall),
                    ),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          '\$${amount.toStringAsFixed(2)}',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                            color: statusColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppTheme.spaceMedium),
              // Status and date row
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  // Status badge
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 5,
                    ),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          statusIcon,
                          size: 14,
                          color: statusColor,
                        ),
                        const SizedBox(width: 5),
                        Text(
                          statusText,
                          style: TextStyle(
                            color: statusColor,
                            fontWeight: FontWeight.w600,
                            fontSize: 11,
                          ),
                        ),
                      ],
                    ),
                  ),
                  // Date info
                  Text(
                    isPaid && paidAt != null
                        ? 'Paid ${_formatDate(paidAt)}'
                        : dueDate != null
                            ? 'Due ${_formatDate(dueDate)}'
                            : 'No due date',
                    style: TextStyle(
                      color: Colors.grey.shade500,
                      fontSize: 11,
                      fontWeight: FontWeight.w500,
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
    ),
    ),
    );
  }

  String? _extractErrorMessage(dynamic payload) {
    if (payload == null) {
      return null;
    }

    if (payload is String && payload.isNotEmpty) {
      return payload;
    }

    if (payload is Map) {
      final message = payload['message'];
      if (message is String && message.isNotEmpty) {
        return message;
      }
      final error = payload['error'];
      if (error is String && error.isNotEmpty) {
        return error;
      }
    }

    return null;
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
  
  List<Map<String, dynamic>> _parsePaymentRequests(dynamic payload) {
    final list = _extractRequestList(payload);

    return list
        .map((item) =>
            item is Map ? Map<String, dynamic>.from(item) : null)
        .whereType<Map<String, dynamic>>()
        .toList();
  }

  List<dynamic> _extractRequestList(dynamic payload) {
    if (payload is List) {
      return payload;
    }

    if (payload is Map<String, dynamic>) {
      final candidates = [
        payload['data'],
        payload['payment_requests'],
        payload['items'],
      ];

      for (final candidate in candidates) {
        if (candidate is List) {
          return candidate;
        }
      }
    }

    return const <dynamic>[];
  }

  Map<String, int> _buildStats(
    List<Map<String, dynamic>> requests,
  ) {
    return <String, int>{
      'total': requests.length,
      'pending': _countWhere(requests, (pr) => _matchesFilter(pr, 'pending')),
      'paid': _countWhere(requests, (pr) => _matchesFilter(pr, 'paid')),
      'overdue': _countWhere(requests, (pr) => _matchesFilter(pr, 'overdue')),
    };
  }

  int _countWhere(
    List<Map<String, dynamic>> source,
    bool Function(Map<String, dynamic>) predicate,
  ) {
    var count = 0;
    for (final item in source) {
      if (predicate(item)) {
        count++;
      }
    }
    return count;
  }

  bool _matchesFilter(Map<String, dynamic> pr, String filter) {
    switch (filter) {
      case 'pending':
        return _isPendingStatus(pr['status']) && !_isOverdueRequest(pr);
      case 'paid':
        return _isPaidStatus(pr['status']);
      case 'overdue':
        return _isOverdueRequest(pr);
      default:
        return true;
    }
  }

  bool _isPendingStatus(dynamic statusValue) {
    final status = (statusValue as String?)?.toLowerCase() ?? '';
    return status == 'pending' ||
        status == 'sent' ||
        status == 'member_marked';
  }

  bool _isAwaitingConfirmationStatus(dynamic statusValue) {
    final status = (statusValue as String?)?.toLowerCase();
    return status == 'member_marked';
  }

  bool _isPaidStatus(dynamic statusValue) {
    final status = (statusValue as String?)?.toLowerCase();
    return status == 'paid' ||
        status == 'owner_confirmed' ||
        status == 'auto_verified';
  }

  bool _isOverdueRequest(Map<String, dynamic> pr) {
    if (_isPaidStatus(pr['status'])) return false;
    final dueDateStr = pr['due_date'] as String?;
    if (dueDateStr == null || dueDateStr.isEmpty) return false;
    try {
      final dueDate = DateTime.parse(dueDateStr);
      return dueDate.isBefore(DateTime.now());
    } catch (_) {
      return false;
    }
  }
}

extension StringExtension on String {
  String capitalize() {
    if (isEmpty) return this;
    return '${this[0].toUpperCase()}${substring(1)}';
  }
}
