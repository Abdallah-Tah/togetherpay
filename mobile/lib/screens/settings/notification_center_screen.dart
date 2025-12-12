import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../services/notification_center_service.dart';
import 'notification_preferences_screen.dart';

class NotificationCenterScreen extends StatefulWidget {
  const NotificationCenterScreen({super.key});

  @override
  State<NotificationCenterScreen> createState() =>
      _NotificationCenterScreenState();
}

class _NotificationCenterScreenState extends State<NotificationCenterScreen> {
  bool _loading = true;
  List<NotificationItem> _items = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    await NotificationCenterService.instance.load();
    setState(() {
      _items = NotificationCenterService.instance.items;
      _loading = false;
    });
  }

  Future<void> _markAllRead() async {
    await NotificationCenterService.instance.markAllRead();
    await _load();
  }

  Future<void> _clearAll() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Clear all notifications?'),
        content: const Text(
          'This removes all notifications from your device.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(
              backgroundColor: Colors.red,
            ),
            child: const Text('Clear all'),
          ),
        ],
      ),
    );
    if (confirmed == true) {
      await NotificationCenterService.instance.clearAll();
      await _load();
    }
  }

  Future<void> _handleItemTap(NotificationItem item) async {
    await NotificationCenterService.instance.markRead(item.id);
    await _load();
  }

  Future<void> _handleDismiss(String id) async {
    await NotificationCenterService.instance.delete(id);
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final unread = NotificationCenterService.instance.unreadCount;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          IconButton(
            tooltip: 'Preferences',
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => const NotificationPreferencesScreen(),
                ),
              );
            },
            icon: const Icon(Icons.tune_rounded),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: _items.isEmpty
                  ? _buildEmptyState(theme)
                  : ListView(
                      padding: const EdgeInsets.all(16),
                      children: [
                        Row(
                          children: [
                            Text(
                              unread > 0 ? '$unread unread' : 'All caught up',
                              style: theme.textTheme.titleMedium
                                  ?.copyWith(fontWeight: FontWeight.bold),
                            ),
                            const Spacer(),
                            if (unread > 0)
                              TextButton(
                                onPressed: _markAllRead,
                                child: const Text('Mark all read'),
                              ),
                            const SizedBox(width: 8),
                            TextButton(
                              onPressed: _items.isEmpty ? null : _clearAll,
                              style: TextButton.styleFrom(
                                foregroundColor: Colors.red,
                              ),
                              child: const Text('Clear all'),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        ..._buildGroupedList(theme),
                      ],
                    ),
            ),
    );
  }

  Widget _buildEmptyState(ThemeData theme) {
    return ListView(
      children: [
        const SizedBox(height: 80),
        Icon(
          Icons.notifications_none_rounded,
          size: 72,
          color: theme.colorScheme.primary.withOpacity(0.4),
        ),
        const SizedBox(height: 16),
        Center(
          child: Text(
            'You’re all caught up',
            style: theme.textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
        const SizedBox(height: 8),
        Center(
          child: Text(
            'New notifications will appear here.',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurface.withOpacity(0.65),
            ),
          ),
        ),
        const SizedBox(height: 80),
      ],
    );
  }

  List<Widget> _buildGroupedList(ThemeData theme) {
    final now = DateTime.now();
    String sectionLabel(DateTime ts) {
      final delta = now.difference(ts).inDays;
      if (delta == 0) return 'Today';
      if (delta == 1) return 'Yesterday';
      if (delta < 7) return 'This week';
      return DateFormat.MMMd().format(ts);
    }

    final Map<String, List<NotificationItem>> sections = {};
    for (final item in _items) {
      final label = sectionLabel(item.timestamp);
      sections.putIfAbsent(label, () => []).add(item);
    }

    final List<Widget> widgets = [];
    sections.forEach((label, items) {
      widgets.add(Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Text(
          label,
          style: theme.textTheme.titleSmall?.copyWith(
            color: theme.colorScheme.onSurface.withOpacity(0.6),
            fontWeight: FontWeight.w600,
          ),
        ),
      ));
      widgets.addAll(items.map((item) => _buildTile(theme, item)));
    });

    return widgets;
  }

  Widget _buildTile(ThemeData theme, NotificationItem item) {
    final time = DateFormat.jm().format(item.timestamp);
    final icon = _iconForCategory(item.category);

    return Dismissible(
      key: ValueKey(item.id),
      direction: DismissDirection.endToStart,
      background: Container(
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.symmetric(horizontal: 20),
        decoration: BoxDecoration(
          color: Colors.red.shade100,
          borderRadius: BorderRadius.circular(14),
        ),
        child: const Icon(Icons.delete_outline, color: Colors.red),
      ),
      onDismissed: (_) => _handleDismiss(item.id),
      child: Card(
        margin: const EdgeInsets.only(bottom: 12),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        child: ListTile(
          leading: Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: theme.colorScheme.primary.withOpacity(0.08),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: theme.colorScheme.primary),
          ),
          title: Text(
            item.title,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          subtitle: Padding(
            padding: const EdgeInsets.only(top: 4),
            child: Text(
              item.body,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          trailing: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              if (!item.read)
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.primary,
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    'NEW',
                    style: TextStyle(
                      color: theme.colorScheme.onPrimary,
                      fontWeight: FontWeight.bold,
                      fontSize: 11,
                      letterSpacing: 0.4,
                    ),
                  ),
                ),
              const SizedBox(height: 6),
              Text(
                time,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurface.withOpacity(0.55),
                ),
              ),
            ],
          ),
          onTap: () => _handleItemTap(item),
        ),
      ),
    );
  }

  IconData _iconForCategory(String category) {
    switch (category.toLowerCase()) {
      case 'payment':
        return Icons.payments_outlined;
      case 'invite':
        return Icons.mail_outline;
      case 'system':
        return Icons.shield_outlined;
      default:
        return Icons.notifications_outlined;
    }
  }
}
