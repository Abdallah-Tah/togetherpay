import 'package:flutter/material.dart';

/// Reusable payment request card widget
///
/// Displays payment request details with status badge and action button
class PaymentRequestCard extends StatelessWidget {
  final Map<String, dynamic> paymentRequest;
  final VoidCallback? onTap;
  final VoidCallback? onPay;

  const PaymentRequestCard({
    super.key,
    required this.paymentRequest,
    this.onTap,
    this.onPay,
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
      case 'member_marked':
      case 'confirmed':
        statusColor = Colors.green;
        statusIcon = Icons.check_circle;
        statusLabel = 'Paid';
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
              if (dueDate != null) ...[
                Row(
                  children: [
                    Icon(
                      Icons.calendar_today,
                      size: 14,
                      color: Colors.grey[600],
                    ),
                    const SizedBox(width: 4),
                    Text(
                      'Due: $dueDate',
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

                  // Pay button for pending/sent payments
                  if (onPay != null &&
                      (status == 'pending' || status == 'sent')) ...[
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
            ],
          ),
        ),
      ),
    );
  }
}
