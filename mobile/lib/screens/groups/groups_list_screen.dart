import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../main.dart';
import '../../widgets/group_card.dart';
import 'group_details_screen.dart';

/// Groups list screen showing user's groups
///
/// Displays all groups the user owns or is a member of
class GroupsListScreen extends StatefulWidget {
  const GroupsListScreen({super.key});

  @override
  State<GroupsListScreen> createState() => _GroupsListScreenState();
}

class _GroupsListScreenState extends State<GroupsListScreen> {
  bool _loading = true;
  String? _error;
  List<Map<String, dynamic>> _groups = [];

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
      final response = await dio.get('/groups');

      if (response.data is Map<String, dynamic>) {
        // API returns { data: [...] }
        final data = response.data as Map<String, dynamic>;
        final groupsList = data['data'] as List<dynamic>?;

        setState(() {
          _groups = groupsList?.map((g) => g as Map<String, dynamic>).toList() ?? [];
          _loading = false;
        });
      } else if (response.data is List) {
        // API returns [...] directly
        setState(() {
          _groups = (response.data as List)
              .map((g) => g as Map<String, dynamic>)
              .toList();
          _loading = false;
        });
      } else {
        throw Exception('Unexpected response format');
      }

      if (Env.debug) {
        print('Loaded ${_groups.length} groups');
      }
    } on DioException catch (e) {
      if (Env.debug) {
        print('Error loading groups: ${e.response?.data}');
      }

      setState(() {
        _error = e.response?.data?['message'] as String? ??
                 'Failed to load groups';
        _loading = false;
      });
    } catch (e) {
      if (Env.debug) {
        print('Unexpected error loading groups: $e');
      }

      setState(() {
        _error = 'An unexpected error occurred';
        _loading = false;
      });
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
        builder: (_) => GroupDetailsScreen(groupId: groupId),
      ),
    ).then((_) {
      // Refresh groups when returning from details
      _loadGroups();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My Groups'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loading ? null : _handleRefresh,
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: _buildBody(),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          // TODO: Implement create group
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Create group feature coming soon'),
            ),
          );
        },
        icon: const Icon(Icons.add),
        label: const Text('New Group'),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(
        child: CircularProgressIndicator(),
      );
    }

    if (_error != null) {
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
                label: const Text('Try Again'),
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
                'Create a group to start splitting bills with friends',
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: Colors.grey,
                    ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 32),
              FilledButton.icon(
                onPressed: () {
                  // TODO: Implement create group
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Create group feature coming soon'),
                    ),
                  );
                },
                icon: const Icon(Icons.add),
                label: const Text('Create Your First Group'),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _handleRefresh,
      child: ListView.builder(
        padding: const EdgeInsets.only(top: 8, bottom: 80),
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
  }
}
