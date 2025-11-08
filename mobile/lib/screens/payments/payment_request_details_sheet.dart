import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../main.dart';
import '../../env.dart';
import '../../utils/theme.dart';

enum _PaymentAction { markPaid, reminder, cancel }

/// Payment Request Details Bottom Sheet
class PaymentRequestDetailsSheet extends StatefulWidget {
  final Map<String, dynamic> paymentRequest;

  const PaymentRequestDetailsSheet({
    super.key,
    required this.paymentRequest,
  });

  @override
  State<PaymentRequestDetailsSheet> createState() =>
      _PaymentRequestDetailsSheetState();
}

class _PaymentRequestDetailsSheetState
    extends State<PaymentRequestDetailsSheet> {
  _PaymentAction? _processingAction;

  bool get _isProcessing => _processingAction != null;

  Future<void> _markAsPaid() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Mark as Paid'),
        content: const Text(
          'Are you sure you want to mark this payment request as paid?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Mark Paid'),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() {
      _processingAction = _PaymentAction.markPaid;
    });

    try {
      final prId = widget.paymentRequest['id'];
      await dio.post('/payment-requests/$prId/mark-paid');

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Payment marked as paid successfully'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context);
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Mark paid error: ${e.response?.data}');
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
    } finally {
      if (mounted) {
        setState(() {
          _processingAction = null;
        });
      }
    }
  }

  Future<void> _sendReminder() async {
    setState(() {
      _processingAction = _PaymentAction.reminder;
    });

    try {
      final prCode = widget.paymentRequest['pr_code'];
      await dio.post('/requests/by-code/$prCode/nudge');

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Reminder sent successfully'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Send reminder error: ${e.response?.data}');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data?['message'] as String? ??
                  'Failed to send reminder',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _processingAction = null;
        });
      }
    }
  }

  Future<void> _cancelRequest() async {
    final prCode = widget.paymentRequest['pr_code'] as String?;
    if (prCode == null || prCode.isEmpty) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Missing payment code; unable to cancel request'),
            backgroundColor: Colors.red,
          ),
        );
      }
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancel Payment Request?'),
        content: const Text(
          'Cancelling will archive this request so the member no longer sees it.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Keep Request'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(
              backgroundColor: Colors.red,
            ),
            child: const Text('Yes, Cancel'),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() {
      _processingAction = _PaymentAction.cancel;
    });

    try {
      await dio.post('/requests/by-code/$prCode/cancel');

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Payment request cancelled'),
            backgroundColor: Colors.orange,
          ),
        );
        Navigator.pop(context);
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Cancel error: ${e.response?.data}');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data?['message'] as String? ??
                  'Failed to cancel payment request',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected cancel error: $e');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('An unexpected error occurred'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _processingAction = null;
        });
      }
    }
  }

  Future<void> _openPaymentApp(String app, String? handle) async {
    if (handle == null || handle.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('No $app handle found for this member'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final amount = (widget.paymentRequest['amount_cents'] as int? ?? 0) / 100.0;
    final amountStr = amount.toStringAsFixed(2);
    final prCode = widget.paymentRequest['pr_code'] as String? ?? '';
    final note = 'Payment for ${widget.paymentRequest['group']?['name'] ?? 'TogetherPay'}';

    Uri? deepLinkUri;
    Uri? fallbackUri;

    if (app == 'Cash App') {
      // Clean the cashtag (remove $ if present)
      final cashtag = handle.startsWith('\$') ? handle.substring(1) : handle;
      // Cash App doesn't have a reliable deep link for payment requests
      // Use web URL which will prompt to open in app if installed
      fallbackUri = Uri.parse('https://cash.app/\$$cashtag/$amountStr?note=${Uri.encodeComponent(note)}&pr=${Uri.encodeComponent(prCode)}');
      deepLinkUri = fallbackUri; // Use same URL for both
    } else if (app == 'Venmo') {
      // Clean the username (remove @ if present)
      final username = handle.startsWith('@') ? handle.substring(1) : handle;
      // Use deep link for Venmo
      deepLinkUri = Uri.parse('venmo://paycharge?txn=pay&recipients=$username&amount=$amountStr&note=${Uri.encodeComponent(note)}');
      // Web fallback
      fallbackUri = Uri.parse('https://venmo.com/$username?txn=pay&amount=$amountStr&note=${Uri.encodeComponent(note)}');
    }

    if (deepLinkUri != null) {
      try {
        // Try deep link first
        bool launched = false;
        try {
          launched = await launchUrl(deepLinkUri, mode: LaunchMode.externalApplication);
        } catch (e) {
          if (Env.debug) {
            debugPrint('Deep link failed, trying fallback: $e');
          }
        }

        // If deep link failed, try web fallback
        if (!launched && fallbackUri != null) {
          if (await canLaunchUrl(fallbackUri)) {
            await launchUrl(fallbackUri, mode: LaunchMode.externalApplication);
          } else {
            if (mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text('$app is not installed'),
                  backgroundColor: Colors.orange,
                ),
              );
            }
          }
        }
      } catch (e) {
        if (Env.debug) {
          debugPrint('Error launching $app: $e');
        }
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Failed to open $app'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final pr = widget.paymentRequest;
    final memberName = pr['member']?['name'] as String? ??
        pr['member']?['user']?['name'] as String? ??
        'Unknown Member';
    final memberEmail = pr['member']?['email'] as String? ??
        pr['member']?['user']?['email'] as String?;
    final memberPhone = pr['member']?['phone'] as String? ??
        pr['member']?['user']?['phone'] as String?;
    final cashAppHandle = pr['member']?['cashapp_cashtag'] as String?;
    final venmoHandle = pr['member']?['venmo_username'] as String?;
    final groupName = pr['cycle']?['group']?['name'] as String? ??
        pr['group']?['name'] as String? ??
        'Unknown Group';
    final amountCents = pr['amount_cents'] as int? ?? 0;
    final amount = amountCents / 100.0;
    final status = pr['status'] as String? ?? 'pending';
    final normalizedStatus = status.toLowerCase();
    final isPaid = normalizedStatus == 'paid' ||
        normalizedStatus == 'owner_confirmed' ||
        normalizedStatus == 'auto_verified';
    final isAwaitingConfirm = normalizedStatus == 'member_marked';
    final prCode = pr['pr_code'] as String?;
    final dueDateStr = pr['due_date'] as String?;
    final paidAtStr = pr['paid_at'] as String?;
    final sentAtStr = pr['sent_at'] as String?;
    final isProcessing = _isProcessing;
    final canCancelRequest =
        prCode != null && _canCancelStatus(status);
    final showStandardActions = !isPaid && !isAwaitingConfirm;

    DateTime? dueDate;
    DateTime? paidAt;
    DateTime? sentAt;
    bool isOverdue = false;

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

    if (sentAtStr != null) {
      try {
        sentAt = DateTime.parse(sentAtStr);
      } catch (e) {
        if (Env.debug) {
          debugPrint('Error parsing sent at: $e');
        }
      }
    }

    return DraggableScrollableSheet(
      initialChildSize: 0.7,
      minChildSize: 0.5,
      maxChildSize: 0.95,
      builder: (context, scrollController) {
        return Container(
          decoration: BoxDecoration(
            color: Theme.of(context).scaffoldBackgroundColor,
            borderRadius: const BorderRadius.vertical(
              top: Radius.circular(AppTheme.radiusLarge),
            ),
          ),
          child: Column(
            children: [
              // Drag handle
              Container(
                margin: const EdgeInsets.symmetric(vertical: 12),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              // Content
              Expanded(
                child: ListView(
                  controller: scrollController,
                  padding: const EdgeInsets.all(AppTheme.spaceLarge),
                  children: [
                    // Header
                    Row(
                      children: [
                        CircleAvatar(
                          radius: 30,
                          backgroundColor:
                              Theme.of(context).colorScheme.primaryContainer,
                          child: Text(
                            memberName.isNotEmpty
                                ? memberName[0].toUpperCase()
                                : '?',
                            style: TextStyle(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onPrimaryContainer,
                              fontWeight: FontWeight.bold,
                              fontSize: 24,
                            ),
                          ),
                        ),
                        const SizedBox(width: AppTheme.spaceMedium),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                memberName,
                                style: Theme.of(context)
                                    .textTheme
                                    .titleLarge
                                    ?.copyWith(
                                      fontWeight: FontWeight.bold,
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
                      ],
                    ),
                    const SizedBox(height: AppTheme.spaceLarge),

                    // Amount card
                    Card(
                      elevation: 0,
                      color: Theme.of(context)
                          .colorScheme
                          .primaryContainer
                          .withValues(alpha: 0.3),
                      child: Padding(
                        padding: const EdgeInsets.all(AppTheme.spaceLarge),
                        child: Column(
                          children: [
                            Text(
                              'Amount',
                              style: TextStyle(
                                color: Colors.grey.shade600,
                                fontSize: 14,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '\$${amount.toStringAsFixed(2)}',
                              style: Theme.of(context)
                                  .textTheme
                                  .displaySmall
                                  ?.copyWith(
                                    fontWeight: FontWeight.bold,
                                    color: Theme.of(context).colorScheme.primary,
                                  ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: AppTheme.spaceLarge),

                    // Status info
                    _buildInfoRow(
                      'Status',
                      isPaid
                          ? 'Paid'
                          : isAwaitingConfirm
                              ? 'Awaiting Confirmation'
                              : isOverdue
                                  ? 'Overdue'
                                  : 'Pending',
                      isPaid
                          ? Colors.green
                          : isAwaitingConfirm
                              ? Colors.blue
                              : isOverdue
                                  ? Colors.red
                                  : Colors.orange,
                    ),
                    if (dueDate != null)
                      _buildInfoRow(
                        'Due Date',
                        DateFormat('MMM d, yyyy').format(dueDate),
                        null,
                      ),
                    if (paidAt != null)
                      _buildInfoRow(
                        'Paid At',
                        DateFormat('MMM d, yyyy h:mm a').format(paidAt),
                        Colors.green,
                      ),
                    if (sentAt != null)
                      _buildInfoRow(
                        'Sent At',
                        DateFormat('MMM d, yyyy h:mm a').format(sentAt),
                        null,
                      ),
                    if (prCode != null)
                      _buildInfoRow(
                        'Payment Code',
                        prCode,
                        null,
                        copyable: true,
                      ),

                    const SizedBox(height: AppTheme.spaceLarge),
                    const Divider(),
                    const SizedBox(height: AppTheme.spaceMedium),

                    // Contact info
                    Text(
                      'Contact Information',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: AppTheme.spaceMedium),
                    if (memberEmail != null)
                      _buildContactRow(Icons.email, memberEmail),
                    if (memberPhone != null)
                      _buildContactRow(Icons.phone, memberPhone),
                    if (cashAppHandle != null)
                      _buildContactRow(
                        Icons.attach_money,
                        cashAppHandle,
                        onTap: () => _openPaymentApp('Cash App', cashAppHandle),
                      ),
                    if (venmoHandle != null)
                      _buildContactRow(
                        Icons.payments,
                        venmoHandle,
                        onTap: () => _openPaymentApp('Venmo', venmoHandle),
                      ),

                    const SizedBox(height: AppTheme.spaceLarge),

                    // Actions
                    if (showStandardActions) ...[
                      FilledButton.icon(
                        onPressed: isProcessing ? null : _markAsPaid,
                        icon: _processingAction == _PaymentAction.markPaid
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  valueColor:
                                      AlwaysStoppedAnimation<Color>(Colors.white),
                                ),
                              )
                            : const Icon(Icons.check_circle),
                        label: const Text('Mark as Paid'),
                      ),
                      const SizedBox(height: AppTheme.spaceSmall),
                      OutlinedButton.icon(
                        onPressed: isProcessing ? null : _sendReminder,
                        icon: _processingAction == _PaymentAction.reminder
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            : const Icon(Icons.notifications),
                        label: const Text('Send Reminder'),
                      ),
                    ],
                    if (canCancelRequest) ...[
                      const SizedBox(height: AppTheme.spaceSmall),
                      OutlinedButton.icon(
                        onPressed: isProcessing ? null : _cancelRequest,
                        icon: _processingAction == _PaymentAction.cancel
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  valueColor:
                                      AlwaysStoppedAnimation<Color>(Colors.red),
                                ),
                              )
                            : const Icon(Icons.cancel_outlined),
                        label: const Text('Cancel Request'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.red,
                          side: const BorderSide(color: Colors.red),
                        ),
                      ),
                    ],

                    // Quick payment buttons
                    if (showStandardActions &&
                        (cashAppHandle != null || venmoHandle != null)) ...[
                      const SizedBox(height: AppTheme.spaceMedium),
                      Text(
                        'Quick Pay',
                        style:
                            Theme.of(context).textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                      ),
                      const SizedBox(height: AppTheme.spaceSmall),
                      Row(
                        children: [
                          if (cashAppHandle != null)
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed: () =>
                                    _openPaymentApp('Cash App', cashAppHandle),
                                icon: const Icon(Icons.attach_money),
                                label: const Text('Cash App'),
                              ),
                            ),
                          if (cashAppHandle != null && venmoHandle != null)
                            const SizedBox(width: AppTheme.spaceSmall),
                          if (venmoHandle != null)
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed: () =>
                                    _openPaymentApp('Venmo', venmoHandle),
                                icon: const Icon(Icons.payments),
                                label: const Text('Venmo'),
                              ),
                            ),
                        ],
                      ),
                    ],

                    const SizedBox(height: AppTheme.spaceLarge),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildInfoRow(String label, String value, Color? valueColor,
      {bool copyable = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppTheme.spaceSmall),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              color: Colors.grey.shade600,
              fontSize: 14,
            ),
          ),
          Row(
            children: [
              Text(
                value,
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: valueColor,
                  fontSize: 14,
                ),
              ),
              if (copyable) ...[
                const SizedBox(width: 8),
                InkWell(
                  onTap: () {
                    Clipboard.setData(ClipboardData(text: value));
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('Copied to clipboard'),
                        duration: Duration(seconds: 1),
                      ),
                    );
                  },
                  child: Icon(
                    Icons.copy,
                    size: 16,
                    color: Colors.grey.shade600,
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildContactRow(IconData icon, String value, {VoidCallback? onTap}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppTheme.spaceSmall),
      child: InkWell(
        onTap: onTap,
        child: Row(
          children: [
            Icon(
              icon,
              size: 20,
              color: Colors.grey.shade600,
            ),
            const SizedBox(width: AppTheme.spaceSmall),
            Expanded(
              child: Text(
                value,
                style: TextStyle(
                  fontSize: 14,
                  color: onTap != null
                      ? Theme.of(context).colorScheme.primary
                      : null,
                  decoration:
                      onTap != null ? TextDecoration.underline : null,
                ),
              ),
            ),
            if (onTap != null)
              Icon(
                Icons.chevron_right,
                size: 20,
                color: Colors.grey.shade400,
              ),
          ],
        ),
      ),
    );
  }

  bool _canCancelStatus(String? status) {
    if (status == null) return false;
    final normalized = status.toLowerCase();
    return normalized == 'pending' ||
        normalized == 'sent' ||
        normalized == 'member_marked';
  }
}
