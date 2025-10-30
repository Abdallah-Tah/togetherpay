import 'package:flutter/material.dart';

/// Reusable payment request card widget.
///
/// Displays payment request details with optional actionable buttons for
/// paying, nudging, or confirming payments depending on status.
class PaymentRequestCard extends StatelessWidget {
  final Map<String, dynamic> paymentRequest;
  final VoidCallback? onTap;
  final VoidCallback? onPay;
  final VoidCallback? onNudge;
  final VoidCallback? onConfirm;
  final VoidCallback? onRetry;
  final bool isProcessingNudge;
  final bool isProcessingConfirm;
  final bool isProcessingRetry;

  const PaymentRequestCard({
    super.key,
    required this.paymentRequest,
    this.onTap,
    this.onPay,
    this.onNudge,
    this.onConfirm,
    this.onRetry,
    this.isProcessingNudge = false,
    this.isProcessingConfirm = false,
    this.isProcessingRetry = false,
  });

  @override
  Widget build(BuildContext context) {
    final amount = paymentRequest['amount_display'] as String? ?? '\$0.00';
    final status = paymentRequest['status'] as String? ?? 'pending';
    final groupName = paymentRequest['group']?['name'] as String? ?? 'Payment';
    final dueDate = paymentRequest['due_date'] as String?;
    final code = paymentRequest['code'] as String? ?? '';

    // Determine status color and icon
    Color statusColor;
    IconData statusIcon;
    String statusLabel;

    switch (status.toLowerCase()) {
      case 'paid':
      case 'confirmed':
        statusColor = Colors.green;
        statusIcon = Icons.check_circle;
        statusLabel = 'Paid';
        break;
      case 'member_marked':
        statusColor = Colors.amber.shade800;
        statusIcon = Icons.hourglass_bottom;
        statusLabel = 'Awaiting confirm';
        break;
      case 'failed':
      case 'error':
        statusColor = Colors.red.shade700;
        statusIcon = Icons.error;
        statusLabel = 'Failed';
        break;
      case 'overdue':
      case 'late':
        statusColor = Colors.red;
        statusIcon = Icons.warning;
        statusLabel = 'Overdue';
        break;
      case 'sent':
        statusColor = Colors.blue;
        statusIcon = Icons.send;
        statusLabel = 'Sent';
        break;
      case 'pending':
      default:
        statusColor = Colors.orange;
        statusIcon = Icons.pending;
        statusLabel = 'Pending';
        break;
    }

    final dueDateLabel = _formatDueDateLabel(dueDate);
    final showPayButton = onPay != null && _canShowPayButton(status);
    final showNudgeButton = onNudge != null && _canShowNudge(status);
    final showConfirmButton = onConfirm != null && _canShowConfirm(status);
    final showRetryButton = onRetry != null && _canShowRetry(status);

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header row with amount and status
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  // Amount
                  Text(
                    amount,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: Theme.of(context).colorScheme.primary,
                        ),
                  ),

                  // Status badge
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: statusColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: statusColor.withOpacity(0.3),
                        width: 1,
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
                          statusLabel,
                          style: TextStyle(
                            color: statusColor,
                            fontWeight: FontWeight.bold,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 8),

              // Group name
              Text(
                groupName,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w500,
                    ),
              ),

              const SizedBox(height: 4),

              // Due date
              if (dueDateLabel != null) ...[
                Row(
                  children: [
                    Icon(
                      Icons.calendar_today,
                      size: 14,
                      color: Colors.grey[600],
                    ),
                    const SizedBox(width: 4),
                    Text(
                      'Due: $dueDateLabel',
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: Colors.grey[600],
                          ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
              ],

              // Payment code
              Row(
                children: [
                  Expanded(
                    child: Text(
                      code,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            fontFamily: 'monospace',
                            color: Colors.grey[500],
                          ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (showPayButton) ...[
                    const SizedBox(width: 8),
                    FilledButton.icon(
                      onPressed: onPay,
                      icon: const Icon(Icons.payment, size: 18),
                      label: const Text('Pay'),
                      style: FilledButton.styleFrom(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 8,
                        ),
                      ),
                    ),
                  ],
                ],
              ),

              if (showNudgeButton || showConfirmButton || showRetryButton) ...[
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  alignment: WrapAlignment.end,
                  children: [
                    if (showRetryButton)
                      OutlinedButton.icon(
                        onPressed: isProcessingRetry ? null : onRetry,
                        icon: isProcessingRetry
                            ? const SizedBox(
                                width: 16,
                                height: 16,
                                child:
                                    CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Icon(Icons.refresh),
                        label: const Text('Retry'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.orange,
                        ),
                      ),
                    if (showNudgeButton)
                      OutlinedButton.icon(
                        onPressed: isProcessingNudge ? null : onNudge,
                        icon: isProcessingNudge
                            ? const SizedBox(
                                width: 16,
                                height: 16,
                                child:
                                    CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Icon(Icons.notifications_active_outlined),
                        label: const Text('Remind'),
                      ),
                    if (showConfirmButton)
                      FilledButton.icon(
                        onPressed: isProcessingConfirm ? null : onConfirm,
                        icon: isProcessingConfirm
                            ? const SizedBox(
                                width: 16,
                                height: 16,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : const Icon(Icons.verified_outlined),
                        label: const Text('Confirm'),
                      ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  bool _canShowPayButton(String status) {
    final normalized = status.toLowerCase();
    return normalized == 'pending' ||
        normalized == 'sent' ||
        normalized == 'overdue' ||
        normalized == 'late';
  }

  bool _canShowNudge(String status) {
    final normalized = status.toLowerCase();
    return normalized == 'pending' ||
        normalized == 'sent' ||
        normalized == 'member_marked' ||
        normalized == 'overdue' ||
        normalized == 'late';
  }

  bool _canShowConfirm(String status) {
    return status.toLowerCase() == 'member_marked';
  }

  bool _canShowRetry(String status) {
    final normalized = status.toLowerCase();
    return normalized == 'failed' || normalized == 'error';
  }

  String? _formatDueDateLabel(String? raw) {
    if (raw == null || raw.isEmpty) return null;
    try {
      final dt = DateTime.parse(raw).toLocal();
      const months = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec',
      ];
      final month = months[dt.month - 1];
      final day = dt.day.toString();
      return '$month $day, ${dt.year}';
    } catch (_) {
      return raw;
    }
  }
}
