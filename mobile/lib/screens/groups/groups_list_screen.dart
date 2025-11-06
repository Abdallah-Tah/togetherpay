import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../services/api_service.dart';
import '../../utils/theme.dart';
import '../../widgets/app_background.dart';
import '../../widgets/group_card.dart';
import 'group_details_screen.dart';
import 'group_form_sheet.dart';

class GroupsListScreen extends StatefulWidget {
  const GroupsListScreen({super.key});

  @override
  State<GroupsListScreen> createState() => _GroupsListScreenState();
}

class _GroupsListScreenState extends State<GroupsListScreen> {
  final Dio _dio = ApiService().dio;

  List<Map<String, dynamic>> _groups = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadGroups();
  }

  Future<void> _loadGroups() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await _dio.get('/groups');
      final data = response.data;

      final List<Map<String, dynamic>> groups = [];

      if (data is Map<String, dynamic>) {
        final owned = data['owned_groups'];
        final member = data['member_groups'];

        if (owned is List) {
          for (final group in owned) {
            groups.add(_normalizeGroup(group, isOwner: true));
          }
        }

        if (member is List) {
          for (final group in member) {
            groups.add(_normalizeGroup(group, isOwner: false));
          }
        }

        if (groups.isEmpty && data['data'] is List) {
          for (final group in data['data'] as List<dynamic>) {
            groups.add(_normalizeGroup(group, isOwner: false));
          }
        }
      } else if (data is List) {
        for (final group in data) {
          groups.add(_normalizeGroup(group, isOwner: false));
        }
      } else {
        throw Exception('Unexpected response format');
      }

      groups.sort((a, b) {
        final nameA = (a['name'] as String?) ?? '';
        final nameB = (b['name'] as String?) ?? '';
        return nameA.toLowerCase().compareTo(nameB.toLowerCase());
      });

      if (!mounted) return;

      setState(() {
        _groups = groups;
        _loading = false;
      });

      if (Env.debug) {
        debugPrint('Loaded ${groups.length} groups');
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Error loading groups: ${e.response?.data}');
      }

      if (!mounted) return;

      setState(() {
        _error =
            e.response?.data?['message'] as String? ?? 'Failed to load groups';
        _loading = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected error loading groups: $e');
      }

      if (!mounted) return;

      setState(() {
        _error = 'An unexpected error occurred';
        _loading = false;
      });
    }
  }

  Map<String, dynamic> _normalizeGroup(
    dynamic group, {
    required bool isOwner,
  }) {
    final map = Map<String, dynamic>.from(group as Map);

    map['is_owner'] = isOwner || (map['is_owner'] as bool? ?? false);

    if (map['member_count'] == null) {
      final members = map['members'];
      if (members is List) {
        map['member_count'] = members.length;
      }
    }

    if (map['pending_payments_count'] == null) {
      map['pending_payments_count'] = _countPendingRequests(map);
    }

    return map;
  }

  int _countPendingRequests(Map<String, dynamic> group) {
    int count = 0;

    void handleList(List<dynamic> list) {
      for (final item in list) {
        if (item is Map<String, dynamic>) {
          final status = item['status'] as String?;
          if (_isPendingStatus(status)) {
            count++;
          }
        }
      }
    }

    final paymentRequests = group['payment_requests'];
    if (paymentRequests is List) {
      handleList(paymentRequests);
    } else {
      final cycles = group['cycles'];
      if (cycles is List) {
        for (final cycle in cycles) {
          if (cycle is Map<String, dynamic>) {
            final cycleRequests = cycle['payment_requests'];
            if (cycleRequests is List) {
              handleList(cycleRequests);
            }
          }
        }
      }
    }

    return count;
  }

  bool _isPendingStatus(String? status) {
    switch ((status ?? '').toLowerCase()) {
      case 'pending':
      case 'sent':
      case 'member_marked':
      case 'collecting':
      case 'overdue':
      case 'late':
        return true;
      default:
        return false;
    }
  }

  Future<void> _handleRefresh() async {
    await _loadGroups();
  }

  void _navigateToGroupDetails(Map<String, dynamic> group) {
    final groupId = group['id'] as int?;

    if (groupId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Invalid group ID')),
      );
      return;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => GroupDetailsScreen(
          groupId: groupId,
          initialGroup: group,
        ),
      ),
    ).then((_) => _loadGroups());
  }

  Future<void> _showCreateGroupSheet() async {
    final result = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      builder: (_) => const GroupFormSheet(),
    );

    if (result != null && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Group "${result['name'] ?? 'New Group'}" created'),
        ),
      );
      await _loadGroups();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Groups'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _loading ? null : _handleRefresh,
            icon: const Icon(Icons.refresh),
          ),
          IconButton(
            tooltip: 'Create group',
            onPressed: _loading ? null : _showCreateGroupSheet,
            icon: const Icon(Icons.add),
          ),
          const SizedBox(width: 8),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _loading ? null : _showCreateGroupSheet,
        icon: const Icon(Icons.group_add_outlined),
        label: const Text('New group'),
      ),
      body: AppBackground(
        child: SafeArea(
          bottom: false,
          child: AnimatedSwitcher(
            duration: const Duration(milliseconds: 250),
            child: _buildBody(),
          ),
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading && _groups.isEmpty) {
      return _buildInitialLoader();
    }

    if (_error != null && _groups.isEmpty) {
      return _buildErrorState();
    }

    if (_groups.isEmpty) {
      return _buildEmptyState();
    }

    final totalPending = _groups.fold<int>(
      0,
      (previousValue, element) =>
          previousValue + (element['pending_payments_count'] as int? ?? 0),
    );
    final totalMembers = _groups.fold<int>(
      0,
      (previousValue, element) =>
          previousValue + (element['member_count'] as int? ?? 0),
    );
    final ownedGroups =
        _groups.where((g) => g['is_owner'] as bool? ?? false).length;

    final header = _SummaryHeader(
      totalGroups: _groups.length,
      ownedGroups: ownedGroups,
      totalMembers: totalMembers,
      totalPending: totalPending,
    );

    final list = SliverList(
      delegate: SliverChildBuilderDelegate(
        (context, index) {
          final group = _groups[index];
          return TweenAnimationBuilder<double>(
            tween: Tween(begin: 0.0, end: 1.0),
            duration: Duration(milliseconds: 200 + (index * 60)),
            curve: Curves.easeOut,
            builder: (context, value, child) => Transform.translate(
              offset: Offset(0, 24 * (1 - value)),
              child: Opacity(opacity: value, child: child),
            ),
            child: GroupCard(
              group: group,
              onTap: () => _navigateToGroupDetails(group),
            ),
          );
        },
        childCount: _groups.length,
      ),
    );

    final content = RefreshIndicator(
      onRefresh: _handleRefresh,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverToBoxAdapter(child: header),
          SliverPadding(
            padding: const EdgeInsets.only(bottom: 120),
            sliver: list,
          ),
        ],
      ),
    );

    if (_loading) {
      return Stack(
        children: [
          content,
          const Positioned(
            left: 0,
            right: 0,
            child: LinearProgressIndicator(minHeight: 2),
          ),
        ],
      );
    }

    return content;
  }

  Widget _buildInitialLoader() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: const [
          SizedBox(
            width: 48,
            height: 48,
            child: CircularProgressIndicator(),
          ),
          SizedBox(height: 16),
          Text('Loading your groups…'),
        ],
      ),
    );
  }

  Widget _buildErrorState() {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.error_outline,
              size: 48, color: Theme.of(context).colorScheme.error),
          const SizedBox(height: 12),
          Text(
            _error ?? 'Unable to load groups',
            style: Theme.of(context).textTheme.titleMedium,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: _loadGroups,
            icon: const Icon(Icons.refresh),
            label: const Text('Try again'),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 96,
            height: 96,
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.primaryContainer,
              shape: BoxShape.circle,
            ),
            child: Icon(
              Icons.groups_outlined,
              color: Theme.of(context).colorScheme.primary,
              size: 40,
            ),
          ),
          const SizedBox(height: 24),
          Text(
            'Create your first group',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 12),
          Text(
            'Invite members, track payments and settle balances from one place.',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Theme.of(context)
                      .colorScheme
                      .onSurface
                      .withValues(alpha: 0.6),
                ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 24),
          FilledButton.icon(
            onPressed: _loading ? null : _showCreateGroupSheet,
            icon: const Icon(Icons.add),
            label: const Text('Start a group'),
          ),
        ],
      ),
    );
  }
}

