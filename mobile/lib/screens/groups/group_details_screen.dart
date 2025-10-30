import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../main.dart';
import '../../pay_screen.dart';
import '../../widgets/payment_request_card.dart';

/// Group details screen showing members and payment requests
///
/// Displays group information, member list, and recent payment requests
class GroupDetailsScreen extends StatefulWidget {
  final int groupId;

  const GroupDetailsScreen({
    super.key,
    required this.groupId,
  });

  @override
  State<GroupDetailsScreen> createState() => _GroupDetailsScreenState();
}

class _GroupDetailsScreenState extends State<GroupDetailsScreen> {
  bool _loading = true;
  String? _error;
  Map<String, dynamic>? _groupData;

  @override
  void initState() {
    super.initState();
    _loadGroupDetails();
  }

  Future<void> _loadGroupDetails() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await dio.get('/groups/${widget.groupId}');

      setState(() {
        _groupData = response.data as Map<String, dynamic>;
        _loading = false;
      });

      if (Env.debug) {
        print('Loaded group: ${_groupData?['name']}');
      }
    } on DioException catch (e) {
      if (Env.debug) {
        print('Error loading group: ${e.response?.data}');
      }

      setState(() {
        _error = e.response?.data?['message'] as String? ??
                 'Failed to load group details';
        _loading = false;
      });
    } catch (e) {
      if (Env.debug) {
        print('Unexpected error loading group: $e');
      }

      setState(() {
        _error = 'An unexpected error occurred';
        _loading = false;
      });
    }
  }

  void _navigateToPayScreen(Map<String, dynamic> paymentRequest) {
    final prCode = paymentRequest['code'] as String?;

    if (prCode == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Invalid payment request code')),
      );
      return;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => PayScreen(
          prCode: prCode,
          deepLink: paymentRequest['deep_link'] as String?,
        ),
      ),
    ).then((_) {
      // Refresh group details when returning
      _loadGroupDetails();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_groupData?['name'] as String? ?? 'Group Details'),
        actions: [
          IconButton(
            icon: const Icon(Icons.more_vert),
            onPressed: () {
              // TODO: Show group options menu
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Group options coming soon')),
              );
            },
          ),
        ],
      ),
      body: _buildBody(),
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
                onPressed: _loadGroupDetails,
                icon: const Icon(Icons.refresh),
                label: const Text('Try Again'),
              ),
            ],
          ),
        ),
      );
    }

    if (_groupData == null) {
      return const Center(
        child: Text('No data available'),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadGroupDetails,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Group info card
          _buildGroupInfoCard(),
          const SizedBox(height: 16),

          // Members section
          _buildMembersSection(),
          const SizedBox(height: 16),

          // Payment requests section
          _buildPaymentRequestsSection(),
        ],
      ),
    );
  }

  Widget _buildGroupInfoCard() {
    final name = _groupData!['name'] as String? ?? 'Unnamed Group';
    final description = _groupData!['description'] as String?;
    final ownerName = _groupData!['owner']?['name'] as String?;
    final isOwner = _groupData!['is_owner'] as bool? ?? false;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    name,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                ),
                if (isOwner)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.primaryContainer,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      'OWNER',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: Theme.of(context).colorScheme.onPrimaryContainer,
                      ),
                    ),
                  ),
              ],
            ),

            if (description != null && description.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                description,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: Colors.grey[600],
                    ),
              ),
            ],

            if (ownerName != null && !isOwner) ...[
              const SizedBox(height: 12),
              Row(
                children: [
                  Icon(
                    Icons.person_outline,
                    size: 18,
                    color: Colors.grey[600],
                  ),
                  const SizedBox(width: 4),
                  Text(
                    'Owner: $ownerName',
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Colors.grey[600],
                        ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildMembersSection() {
    final members = _groupData!['members'] as List<dynamic>? ?? [];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8),
          child: Text(
            'Members (${members.length})',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
        ),
        const SizedBox(height: 8),

        Card(
          child: members.isEmpty
              ? const Padding(
                  padding: EdgeInsets.all(16),
                  child: Center(
                    child: Text('No members yet'),
                  ),
                )
              : ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: members.length,
                  separatorBuilder: (_, __) => const Divider(height: 1),
                  itemBuilder: (context, index) {
                    final member = members[index] as Map<String, dynamic>;
                    return _buildMemberTile(member);
                  },
                ),
        ),
      ],
    );
  }

  Widget _buildMemberTile(Map<String, dynamic> member) {
    final name = member['name'] as String? ?? 'Unknown';
    final email = member['email'] as String?;
    final shareAmount = member['share_amount_display'] as String?;
    final isCurrentUser = member['is_current_user'] as bool? ?? false;

    return ListTile(
      leading: CircleAvatar(
        backgroundColor: Theme.of(context).colorScheme.primaryContainer,
        child: Text(
          name.isNotEmpty ? name[0].toUpperCase() : '?',
          style: TextStyle(
            color: Theme.of(context).colorScheme.onPrimaryContainer,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
      title: Row(
        children: [
          Text(name),
          if (isCurrentUser) ...[
            const SizedBox(width: 8),
            Text(
              '(You)',
              style: TextStyle(
                color: Theme.of(context).colorScheme.primary,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ],
      ),
      subtitle: email != null ? Text(email) : null,
      trailing: shareAmount != null
          ? Text(
              shareAmount,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            )
          : null,
    );
  }

  Widget _buildPaymentRequestsSection() {
    final paymentRequests =
        _groupData!['payment_requests'] as List<dynamic>? ?? [];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8),
          child: Text(
            'Recent Payments',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
        ),
        const SizedBox(height: 8),

        if (paymentRequests.isEmpty)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Center(
                child: Column(
                  children: [
                    Icon(
                      Icons.payment_outlined,
                      size: 48,
                      color: Colors.grey.shade300,
                    ),
                    const SizedBox(height: 8),
                    const Text('No payment requests yet'),
                  ],
                ),
              ),
            ),
          )
        else
          ...paymentRequests.map((pr) {
            final paymentRequest = pr as Map<String, dynamic>;
            return PaymentRequestCard(
              paymentRequest: paymentRequest,
              onTap: () => _navigateToPayScreen(paymentRequest),
              onPay: () => _navigateToPayScreen(paymentRequest),
            );
          }),
      ],
    );
  }
}
