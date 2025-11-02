import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import '../../services/api_service.dart';

class GroupsListScreen extends StatefulWidget {
  const GroupsListScreen({super.key});

  @override
  State<GroupsListScreen> createState() => _GroupsListScreenState();
}

class _GroupsListScreenState extends State<GroupsListScreen> {
  final ApiService _apiService = ApiService();
  List<dynamic> _ownedGroups = [];
  List<dynamic> _memberGroups = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadGroups();
  }

  Future<void> _loadGroups() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final response = await _apiService.dio.get('/groups');

      if (response.statusCode == 200) {
        setState(() {
          _ownedGroups = response.data['owned_groups'] ?? [];
          _memberGroups = response.data['member_groups'] ?? [];
          _isLoading = false;
        });
      }
    } on DioException catch (e) {
      setState(() {
        _error = e.response?.data['message'] ?? 'Failed to load groups';
        _isLoading = false;
      });
      debugPrint('Error loading groups: $e');
    } catch (e) {
      setState(() {
        _error = 'An unexpected error occurred';
        _isLoading = false;
      });
      debugPrint('Unexpected error: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Groups'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () {
              // TODO: Navigate to create group screen
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Create group coming soon')),
              );
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadGroups,
        child: _buildBody(),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(),
      );
    }

    if (_error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.error_outline,
              size: 60,
              color: Colors.red.shade300,
            ),
            const SizedBox(height: 16),
            Text(
              _error!,
              style: const TextStyle(fontSize: 16),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _loadGroups,
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }

    if (_ownedGroups.isEmpty && _memberGroups.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.group_outlined,
              size: 80,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 16),
            const Text(
              'No groups yet',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Create a group to start splitting bills',
              style: TextStyle(
                fontSize: 14,
                color: Colors.grey.shade600,
              ),
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: () {
                // TODO: Navigate to create group screen
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Create group coming soon')),
                );
              },
              icon: const Icon(Icons.add),
              label: const Text('Create Group'),
            ),
          ],
        ),
      );
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_ownedGroups.isNotEmpty) ...[
          Text(
            'My Groups',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
          const SizedBox(height: 12),
          ..._ownedGroups.map((group) => _buildGroupCard(group, isOwner: true)),
          const SizedBox(height: 24),
        ],
        if (_memberGroups.isNotEmpty) ...[
          Text(
            'Member Of',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
          const SizedBox(height: 12),
          ..._memberGroups.map((group) => _buildGroupCard(group, isOwner: false)),
        ],
      ],
    );
  }

  Widget _buildGroupCard(dynamic group, {required bool isOwner}) {
    final name = group['name'] ?? 'Unnamed Group';
    final memberCount = (group['members'] as List?)?.length ?? 0;
    final totalAmountCents = group['total_amount_cents'];
    final currency = group['currency'] ?? 'USD';

    String? amountText;
    if (totalAmountCents != null) {
      final amount = (totalAmountCents / 100).toStringAsFixed(2);
      amountText = '$currency \$$amount/month';
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        contentPadding: const EdgeInsets.all(16),
        leading: CircleAvatar(
          backgroundColor: isOwner ? Colors.blue.shade100 : Colors.green.shade100,
          child: Icon(
            isOwner ? Icons.person : Icons.group,
            color: isOwner ? Colors.blue.shade700 : Colors.green.shade700,
          ),
        ),
        title: Text(
          name,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 16,
          ),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 4),
            Text('$memberCount ${memberCount == 1 ? 'member' : 'members'}'),
            if (amountText != null) ...[
              const SizedBox(height: 2),
              Text(
                amountText,
                style: TextStyle(
                  color: Colors.grey.shade700,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ],
        ),
        trailing: const Icon(Icons.chevron_right),
        onTap: () {
          // TODO: Navigate to group details
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Group details for: $name')),
          );
        },
      ),
    );
  }
}
