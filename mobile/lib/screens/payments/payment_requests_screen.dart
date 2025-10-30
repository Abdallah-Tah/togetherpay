import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../main.dart';
import '../../pay_screen.dart';
import '../../widgets/payment_request_card.dart';

/// Payment requests screen showing all user's payment requests
///
/// Groups payment requests by status: Pending, Paid, Overdue
class PaymentRequestsScreen extends StatefulWidget {
  const PaymentRequestsScreen({super.key});

  @override
  State<PaymentRequestsScreen> createState() => _PaymentRequestsScreenState();
}

class _PaymentRequestsScreenState extends State<PaymentRequestsScreen> {
  bool _loading = true;
  String? _error;
  List<Map<String, dynamic>> _allRequests = [];

  // Filtered lists by status
  List<Map<String, dynamic>> _pendingRequests = [];
  List<Map<String, dynamic>> _paidRequests = [];
  List<Map<String, dynamic>> _overdueRequests = [];
  List<Map<String, dynamic>> _failedRequests = [];

  // Track which payment request is being retried
  String? _retryingRequestId;

  @override
  void initState() {
    super.initState();
    _loadPaymentRequests();
  }

  Future<void> _loadPaymentRequests() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await dio.get('/payment-requests');

      List<Map<String, dynamic>> requests;

      if (response.data is Map<String, dynamic>) {
        // API returns { data: [...] }
        final data = response.data as Map<String, dynamic>;
        final requestsList = data['data'] as List<dynamic>?;
        requests = requestsList?.map((r) => r as Map<String, dynamic>).toList() ?? [];
      } else if (response.data is List) {
        // API returns [...] directly
        requests = (response.data as List)
            .map((r) => r as Map<String, dynamic>)
            .toList();
      } else {
        throw Exception('Unexpected response format');
      }

      // Filter by status
      _pendingRequests = [];
      _paidRequests = [];
      _overdueRequests = [];
      _failedRequests = [];

      for (final request in requests) {
        final status = (request['status'] as String? ?? 'pending').toLowerCase();

        if (status == 'paid' || status == 'member_marked' || status == 'confirmed') {
          _paidRequests.add(request);
        } else if (status == 'failed' || status == 'error') {
          _failedRequests.add(request);
        } else if (status == 'overdue' || status == 'late') {
          _overdueRequests.add(request);
        } else {
          _pendingRequests.add(request);
        }
      }

      setState(() {
        _allRequests = requests;
        _loading = false;
      });

