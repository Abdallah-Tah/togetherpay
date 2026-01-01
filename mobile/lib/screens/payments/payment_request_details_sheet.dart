import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../main.dart';
import '../../env.dart';
import '../../utils/theme.dart';

enum _PaymentAction { markSent, confirm, reminder, cancel }

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

  Future<void> _markAsSent() async {
    final prCode = widget.paymentRequest['pr_code'] as String?;
    if (prCode == null || prCode.isEmpty) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Missing payment code; unable to mark as paid'),
            backgroundColor: Colors.red,
          ),
        );
      }
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirm Payment Sent'),
        content: const Text(
          'Only confirm after you have sent the payment in your wallet app.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('I Have Paid'),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() {
      _processingAction = _PaymentAction.markSent;
    });

    try {
      await dio.post('/requests/by-code/$prCode/mark');

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Payment marked as sent'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context);
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Mark sent error: ${e.response?.data}');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data?['message'] as String? ??
                  'Failed to mark payment as sent',
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

  Future<void> _confirmReceived() async {
    final prCode = widget.paymentRequest['pr_code'] as String?;
    if (prCode == null || prCode.isEmpty) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Missing payment code; unable to confirm'),
            backgroundColor: Colors.red,
          ),
        );
      }
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirm Payment Received'),
        content: const Text(
          'Confirm only if you have received the payment from the member.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Confirm'),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() {
      _processingAction = _PaymentAction.confirm;
    });

    try {
      await dio.post('/requests/by-code/$prCode/confirm');

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Payment confirmed'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context);
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Confirm payment error: ${e.response?.data}');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data?['message'] as String? ??
                  'Failed to confirm payment',
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

  String _optionLabel(String method) {
    switch (method.toLowerCase()) {
      case 'cashapp':
      case 'cash_app':
        return 'Cash App';
      case 'venmo':
        return 'Venmo';
      default:
        return 'Payment';
    }
  }

  IconData _optionIcon(String method) {
    switch (method.toLowerCase()) {
      case 'cashapp':
      case 'cash_app':
        return Icons.attach_money;
      case 'venmo':
        return Icons.payments;
      default:
        return Icons.open_in_new;
    }
  }

  Future<void> _openPaymentOption(Map<String, dynamic> option) async {
    final raw =
        (option['uri'] as String? ?? option['web_fallback'] ?? '').trim();
    if (raw.isEmpty) return;
    Uri? uri = Uri.tryParse(raw);
    if (uri != null && !uri.hasScheme) {
      uri = Uri.parse('${Env.appBaseUrl}$raw');
    }
    if (uri == null) return;

    try {
      await launchUrl(uri, mode: LaunchMode.inAppBrowserView);
    } catch (e) {
      if (Env.debug) {
        debugPrint('Failed to open payment option: $e');
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Unable to open payment link'),
            backgroundColor: Colors.red,
          ),
        );
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
    final metadata = pr['metadata'] as Map<String, dynamic>?;
    final rawOptions = metadata?['payment_options'];
    final paymentOptions = rawOptions is List
        ? rawOptions
            .whereType<Map>()
            .map((option) => Map<String, dynamic>.from(option))
            .toList()
        : <Map<String, dynamic>>[];
    final squareUrl = metadata?['square_public_url'] as String?;
    final fallbackUrl =
        pr['deep_link'] as String? ?? pr['web_fallback_link'] as String?;
    final groupName = pr['cycle']?['group']?['name'] as String? ??
        pr['group']?['name'] as String? ??
        'Unknown Group';
    final amountCents = pr['amount_cents'] as int? ?? 0;
    final amount = amountCents / 100.0;
    final status = pr['status'] as String? ?? 'pending';
    final normalizedStatus = status.toLowerCase();
    final isOwner = pr['is_owner'] == true;
    final isMember = pr['is_member'] == true;
    final isPaid = normalizedStatus == 'paid' ||
        normalizedStatus == 'owner_confirmed' ||
        normalizedStatus == 'auto_verified';
    final isAwaitingConfirm = normalizedStatus == 'member_marked';
    final prCode = pr['pr_code'] as String?;
    final dueDateStr = pr['due_date'] as String?;
    final paidAtStr = pr['paid_at'] as String?;
    final sentAtStr = pr['sent_at'] as String?;
    final isProcessing = _isProcessing;
    final canCancelRequest = prCode != null && _canCancelStatus(status);
    final canMemberMark =
        isMember && !isPaid && !isAwaitingConfirm && _canMarkStatus(status);
    final canOwnerConfirm = isOwner && isAwaitingConfirm;
    final canOwnerNudge =
        isOwner && !isPaid && _canNudgeStatus(normalizedStatus);

    DateTime? dueDate;
    DateTime? paidAt;
    DateTime? sentAt;
    bool isOverdue = false;

    if (dueDateStr != null) {
      try {
        dueDate = DateTime.parse(dueDateStr);
        if (!isPaid && !isAwaitingConfirm && dueDate.isBefore(DateTime.now())) {
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
              Container(
                margin: const EdgeInsets.symmetric(vertical: 12),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              Expanded(
                child: ListView(
                  controller: scrollController,
                  padding: const EdgeInsets.all(AppTheme.spaceLarge),
                  children: [
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
                                    color:
                                        Theme.of(context).colorScheme.primary,
                                  ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: AppTheme.spaceLarge),
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
                    const SizedBox(height: AppTheme.spaceMedium),
                    if (paymentOptions.isNotEmpty ||
                        squareUrl != null ||
                        fallbackUrl != null) ...[
                      Text(
                        'Payment Options',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: AppTheme.spaceSmall),
                      if (squareUrl != null)
                        OutlinedButton.icon(
                          onPressed: () => _openPaymentOption({
                            'uri': squareUrl,
                            'method': 'square',
                          }),
                          icon: const Icon(Icons.credit_card),
                          label: const Text('Card / Apple Pay'),
                        ),
                      if (squareUrl != null && paymentOptions.isNotEmpty)
                        const SizedBox(height: AppTheme.spaceSmall),
                      Wrap(
                        spacing: AppTheme.spaceSmall,
                        runSpacing: AppTheme.spaceSmall,
                        children: paymentOptions.map((option) {
                          final method =
                              option['method']?.toString() ?? 'payment';
                          return OutlinedButton.icon(
                            onPressed: () => _openPaymentOption(option),
                            icon: Icon(_optionIcon(method)),
                            label: Text(_optionLabel(method)),
                          );
                        }).toList(),
                      ),
                      if (paymentOptions.isEmpty && squareUrl == null)
                        OutlinedButton.icon(
                          onPressed: fallbackUrl == null
                              ? null
                              : () => _openPaymentOption({
                                    'uri': fallbackUrl,
                                    'method': 'payment',
                                  }),
                          icon: const Icon(Icons.open_in_new),
                          label: const Text('Open Payment Page'),
                        ),
                    ],
                    const SizedBox(height: AppTheme.spaceLarge),
                    if (canMemberMark) ...[
                      FilledButton.icon(
                        onPressed: isProcessing ? null : _markAsSent,
                        icon: _processingAction == _PaymentAction.markSent
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  valueColor: AlwaysStoppedAnimation<Color>(
                                      Colors.white),
                                ),
                              )
                            : const Icon(Icons.check_circle),
                        label: const Text('I Have Paid'),
                      ),
                      const SizedBox(height: AppTheme.spaceSmall),
                    ],
                    if (canOwnerConfirm) ...[
                      FilledButton.icon(
                        onPressed: isProcessing ? null : _confirmReceived,
                        icon: _processingAction == _PaymentAction.confirm
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  valueColor: AlwaysStoppedAnimation<Color>(
                                      Colors.white),
                                ),
                              )
                            : const Icon(Icons.verified),
                        label: const Text('Confirm Received'),
                      ),
                      const SizedBox(height: AppTheme.spaceSmall),
                    ],
                    if (canOwnerNudge) ...[
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
                      const SizedBox(height: AppTheme.spaceSmall),
                    ],
                    if (isOwner && canCancelRequest) ...[
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
                      const SizedBox(height: AppTheme.spaceSmall),
                    ],
                    if (canMemberMark &&
                        (paymentOptions.isNotEmpty || squareUrl != null)) ...[
                      Text(
                        'Quick Pay',
                        style: Theme.of(context).textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: AppTheme.spaceSmall),
                      Wrap(
                        spacing: AppTheme.spaceSmall,
                        runSpacing: AppTheme.spaceSmall,
                        children: [
                          if (squareUrl != null)
                            OutlinedButton.icon(
                              onPressed: () => _openPaymentOption({
                                'uri': squareUrl,
                                'method': 'square',
                              }),
                              icon: const Icon(Icons.credit_card),
                              label: const Text('Card / Apple Pay'),
                            ),
                          ...paymentOptions.map((option) {
                            final method =
                                option['method']?.toString() ?? 'payment';
                            return OutlinedButton.icon(
                              onPressed: () => _openPaymentOption(option),
                              icon: Icon(_optionIcon(method)),
                              label: Text(_optionLabel(method)),
                            );
                          }),
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
                  decoration: onTap != null ? TextDecoration.underline : null,
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
    return normalized == 'pending' || normalized == 'sent';
  }

  bool _canMarkStatus(String? status) {
    if (status == null) return false;
    final normalized = status.toLowerCase();
    return normalized == 'pending' ||
        normalized == 'sent' ||
        normalized == 'overdue' ||
        normalized == 'late';
  }

  bool _canNudgeStatus(String status) {
    return status == 'pending' ||
        status == 'sent' ||
        status == 'member_marked' ||
        status == 'overdue' ||
        status == 'late';
  }
}
