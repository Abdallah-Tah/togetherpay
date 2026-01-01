import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'dart:ui' as ui;
import 'package:flutter/scheduler.dart';

import '../../env.dart';
import '../../services/api_service.dart';
import '../../widgets/group_card.dart';
import '../../widgets/skeleton_loader.dart';
import 'group_details_screen.dart';
import 'group_form_sheet.dart';
import '../../utils/animations.dart';
import '../../utils/theme.dart';

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
        final ownerId = (map['owner'] as Map?)?['id'] as int?;
        final activeMembers = members.where((member) {
          if (member is! Map) return false;
          final status = (member['status'] as String?)?.toLowerCase() ?? 'active';
          if (status != 'active') return false;
          final userId = (member['user'] as Map?)?['id'] as int?;
          if (ownerId != null && userId == ownerId) return false;
          return true;
        }).toList();
        map['member_count'] = activeMembers.length;
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
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      extendBodyBehindAppBar: isDark,
      appBar: AppBar(
        title: const Text('My Groups'),
        backgroundColor:
            isDark ? Colors.transparent : Theme.of(context).colorScheme.surface,
        elevation: isDark ? 0 : null,
        flexibleSpace: isDark
            ? ClipRect(
                child: BackdropFilter(
                  filter: ui.ImageFilter.blur(sigmaX: 10, sigmaY: 10),
                  child: Container(
                    color: Colors.black.withValues(alpha: 0.25),
                  ),
                ),
              )
            : null,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
            onPressed: _loading ? null : _handleRefresh,
          ),
          IconButton(
            icon: const Icon(Icons.add),
            tooltip: 'Create group',
            onPressed: _loading ? null : _showCreateGroupSheet,
          ),
        ],
      ),
      body: Container(
        decoration: BoxDecoration(
          color: isDark ? null : Theme.of(context).scaffoldBackgroundColor,
          gradient: isDark
              ? const LinearGradient(
                  colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                )
              : null,
        ),
        child: SafeArea(child: _buildBody()),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _loading ? null : _showCreateGroupSheet,
        icon: const Icon(Icons.group_add_outlined),
        label: const Text('New Group'),
        backgroundColor: AppTheme.primary,
        foregroundColor: Colors.white,
        elevation: 4,
      ),
    );
  }

  Widget _buildBody() {
    if (_loading && _groups.isEmpty) {
      return ListView.builder(
        padding: const EdgeInsets.only(top: 8, bottom: 96),
        itemCount: 5,
        itemBuilder: (context, index) => const SkeletonGroupCard(),
      );
    }

    if (_error != null && _groups.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.error_outline,
                size: 64,
                color: Colors.red.shade300,
              ),
              const SizedBox(height: 16),
              Text(
                _error!,
                style: Theme.of(context).textTheme.bodyLarge,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              FilledButton.icon(
                onPressed: _loadGroups,
                icon: const Icon(Icons.refresh),
                label: const Text('Try again'),
              ),
            ],
          ),
        ),
      );
    }

    if (_groups.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Theme.of(context)
                      .colorScheme
                      .surfaceContainerHighest
                      .withValues(alpha: 0.6),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.groups_outlined,
                  size: 80,
                  color: Colors.grey.shade400,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'No groups yet',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.bold,
                      color: Theme.of(context).colorScheme.onSurface,
                    ),
              ),
              const SizedBox(height: 12),
              Text(
                'Create a group or accept an invite to start splitting bills.',
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: Theme.of(context)
                          .colorScheme
                          .onSurface
                          .withValues(alpha: 0.6),
                    ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 32),
              FilledButton.icon(
                onPressed: _loading ? null : _showCreateGroupSheet,
                icon: const Icon(Icons.add),
                label: const Text('Create your first group'),
                style: FilledButton.styleFrom(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 32,
                    vertical: 16,
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }

    final body = RefreshIndicator(
      onRefresh: _handleRefresh,
      child: ListView.builder(
        padding: const EdgeInsets.only(top: 8, bottom: 96),
        itemCount: _groups.length,
        itemBuilder: (context, index) {
          final group = _groups[index];
          return SlideAnimation(
            position: index,
            itemCount: _groups.length,
            slideDirection: SlideDirection.fromBottom,
            animationController: AnimationController(
              vsync:
                  ScrollableState(), // This is a hack, better to use flutter_staggered_animations or similar
              duration: const Duration(milliseconds: 375),
            )..forward(),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: GroupCard(
                group: group,
                onTap: () => _navigateToGroupDetails(group),
              ),
            ),
          );
        },
      ),
    );

    if (_loading) {
      return Stack(
        children: [
          body,
          const Positioned(
            left: 0,
            right: 0,
            child: LinearProgressIndicator(minHeight: 3),
          ),
        ],
      );
    }

    return body;
  }
}

class ScrollableState extends TickerProvider {
  @override
  Ticker createTicker(TickerCallback onTick) => Ticker(onTick);
}