      if (Env.debug) {
        print('Loaded ${_allRequests.length} payment requests');
        print('Pending: ${_pendingRequests.length}, Paid: ${_paidRequests.length}, Overdue: ${_overdueRequests.length}, Failed: ${_failedRequests.length}');
      }
    } on DioException catch (e) {
      if (Env.debug) {
        print('Error loading payment requests: ${e.response?.data}');
      }

      setState(() {
        _error = e.response?.data?['message'] as String? ??
                 'Failed to load payment requests';
        _loading = false;
      });
    } catch (e) {
      if (Env.debug) {
        print('Unexpected error loading payment requests: $e');
      }

      setState(() {
        _error = 'An unexpected error occurred';
        _loading = false;
      });
    }
  }

  Future<void> _handleRefresh() async {
    await _loadPaymentRequests();
  }

  void _navigateToPayScreen(Map<String, dynamic> paymentRequest) {
    final prCode = paymentRequest['code'] as String?;

    if (prCode == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Invalid payment request code')),
      );
      return;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => PayScreen(
          prCode: prCode,
          deepLink: paymentRequest['deep_link'] as String?,
        ),
      ),
    ).then((_) {
      // Refresh payment requests when returning
      _loadPaymentRequests();
    });
  }

  Future<void> _retryPaymentRequest(Map<String, dynamic> paymentRequest) async {
    final requestId = paymentRequest['id'];
    
    if (requestId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Invalid payment request')),
      );
      return;
    }

    setState(() {
      _retryingRequestId = requestId.toString();
    });

    try {
      await dio.post('/payment-requests/$requestId/retry');

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Payment request will be resent'),
            backgroundColor: Colors.green,
          ),
        );

        // Refresh the list
        await _loadPaymentRequests();
      }
    } on DioException catch (e) {
      if (mounted) {
        final message = e.response?.data?['message'] as String? ??
            'Failed to retry payment request';
        
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _retryingRequestId = null;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Payment Requests'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loading ? null : _handleRefresh,
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(
        child: CircularProgressIndicator(),
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
                _error!,
                style: Theme.of(context).textTheme.bodyLarge,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              FilledButton.icon(
                onPressed: _loadPaymentRequests,
                icon: const Icon(Icons.refresh),
                label: const Text('Try Again'),
              ),
            ],
          ),
        ),
      );
    }

    if (_allRequests.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.payment_outlined,
                size: 80,
                color: Colors.grey.shade300,
              ),
              const SizedBox(height: 16),
              Text(
                'No payment requests',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      color: Colors.grey,
                    ),
              ),
              const SizedBox(height: 8),
              Text(
                'Your payment requests will appear here',
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: Colors.grey,
                    ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _handleRefresh,
      child: ListView(
        padding: const EdgeInsets.only(top: 8, bottom: 16),
        children: [
          // Failed section (highest priority)
          if (_failedRequests.isNotEmpty) ...[
            _buildSectionHeader(
              'Failed',
              _failedRequests.length,
              Colors.red.shade700,
              Icons.error,
            ),
            ..._failedRequests.map((request) {
              final requestId = request['id']?.toString();
              final isRetrying = requestId == _retryingRequestId;
              
              return PaymentRequestCard(
                paymentRequest: request,
                onTap: () => _navigateToPayScreen(request),
                onRetry: () => _retryPaymentRequest(request),
                isProcessingRetry: isRetrying,
              );
            }),
            const SizedBox(height: 16),
          ],

          // Overdue section
          if (_overdueRequests.isNotEmpty) ...[
            _buildSectionHeader(
              'Overdue',
              _overdueRequests.length,
              Colors.red,
              Icons.warning,
            ),
            ..._overdueRequests.map((request) {
              return PaymentRequestCard(
                paymentRequest: request,
                onTap: () => _navigateToPayScreen(request),
                onPay: () => _navigateToPayScreen(request),
              );
            }),
            const SizedBox(height: 16),
          ],

          // Pending section
          if (_pendingRequests.isNotEmpty) ...[
            _buildSectionHeader(
              'Pending',
              _pendingRequests.length,
              Colors.orange,
              Icons.pending,
            ),
            ..._pendingRequests.map((request) {
              return PaymentRequestCard(
                paymentRequest: request,
                onTap: () => _navigateToPayScreen(request),
                onPay: () => _navigateToPayScreen(request),
              );
            }),
            const SizedBox(height: 16),
          ],

          // Paid section
          if (_paidRequests.isNotEmpty) ...[
            _buildSectionHeader(
              'Paid',
              _paidRequests.length,
              Colors.green,
              Icons.check_circle,
            ),
            ..._paidRequests.map((request) {
              return PaymentRequestCard(
                paymentRequest: request,
                onTap: () => _navigateToPayScreen(request),
              );
            }),
          ],
        ],
      ),
    );
  }

  Widget _buildSectionHeader(
    String title,
    int count,
    Color color,
    IconData icon,
  ) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
      child: Row(
        children: [
          Icon(icon, size: 20, color: color),
          const SizedBox(width: 8),
          Text(
            title,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                  color: color,
                ),
          ),
          const SizedBox(width: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              count.toString(),
              style: TextStyle(
                color: color,
                fontWeight: FontWeight.bold,
                fontSize: 12,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
