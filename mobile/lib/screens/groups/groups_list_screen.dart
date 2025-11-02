import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../services/api_service.dart';
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
        title: const Text('My Groups'),
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
      body: _buildBody(),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _loading ? null : _showCreateGroupSheet,
        icon: const Icon(Icons.group_add_outlined),
        label: const Text('New Group'),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading && _groups.isEmpty) {
      return const Center(child: CircularProgressIndicator());
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
              Icon(
                Icons.groups_outlined,
                size: 80,
                color: Colors.grey.shade300,
              ),
              const SizedBox(height: 16),
              Text(
                'No groups yet',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      color: Colors.grey,
                    ),
              ),
              const SizedBox(height: 8),
              Text(
                'Create a group to start splitting bills with friends.',
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: Colors.grey,
                    ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 32),
              FilledButton.icon(
                onPressed: _loading ? null : _showCreateGroupSheet,
                icon: const Icon(Icons.add),
                label: const Text('Create your first group'),
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
          return GroupCard(
            group: group,
            onTap: () => _navigateToGroupDetails(group),
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
