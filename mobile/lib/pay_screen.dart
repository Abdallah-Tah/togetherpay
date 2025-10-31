import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import 'env.dart';
import 'main.dart';

class PayScreen extends StatefulWidget {
  final String prCode;
  final String? deepLink;

  const PayScreen({
    super.key,
    required this.prCode,
    this.deepLink,
  });

  @override
  State<PayScreen> createState() => _PayScreenState();
}

class _PayScreenState extends State<PayScreen> with WidgetsBindingObserver {
  bool _loading = true;
  bool _launching = false;
  bool _hasLaunchedWallet = false;
  bool _confirmationShown = false;
  String? _error;

  // Payment request data
  Map<String, dynamic>? _paymentData;
  String? _deepLinkUri;
  String? _webFallback;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _loadPaymentRequest();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);

    // When user returns to app after launching wallet, show confirmation dialog
    if (state == AppLifecycleState.resumed &&
        _hasLaunchedWallet &&
        !_confirmationShown &&
        mounted) {
      _confirmationShown = true;
      // Delay to ensure UI is ready
      Future.delayed(const Duration(milliseconds: 500), () {
        if (mounted) {
          _showPaymentConfirmationDialog();
        }
      });
    }
  }

  Future<void> _loadPaymentRequest() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      // Fetch payment request details from API
      final response = await dio.get('/requests/by-code/${widget.prCode}');
      final data = response.data as Map<String, dynamic>;

      setState(() {
        _paymentData = data;
        _deepLinkUri = data['deep_link'] as String?;
        _webFallback = data['web_fallback_link'] as String?;
        _loading = false;
      });

      // Auto-launch wallet after loading
      await Future.delayed(const Duration(milliseconds: 500));
      if (mounted) {
        _launchWallet();
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Error loading payment request: $e');
      }

      setState(() {
        _error = 'Unable to load payment request';
        _loading = false;
      });
    }
  }

  Future<void> _launchWallet() async {
    if (_launching) return;

    setState(() {
      _launching = true;
      _error = null;
    });

    try {
      Uri? targetUri;

      // Try deep link first (venmo:// or https://cash.app/...)
      if (_deepLinkUri != null) {
        final uri = Uri.parse(_deepLinkUri!);
        if (await canLaunchUrl(uri)) {
          targetUri = uri;
        }
      }

      // Try web fallback if deep link didn't work
      if (targetUri == null && _webFallback != null) {
        final uri = Uri.parse(_webFallback!);
        if (await canLaunchUrl(uri)) {
          targetUri = uri;
        }
      }

      // Try the short link from push notification
      if (targetUri == null && widget.deepLink != null) {
        final uri = Uri.parse(widget.deepLink!);
        if (await canLaunchUrl(uri)) {
          targetUri = uri;
        }
      }

      if (targetUri != null) {
        final launched = await launchUrl(
          targetUri,
          mode: LaunchMode.externalApplication,
        );

        if (launched) {
          setState(() {
            _hasLaunchedWallet = true;
          });
        } else {
          setState(() {
            _error = 'Unable to open payment app';
          });
        }
      } else {
        setState(() {
          _error = 'No compatible wallet app found.\nPlease install Cash App or Venmo.';
        });
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Error launching wallet: $e');
      }

      setState(() {
        _error = 'Failed to launch wallet app';
      });
    } finally {
      if (mounted) {
        setState(() {
          _launching = false;
        });
      }
    }
  }

  /// Show payment confirmation dialog automatically after returning from wallet
  Future<void> _showPaymentConfirmationDialog() async {
    final result = await showDialog<String>(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Text('Did you complete the payment?'),
        content: const Text(
          'Please confirm if you successfully sent the payment in your wallet app.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, 'later'),
            child: const Text('Remind me later'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, 'no'),
            child: const Text("I'll pay later"),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, 'yes'),
            child: const Text('Yes, I paid'),
          ),
        ],
      ),
    );

    if (!mounted) return;

    if (result == 'yes') {
      await _confirmPayment();
    } else if (result == 'later') {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('We\'ll remind you to confirm this payment'),
        ),
      );
      // TODO: Schedule local notification reminder after 5 minutes
    } else if (result == 'no') {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('You can pay anytime from your payment requests'),
        ),
      );
      Navigator.pop(context);
    }
  }

  /// Confirm payment by calling API
  Future<void> _confirmPayment() async {
    try {
      await dio.post(
        '/requests/by-code/${widget.prCode}/mark',
        data: {
          'evidence_url': null,
          'evidence_type': null,
        },
      );

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Payment marked as paid'),
            backgroundColor: Colors.green,
            duration: Duration(seconds: 2),
          ),
        );

        // Navigate back after a short delay
        Future.delayed(const Duration(milliseconds: 500), () {
          if (mounted) {
            Navigator.pop(context);
          }
        });
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Error marking as paid: ${e.response?.data}');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data['message'] ?? 'Failed to mark as paid',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected error: $e');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('An error occurred'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _markAsPaid() async {
    await _confirmPayment();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Pay • ${widget.prCode}'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Payment amount card
                  if (_paymentData != null) ...[
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          children: [
                            Text(
                              'Amount Due',
                              style: Theme.of(context).textTheme.titleMedium,
                            ),
                            const SizedBox(height: 8),
                            Text(
                              _paymentData!['amount_display'] ?? '\$0.00',
                              style: Theme.of(context)
                                  .textTheme
                                  .displayMedium
                                  ?.copyWith(
                                    fontWeight: FontWeight.bold,
                                    color: Theme.of(context).colorScheme.primary,
                                  ),
                            ),
                            const SizedBox(height: 16),
                            Text(
                              _paymentData!['group']?['name'] ?? 'Payment',
                              style: Theme.of(context).textTheme.bodyLarge,
                            ),
                            if (_paymentData!['note'] != null) ...[
                              const SizedBox(height: 8),
                              Text(
                                _paymentData!['note'],
                                style: Theme.of(context).textTheme.bodySmall,
                                textAlign: TextAlign.center,
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 32),
                  ],

                  // Status message
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: _error != null
                          ? Colors.orange.shade50
                          : Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          _error != null
                              ? Icons.warning_amber_rounded
                              : Icons.info_outline,
                          color: _error != null ? Colors.orange : Colors.blue,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            _error ??
                                'Your wallet should open automatically.\nIf not, tap "Open Wallet" below.',
                            style: TextStyle(
                              color: _error != null
                                  ? Colors.orange.shade900
                                  : Colors.blue.shade900,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 32),

                  // Action buttons
                  FilledButton.icon(
                    onPressed: _launching ? null : _launchWallet,
                    icon: _launching
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Icon(Icons.open_in_new),
                    label: Text(_launching ? 'Opening...' : 'Open Wallet'),
                  ),

                  const SizedBox(height: 12),

                  OutlinedButton.icon(
                    onPressed: _markAsPaid,
                    icon: const Icon(Icons.check_circle_outline),
                    label: const Text('Mark as Paid'),
                  ),

                  const SizedBox(height: 32),

                  // Instructions
                  const Text(
                    'How it works:',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 12),
                  _buildStep(1, 'Tap "Open Wallet"'),
                  _buildStep(2, 'Review pre-filled amount'),
                  _buildStep(3, 'Send payment'),
                  _buildStep(4, 'Return here and tap "Mark as Paid"'),

                  const SizedBox(height: 24),

                  // PR Code reference
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.qr_code, size: 20),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            widget.prCode,
                            style: const TextStyle(
                              fontFamily: 'monospace',
                              fontSize: 12,
                            ),
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

  Widget _buildStep(int number, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          CircleAvatar(
            radius: 12,
            backgroundColor: Theme.of(context).colorScheme.primaryContainer,
            child: Text(
              number.toString(),
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.onPrimaryContainer,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(child: Text(text)),
        ],
      ),
    );
  }
}
