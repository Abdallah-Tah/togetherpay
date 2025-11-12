import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import 'env.dart';
import 'services/api_service.dart';

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
  final Dio _dio = ApiService().dio;

  bool _loading = true;
  bool _launching = false;
  bool _hasLaunchedWallet = false;
  bool _confirmationShown = false;
  String? _error;

  Map<String, dynamic>? _paymentData;
  String? _deepLinkUri;
  String? _webFallback;
  bool _isSquareInvoice = false;
  Map<String, dynamic>? _squareInvoice;
  String? _squareInvoiceUrl;
  String? _squareInvoiceStatus;
  String? _squareInvoiceDueDate;

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

    if (state == AppLifecycleState.resumed &&
        _hasLaunchedWallet &&
        !_confirmationShown &&
        mounted) {
      _confirmationShown = true;
      Future.delayed(const Duration(milliseconds: 400), () {
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
      _squareInvoice = null;
      _squareInvoiceStatus = null;
      _squareInvoiceDueDate = null;
    });

    try {
      final response = await _dio.get('/requests/by-code/${widget.prCode}');
      final data = response.data as Map<String, dynamic>;

      if (!mounted) return;

      setState(() {
        _paymentData = data;
        _deepLinkUri = data['deep_link'] as String?;
        _webFallback = data['web_fallback_link'] as String?;
        _isSquareInvoice = data['square_invoice_id'] != null;
        final metadata = data['metadata'];
        if (metadata is Map<String, dynamic>) {
          _squareInvoiceUrl = metadata['square_public_url'] as String?;
        }
      });

      if (_isSquareInvoice) {
        await _loadSquareInvoiceDetails();
      } else {
        if (mounted) {
          setState(() {
            _loading = false;
          });
        }
        await Future.delayed(const Duration(milliseconds: 400));
        if (mounted) {
          _launchWallet();
        }
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Error loading payment request: ${e.response?.data}');
      }

      if (!mounted) return;

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Unable to load payment request';
        _loading = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected error loading payment request: $e');
      }

      if (!mounted) return;

      setState(() {
        _error = 'Unable to load payment request';
        _loading = false;
      });
    }
  }

  Future<void> _loadSquareInvoiceDetails() async {
    try {
      final response =
          await _dio.get('/requests/by-code/${widget.prCode}/square-invoice');
      final data = Map<String, dynamic>.from(response.data as Map);

      if (!mounted) return;

      setState(() {
        _squareInvoice = data;
        _squareInvoiceStatus = data['status'] as String?;
        _squareInvoiceDueDate = data['due_date'] as String?;
        _squareInvoiceUrl = data['public_url'] as String? ?? _squareInvoiceUrl;
        _loading = false;
      });
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Error loading Square invoice: ${e.response?.data}');
      }
      if (!mounted) return;
      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Unable to load Square invoice';
        _loading = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected error loading Square invoice: $e');
      }
      if (!mounted) return;
      setState(() {
        _error = 'Unable to load Square invoice';
        _loading = false;
      });
    }
  }

  Future<void> _launchWallet() async {
    if (_isSquareInvoice) {
      return;
    }

    if (_launching) return;

    setState(() {
      _launching = true;
      _error = null;
    });

    try {
      Uri? targetUri;

      // Try deep_link first (from API response)
      if (_deepLinkUri != null) {
        targetUri = Uri.tryParse(_deepLinkUri!);
      }

      // Fallback to web_fallback
      if (targetUri == null && _webFallback != null) {
        targetUri = Uri.tryParse(_webFallback!);
      }

      // Last resort: widget deep link
      if (targetUri == null && widget.deepLink != null) {
        targetUri = Uri.tryParse(widget.deepLink!);
      }

      // CRITICAL: Use redirect page for Cash App to force browser opening
      // This prevents Android from intercepting the app link
      if (targetUri != null &&
          targetUri.toString().startsWith('https://cash.app/')) {
        // Parse Cash App URL to extract cashtag and amount
        // Format: https://cash.app/$cashtag/amount
        final cashAppUrl = targetUri.toString();
        final match = RegExp(r'https://cash\.app/\$([^/]+)/([0-9.]+)')
            .firstMatch(cashAppUrl);
        if (match != null) {
          final cashtag = match.group(1);
          final amount = match.group(2);

          // Get note from payment data (group name + note)
          final groupName =
              _paymentData?['group']?['name'] as String? ?? 'Payment';
          final paymentNote = _paymentData?['note'] as String?;
          final note = paymentNote != null && paymentNote.isNotEmpty
              ? '$groupName - $paymentNote'
              : groupName;

          if (Env.debug) {
            debugPrint('=== NOTE DEBUG ===');
            debugPrint('Group name: $groupName');
            debugPrint('Payment note: $paymentNote');
            debugPrint('Combined note: $note');
            debugPrint('Payment data keys: ${_paymentData?.keys}');
            debugPrint('Full payment data: $_paymentData');
          }

          // Use redirect page instead of direct Cash App URL
          final baseUrl = Env.apiBase.replaceAll('/api/v1', '');
          final encodedNote = Uri.encodeComponent(note);

          if (Env.debug) {
            debugPrint('Encoded note: $encodedNote');
            debugPrint('Base URL: $baseUrl');
          }

          targetUri = Uri.parse(
              '$baseUrl/redirect/cashapp?cashtag=$cashtag&amount=$amount&note=$encodedNote');

          if (Env.debug) {
            debugPrint('Final redirect URL: $targetUri');
          }
        }
      }

      if (targetUri != null) {
        if (Env.debug) {
          debugPrint('Launching wallet with inAppBrowserView: $targetUri');
        }

        // Use inAppBrowserView - this is the working approach from the DEBUG button
        // Opens in-app browser which then properly redirects to the wallet app
        final launched = await launchUrl(
          targetUri,
          mode: LaunchMode.inAppBrowserView,
        );

        if (Env.debug) {
          debugPrint('Wallet launched: $launched');
        }

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
          _error =
              'No compatible wallet app found. Install Cash App or Venmo and try again.';
        });
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Error launching wallet: $e');
      }

      if (mounted) {
        setState(() {
          _error = 'Failed to launch wallet app';
        });
      }
    } finally {
      if (mounted) {
        setState(() {
          _launching = false;
        });
      }
    }
  }

  Future<void> _openLink(String url,
      {LaunchMode mode = LaunchMode.inAppBrowserView}) async {
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    try {
      await launchUrl(uri, mode: mode);
    } catch (e) {
      if (Env.debug) {
        debugPrint('Failed to open link $url: $e');
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Unable to open link'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _showPaymentConfirmationDialog() async {
    final result = await showDialog<String>(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Text('Did you complete the payment?'),
        content: const Text(
          'Confirm once you have sent the payment in your wallet app.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, 'later'),
            child: const Text('Not yet'),
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
          content: Text('No worries—confirm when you are ready.'),
        ),
      );
    }
  }

  Future<void> _confirmPayment() async {
    try {
      await _dio.post(
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

        Future.delayed(const Duration(milliseconds: 400), () {
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
              e.response?.data?['message'] as String? ??
                  'Failed to mark payment as paid',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected error marking as paid: $e');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('An unexpected error occurred'),
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
    final hasError = _error != null;
    final infoMessage = _error ??
        (_isSquareInvoice
            ? 'Cash App will notify you automatically. Use "Open Invoice" below if you need to review it manually.'
            : 'Your wallet should open automatically. If not, tap "Open Wallet".');
    final infoBackground = hasError
        ? Colors.orange.shade50
        : (_isSquareInvoice ? Colors.green.shade50 : Colors.blue.shade50);
    final infoIconColor = hasError
        ? Colors.orange
        : (_isSquareInvoice ? Colors.green : Colors.blueAccent);
    final infoTextColor = hasError
        ? Colors.orange.shade900
        : (_isSquareInvoice ? Colors.green.shade900 : Colors.blue.shade900);

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
                                    color:
                                        Theme.of(context).colorScheme.primary,
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
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: infoBackground,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          hasError
                              ? Icons.warning_amber_rounded
                              : (_isSquareInvoice
                                  ? Icons.notifications_active
                                  : Icons.info_outline),
                          color: infoIconColor,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            infoMessage,
                            style: TextStyle(color: infoTextColor),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 32),
                  if (_isSquareInvoice) ...[
                    _buildSquareInvoiceCard(),
                    const SizedBox(height: 16),
                    FilledButton.icon(
                      onPressed: _squareInvoiceUrl == null
                          ? null
                          : () => _openLink(
                                _squareInvoiceUrl!,
                                mode: LaunchMode.inAppBrowserView,
                              ),
                      icon: const Icon(Icons.open_in_new),
                      label: const Text('Open Invoice'),
                    ),
                    const SizedBox(height: 12),
                    OutlinedButton.icon(
                      onPressed: _loadPaymentRequest,
                      icon: const Icon(Icons.refresh),
                      label: const Text('Refresh Status'),
                    ),
                    const SizedBox(height: 32),
                    const Text(
                      'What happens next:',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 12),
                    _buildStep(1, 'Open Cash App and look for TogetherPay'),
                    _buildStep(2, 'Tap Continue to approve the payment'),
                    _buildStep(
                      3,
                      'We auto-mark this request once Cash App finishes processing',
                    ),
                  ] else ...[
                    OutlinedButton.icon(
                      onPressed: () async {
                        final url = _deepLinkUri ?? _webFallback;
                        if (url != null) {
                          final uri = Uri.tryParse(url);
                          if (uri != null) {
                            final launched = await launchUrl(
                              uri,
                              mode: LaunchMode.inAppBrowserView,
                            );
                            if (launched && mounted) {
                              setState(() {
                                _hasLaunchedWallet = true;
                              });
                            }
                          }
                        }
                      },
                      icon: const Icon(Icons.open_in_new),
                      label: const Text('Open Wallet'),
                    ),
                    const SizedBox(height: 12),
                    OutlinedButton.icon(
                      onPressed: _markAsPaid,
                      icon: const Icon(Icons.check_circle_outline),
                      label: const Text('Mark as Paid'),
                    ),
                    if (Env.debug) ...[
                      const SizedBox(height: 12),
                      OutlinedButton.icon(
                        onPressed: () async {
                          final url = _deepLinkUri ?? _webFallback;
                          if (url != null) {
                            final uri = Uri.tryParse(url);
                            if (uri != null) {
                              final launched = await launchUrl(
                                uri,
                                mode: LaunchMode.inAppBrowserView,
                              );
                              if (launched && mounted) {
                                setState(() {
                                  _hasLaunchedWallet = true;
                                });
                              }
                            }
                          }
                        },
                        icon: const Icon(Icons.bug_report),
                        label: const Text('DEBUG: Open in Browser'),
                      ),
                    ],
                    const SizedBox(height: 32),
                    const Text(
                      'How it works:',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 12),
                    _buildStep(1, 'Tap "Open Wallet"'),
                    _buildStep(2, 'Review the pre-filled amount'),
                    _buildStep(3, 'Send the payment'),
                    _buildStep(4, 'Confirm here once sent'),
                  ],
                  const SizedBox(height: 24),
                  _buildCodeCard(),
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
              '$number',
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

  Widget _buildSquareInvoiceCard() {
    final amountDisplay =
        _paymentData?['amount_display'] as String? ?? '\$0.00';
    final invoiceId =
        _paymentData?['square_invoice_id'] ?? _squareInvoice?['invoice_id'];
    final statusLabel = (_squareInvoiceStatus ??
            _paymentData?['status'] ??
            _squareInvoice?['status'] ??
            'pending')
        .toString()
        .toUpperCase();
    final dueLabel = _squareInvoiceDueDate ??
        _paymentData?['cycle']?['due_at'] ??
        _squareInvoice?['due_date'];
    final normalizedDue = dueLabel?.split('T').first;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.receipt_long, size: 28),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Square Cash App Invoice',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                ),
                Chip(
                  label: Text(statusLabel),
                  backgroundColor: statusLabel == 'PAID'
                      ? Colors.green.shade100
                      : Colors.orange.shade100,
                ),
              ],
            ),
            const SizedBox(height: 16),
            Text(
              amountDisplay,
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            if (normalizedDue != null) ...[
              const SizedBox(height: 8),
              Text(
                'Due $normalizedDue',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            ],
            if (invoiceId != null) ...[
              const SizedBox(height: 8),
              Text(
                'Invoice ID: $invoiceId',
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildCodeCard() {
    return Container(
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
    );
  }
}
