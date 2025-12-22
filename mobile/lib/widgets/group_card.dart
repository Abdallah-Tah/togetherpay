import 'package:flutter/material.dart';
import '../utils/theme.dart';

/// Reusable group card widget for displaying group information
///
/// Shows group name, member count, and pending payment count with animations
class GroupCard extends StatefulWidget {
  final Map<String, dynamic> group;
  final VoidCallback? onTap;

  const GroupCard({
    super.key,
    required this.group,
    this.onTap,
  });

  @override
  State<GroupCard> createState() => _GroupCardState();
}

class _GroupCardState extends State<GroupCard>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scaleAnimation;

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
    _controller.forward();
  }

  void _handleTapUp(TapUpDetails details) {
    _controller.reverse();
  }

  void _handleTapCancel() {
    _controller.reverse();
  }

  @override
  Widget build(BuildContext context) {
    final groupName = widget.group['name'] as String? ?? 'Unnamed Group';
    final groupId = widget.group['id'];
    final memberCount = widget.group['member_count'] as int? ?? 0;
    final pendingCount = widget.group['pending_payments_count'] as int? ?? 0;
    final description = widget.group['description'] as String?;
    final isOwner = widget.group['is_owner'] as bool? ?? false;

    final colorScheme = Theme.of(context).colorScheme;
    final onSurface = colorScheme.onSurface;

    return ScaleTransition(
      scale: _scaleAnimation,
      child: Hero(
        tag: 'group_card_$groupId',
        child: Card(
          margin: EdgeInsets.zero,
          clipBehavior: Clip.antiAlias,
          child: InkWell(
            onTap: widget.onTap,
            onTapDown: _handleTapDown,
            onTapUp: _handleTapUp,
            onTapCancel: _handleTapCancel,
            child: Container(
              padding: const EdgeInsets.all(AppTheme.spaceMedium),
              decoration: BoxDecoration(
                gradient: isOwner
                    ? LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [
                          colorScheme.primary.withValues(alpha: 0.10),
                          Colors.transparent,
                        ],
                      )
                    : null,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 52,
                        height: 52,
                        decoration: BoxDecoration(
                          gradient: AppTheme.primaryGradient,
                          borderRadius:
                              BorderRadius.circular(AppTheme.radiusMedium),
                          boxShadow: [
                            BoxShadow(
                              color: colorScheme.shadow.withValues(alpha: 0.10),
                              blurRadius: 10,
                              offset: const Offset(0, 6),
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.groups_rounded,
                          color: Colors.white,
                          size: 26,
                        ),
                      ),
                      const SizedBox(width: AppTheme.spaceMedium),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              groupName,
                              style: Theme.of(context)
                                  .textTheme
                                  .titleLarge
                                  ?.copyWith(
                                    fontWeight: FontWeight.bold,
                                  ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            if (isOwner) ...[
                              const SizedBox(height: 4),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: colorScheme.primary
                                      .withValues(alpha: 0.10),
                                  borderRadius: BorderRadius.circular(
                                    AppTheme.radiusSmall,
                                  ),
                                  border: Border.all(
                                    color: colorScheme.primary
                                        .withValues(alpha: 0.18),
                                  ),
                                ),
                                child: Text(
                                  'OWNER',
                                  style: Theme.of(context)
                                      .textTheme
                                      .labelSmall
                                      ?.copyWith(
                                        fontWeight: FontWeight.bold,
                                        color: colorScheme.primary,
                                        letterSpacing: 0.5,
                                      ),
                                ),
                              ),
                            ] else ...[
                              const SizedBox(height: 4),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: colorScheme.surfaceContainerHighest,
                                  borderRadius: BorderRadius.circular(
                                    AppTheme.radiusSmall,
                                  ),
                                  border: Border.all(
                                    color: colorScheme.outlineVariant
                                        .withValues(alpha: 0.5),
                                  ),
                                ),
                                child: Text(
                                  'MEMBER',
                                  style: Theme.of(context)
                                      .textTheme
                                      .labelSmall
                                      ?.copyWith(
                                        fontWeight: FontWeight.bold,
                                        color: onSurface.withValues(alpha: 0.7),
                                        letterSpacing: 0.5,
                                      ),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      if (pendingCount > 0)
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 6,
                          ),
                          decoration: BoxDecoration(
                            color: colorScheme.error.withValues(alpha: 0.12),
                            borderRadius:
                                BorderRadius.circular(AppTheme.radiusMedium),
                            border: Border.all(
                              color: colorScheme.error.withValues(alpha: 0.18),
                            ),
                          ),
                          child: Text(
                            '$pendingCount',
                            style: Theme.of(context)
                                .textTheme
                                .labelLarge
                                ?.copyWith(
                                  fontWeight: FontWeight.bold,
                                  color: colorScheme.error,
                                ),
                          ),
                        ),
                    ],
                  ),
                  if (description != null && description.isNotEmpty) ...[
                    const SizedBox(height: AppTheme.spaceMedium),
                    Text(
                      description,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: onSurface.withValues(alpha: 0.70),
                          ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                  const SizedBox(height: AppTheme.spaceMedium),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: colorScheme.surfaceContainerHighest
                              .withValues(alpha: 0.55),
                          borderRadius:
                              BorderRadius.circular(AppTheme.radiusMedium),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                              Icons.people_outline_rounded,
                              size: 18,
                              color: colorScheme.primary,
                            ),
                            const SizedBox(width: 6),
                            Text(
                              '$memberCount ${memberCount == 1 ? 'member' : 'members'}',
                              style: Theme.of(context)
                                  .textTheme
                                  .bodySmall
                                  ?.copyWith(
                                    fontWeight: FontWeight.w600,
                                    color: onSurface.withValues(alpha: 0.75),
                                  ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: AppTheme.spaceSmall),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: pendingCount > 0
                              ? colorScheme.error.withValues(alpha: 0.10)
                              : colorScheme.primary.withValues(alpha: 0.10),
                          borderRadius:
                              BorderRadius.circular(AppTheme.radiusMedium),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                              pendingCount > 0
                                  ? Icons.pending_outlined
                                  : Icons.check_circle_outline_rounded,
                              size: 18,
                              color: pendingCount > 0
                                  ? colorScheme.error
                                  : colorScheme.primary,
                            ),
                            const SizedBox(width: 6),
                            Text(
                              pendingCount > 0
                                  ? '$pendingCount pending'
                                  : 'All paid',
                              style: Theme.of(context)
                                  .textTheme
                                  .bodySmall
                                  ?.copyWith(
                                    fontWeight: FontWeight.w600,
                                    color: pendingCount > 0
                                        ? colorScheme.error
                                        : colorScheme.primary,
                                  ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