class _SummaryHeader extends StatelessWidget {
  final int totalGroups;
  final int ownedGroups;
  final int totalMembers;
  final int totalPending;

  const _SummaryHeader({
    required this.totalGroups,
    required this.ownedGroups,
    required this.totalMembers,
    required this.totalPending,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final stats = [
      _HighlightStat(label: 'Groups', value: totalGroups.toString()),
      _HighlightStat(label: 'Owned', value: ownedGroups.toString()),
      _HighlightStat(label: 'Members', value: totalMembers.toString()),
      _HighlightStat(label: 'Pending', value: totalPending.toString()),
    ];

    return Padding(
      padding: const EdgeInsets.fromLTRB(24, 16, 24, 12),
      child: Container(
        decoration: BoxDecoration(
          color: theme.colorScheme.surface,
          borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
          border: Border.all(color: theme.dividerColor.withValues(alpha: 0.6)),
          boxShadow: AppTheme.cardShadow,
        ),
        padding: const EdgeInsets.fromLTRB(24, 24, 24, 20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Group overview',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Stay on top of contributions and pending balances.',
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurface.withValues(alpha: 0.6),
              ),
            ),
            const SizedBox(height: 20),
            Row(
              children: [
                for (int i = 0; i < stats.length; i++) ...[
                  Expanded(child: stats[i]),
                  if (i != stats.length - 1)
                    Container(
                      height: 38,
                      width: 1,
                      margin: const EdgeInsets.symmetric(horizontal: 12),
                      color: theme.dividerColor.withValues(alpha: 0.6),
                    ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _HighlightStat extends StatelessWidget {
  final String label;
  final String value;

  const _HighlightStat({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          value,
          style: theme.textTheme.headlineSmall?.copyWith(
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.onSurface.withValues(alpha: 0.6),
            letterSpacing: 0.2,
          ),
        ),
      ],
    );
  }
}
