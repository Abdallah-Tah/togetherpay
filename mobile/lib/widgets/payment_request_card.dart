import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../env.dart';
import '../utils/theme.dart';

/// Reusable payment request card widget with animations
///
/// Displays payment request details with optional actionable buttons for
/// paying, nudging, or confirming payments depending on status.
class PaymentRequestCard extends StatefulWidget {
  final Map<String, dynamic> paymentRequest;
  final VoidCallback? onTap;
  final VoidCallback? onPay;
  final VoidCallback? onNudge;
  final VoidCallback? onConfirm;
  final VoidCallback? onRetry;
  final VoidCallback? onCancel;
  final bool isProcessingNudge;
  final bool isProcessingConfirm;
  final bool isProcessingRetry;
  final bool isProcessingCancel;

  const PaymentRequestCard({
    super.key,
    required this.paymentRequest,
    this.onTap,
    this.onPay,
    this.onNudge,
    this.onConfirm,
    this.onRetry,
    this.onCancel,
    this.isProcessingNudge = false,
    this.isProcessingConfirm = false,
    this.isProcessingRetry = false,
    this.isProcessingCancel = false,
  });

  @override
  State<PaymentRequestCard> createState() => _PaymentRequestCardState();
}

class _PaymentRequestCardState extends State<PaymentRequestCard>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scaleAnimation;
  bool _isPressed = false;
  bool _isLaunching = false;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 100),
    );

    _scaleAnimation = Tween<double>(begin: 1.0, end: 0.98).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _handleTapDown(TapDownDetails details) {
    if (widget.onTap != null) {
      setState(() => _isPressed = true);
      _controller.forward();
    }
  }

  void _handleTapUp(TapUpDetails details) {
    setState(() => _isPressed = false);
    _controller.reverse();
  }

  void _handleTapCancel() {
    setState(() => _isPressed = false);
    _controller.reverse();
  }

  Future<void> _launchWallet() async {
    if (_isLaunching) return;

    setState(() {
      _isLaunching = true;
    });

    try {
      final cashAppUrl = _buildCashAppUrl(widget.paymentRequest);

      // Get deep_link from payment request data (same as pay_screen.dart button)
      final deepLinkUri = widget.paymentRequest['deep_link'] as String?;
      final webFallback = widget.paymentRequest['web_fallback_link'] as String?;
      final url = cashAppUrl ?? deepLinkUri ?? webFallback;

      if (url != null) {
        final uri = Uri.tryParse(url);
        if (uri != null) {
          // Use in-app browser so the Cash App redirect stays inside the app.
          var launched = false;
          try {
            launched = await launchUrl(
              uri,
              mode: LaunchMode.inAppBrowserView,
            );
          } catch (_) {
            launched = false;
          }

          if (!launched) {
            await launchUrl(
              uri,
              mode: LaunchMode.platformDefault,
            );
          }
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Payment link not available')),
          );
        }
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Error launching wallet: $e');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Failed to launch wallet app')),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isLaunching = false;
        });
      }
    }
  }

  String? _buildCashAppUrl(Map<String, dynamic> pr) {
    final owner = pr['cycle']?['group']?['owner'] ?? pr['group']?['owner'];
    final handle = owner?['cash_tag'] as String? ??
        owner?['cashapp_cashtag'] as String? ??
        pr['member']?['cashapp_cashtag'] as String? ??
        pr['member']?['cash_tag'] as String? ??
        pr['member']?['cashapp_tag'] as String?;
    if (handle == null || handle.trim().isEmpty) return null;

    final cents = pr['amount_cents'] as int? ?? 0;
    if (cents <= 0) return null;

    var cashtag = handle.trim();
    cashtag = cashtag.startsWith('\$') ? cashtag.substring(1) : cashtag;
    final amount = (cents / 100.0).toStringAsFixed(2);

    return 'https://cash.app/\$$cashtag/$amount';
  }

  @override
  Widget build(BuildContext context) {
    final amount =
        widget.paymentRequest['amount_display'] as String? ?? '\$0.00';
    final status = widget.paymentRequest['status'] as String? ?? 'pending';
    final groupName =
        widget.paymentRequest['group']?['name'] as String? ?? 'Payment';
    final dueDate = widget.paymentRequest['due_date'] as String?;
    final code = widget.paymentRequest['code'] as String? ?? '';

    // Determine status color and icon
    Color statusColor;
    IconData statusIcon;
    String statusLabel;
    LinearGradient? statusGradient;

    switch (status.toLowerCase()) {
      case 'paid':
      case 'confirmed':
      case 'owner_confirmed':
        statusColor = Colors.green;
        statusIcon = Icons.check_circle_rounded;
        statusLabel = 'Paid';
        statusGradient = AppTheme.successGradient;
        break;
      case 'member_marked':
        statusColor = Colors.amber.shade800;
        statusIcon = Icons.hourglass_bottom_rounded;
        statusLabel = 'Awaiting confirm';
        break;
      case 'failed':
      case 'error':
        statusColor = Colors.red.shade700;
        statusIcon = Icons.error_rounded;
        statusLabel = 'Failed';
        statusGradient = AppTheme.errorGradient;
        break;
      case 'overdue':
      case 'late':
        statusColor = Colors.red;
        statusIcon = Icons.warning_rounded;
        statusLabel = 'Overdue';
        statusGradient = AppTheme.errorGradient;
        break;
      case 'sent':
        statusColor = Colors.blue;
        statusIcon = Icons.send_rounded;
        statusLabel = 'Sent';
        break;
      case 'pending':
      default:
        statusColor = Colors.orange;
        statusIcon = Icons.pending_rounded;
        statusLabel = 'Pending';
        statusGradient = AppTheme.warningGradient;
        break;
    }

    final dueDateLabel = _formatDueDateLabel(dueDate);
    final showPayButton = widget.onPay != null && _canShowPayButton(status);
    final showNudgeButton = widget.onNudge != null && _canShowNudge(status);
    final showConfirmButton =
        widget.onConfirm != null && _canShowConfirm(status);
    final showRetryButton = widget.onRetry != null && _canShowRetry(status);
    final showCancelButton = widget.onCancel != null && _canShowCancel(status);

    return ScaleTransition(
      scale: _scaleAnimation,
      child: Card(
        margin: const EdgeInsets.symmetric(
          horizontal: AppTheme.spaceMedium,
          vertical: AppTheme.spaceSmall,
        ),
        elevation: _isPressed ? 2 : 0,
        shadowColor: Colors.black.withValues(alpha: 0.1),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: widget.onTap,
          onTapDown: _handleTapDown,
          onTapUp: _handleTapUp,
          onTapCancel: _handleTapCancel,
          child: Container(
            padding: const EdgeInsets.all(AppTheme.spaceMedium),
            decoration: BoxDecoration(
              border: Border(
                left: BorderSide(
                  color: statusColor,
                  width: 4,
                ),
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header row with amount and status
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    // Amount with icon
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            gradient: AppTheme.primaryGradient,
                            borderRadius:
                                BorderRadius.circular(AppTheme.radiusSmall),
                          ),
                          child: const Icon(
                            Icons.attach_money_rounded,
                            color: Colors.white,
                            size: 20,
                          ),
                        ),
                        const SizedBox(width: AppTheme.spaceMedium),
                        Text(
                          amount,
                          style: Theme.of(context)
                              .textTheme
                              .headlineSmall
                              ?.copyWith(
                                fontWeight: FontWeight.bold,
                                color: Theme.of(context).colorScheme.primary,
                              ),
                        ),
                      ],
                    ),

                    // Status badge
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        gradient: statusGradient,
                        color: statusGradient == null
                            ? statusColor.withValues(alpha: 0.1)
                            : null,
                        borderRadius:
                            BorderRadius.circular(AppTheme.radiusMedium),
                        border: statusGradient == null
                            ? Border.all(
                                color: statusColor.withValues(alpha: 0.3),
                                width: 1,
                              )
                            : null,
                        boxShadow: statusGradient != null
                            ? [
                                BoxShadow(
                                  color: statusColor.withValues(alpha: 0.3),
                                  blurRadius: 8,
                                  offset: const Offset(0, 2),
                                ),
                              ]
                            : null,
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            statusIcon,
                            size: 16,
                            color: statusGradient != null
                                ? Colors.white
                                : statusColor,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            statusLabel,
                            style: TextStyle(
                              color: statusGradient != null
                                  ? Colors.white
                                  : statusColor,
                              fontWeight: FontWeight.bold,
                              fontSize: 12,
                              letterSpacing: 0.5,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 8),

                // Group name
                const SizedBox(height: AppTheme.spaceMedium),
                Row(
                  children: [
                    Icon(
                      Icons.group_outlined,
                      size: 18,
                      color: Theme.of(context)
                          .colorScheme
                          .onSurface
                          .withValues(alpha: 0.6),
                    ),
                    const SizedBox(width: AppTheme.spaceSmall),
                    Expanded(
                      child: Text(
                        groupName,
                        style:
                            Theme.of(context).textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.w600,
                                ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),

                // Due date
                if (dueDateLabel != null) ...[
                  const SizedBox(height: AppTheme.spaceSmall),
                  Row(
                    children: [
                      Icon(
                        Icons.calendar_today_rounded,
                        size: 16,
                        color: Theme.of(context)
                            .colorScheme
                            .onSurface
                            .withValues(alpha: 0.6),
                      ),
                      const SizedBox(width: AppTheme.spaceSmall),
                      Text(
                        'Due: $dueDateLabel',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Theme.of(context)
                                  .colorScheme
                                  .onSurface
                                  .withValues(alpha: 0.6),
                            ),
                      ),
                    ],
                  ),
                ],

                const SizedBox(height: AppTheme.spaceMedium),

                // Payment code
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color:
                        Theme.of(context).colorScheme.surfaceContainerHighest,
                    borderRadius: BorderRadius.circular(AppTheme.radiusSmall),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        Icons.qr_code_rounded,
                        size: 16,
                        color: Theme.of(context)
                            .colorScheme
                            .onSurface
                            .withValues(alpha: 0.5),
                      ),
                      const SizedBox(width: AppTheme.spaceSmall),
                      Expanded(
                        child: Text(
                          code,
                          style:
                              Theme.of(context).textTheme.bodySmall?.copyWith(
                                    fontFamily: 'monospace',
                                    color: Theme.of(context)
                                        .colorScheme
                                        .onSurface
                                        .withValues(alpha: 0.5),
                                  ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ),

                // Pay button
                if (showPayButton) ...[
                  const SizedBox(height: AppTheme.spaceMedium),
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: FilledButton.icon(
                      onPressed: widget.onPay ?? _launchWallet,
                      icon: _isLaunching
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Icon(Icons.payment_rounded),
                      label: Text(_isLaunching ? 'Opening...' : 'Pay Now'),
                      style: FilledButton.styleFrom(
                        backgroundColor: Theme.of(context).colorScheme.primary,
                      ),
                    ),
                  ),
                ],

                // Action buttons
                if (showNudgeButton ||
                    showConfirmButton ||
                    showRetryButton ||
                    showCancelButton) ...[
                  const SizedBox(height: AppTheme.spaceMedium),
                  Row(
                    children: [
                      if (showRetryButton)
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: widget.isProcessingRetry
                                ? null
                                : widget.onRetry,
                            icon: widget.isProcessingRetry
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                        strokeWidth: 2),
                                  )
                                : const Icon(Icons.refresh_rounded),
                            label: const Text('Retry'),
                            style: OutlinedButton.styleFrom(
                              foregroundColor: Colors.orange,
                              side: const BorderSide(color: Colors.orange),
                            ),
                          ),
                        ),
                      if (showNudgeButton) ...[
                        if (showRetryButton)
                          const SizedBox(width: AppTheme.spaceSmall),
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: widget.isProcessingNudge
                                ? null
                                : widget.onNudge,
                            icon: widget.isProcessingNudge
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                        strokeWidth: 2),
                                  )
                                : const Icon(
                                    Icons.notifications_active_rounded),
                            label: const Text('Remind'),
                          ),
                        ),
                      ],
                      if (showConfirmButton) ...[
                        if (showNudgeButton || showRetryButton)
                          const SizedBox(width: AppTheme.spaceSmall),
                        Expanded(
                          child: FilledButton.icon(
                            onPressed: widget.isProcessingConfirm
                                ? null
                                : widget.onConfirm,
                            icon: widget.isProcessingConfirm
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Icon(Icons.verified_rounded),
                            label: const Text('Confirm'),
                            style: FilledButton.styleFrom(
                              backgroundColor: Colors.green,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                  // Cancel button (separate row for better UX)
                  if (showCancelButton) ...[
                    const SizedBox(height: AppTheme.spaceSmall),
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        onPressed:
                            widget.isProcessingCancel ? null : widget.onCancel,
                        icon: widget.isProcessingCancel
                            ? const SizedBox(
                                width: 16,
                                height: 16,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            : const Icon(Icons.cancel_outlined),
                        label: const Text('Cancel Request'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.red,
                          side: const BorderSide(color: Colors.red),
                        ),
                      ),
                    ),
                  ],
                ],
              ],
            ),
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

  bool _canShowCancel(String status) {
    final normalized = status.toLowerCase();
    // Can cancel if pending, sent, or member_marked (before confirmed)
    return normalized == 'pending' ||
        normalized == 'sent' ||
        normalized == 'member_marked';
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
