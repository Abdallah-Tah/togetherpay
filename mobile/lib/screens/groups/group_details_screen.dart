import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../services/api_service.dart';
import '../../widgets/payment_request_card.dart';
import '../../pay_screen.dart';
import 'add_member_sheet.dart';
import 'group_form_sheet.dart';

/// Group details screen showing members, billing cadence, and recent payments.
class GroupDetailsScreen extends StatefulWidget {
  final int groupId;
  final Map<String, dynamic>? initialGroup;

  const GroupDetailsScreen({
    super.key,
    required this.groupId,
    this.initialGroup,
  });

  @override
  State<GroupDetailsScreen> createState() => _GroupDetailsScreenState();
}

class _GroupDetailsScreenState extends State<GroupDetailsScreen> {
  final Dio _dio = ApiService().dio;

  bool _loading = true;
  String? _error;
  Map<String, dynamic>? _groupData;
  List<Map<String, dynamic>> _paymentRequests = [];

  final Set<String> _nudgeInProgress = {};
  final Set<String> _confirmInProgress = {};
  final Set<String> _cancelInProgress = {};

  @override
  void initState() {
    super.initState();

    if (widget.initialGroup != null) {
      _groupData = Map<String, dynamic>.from(widget.initialGroup!);
      _paymentRequests = _extractPaymentRequests(_groupData!);
      _loading = false;
    }

    _loadGroupDetails();
  }

  Future<void> _loadGroupDetails() async {
    if (_groupData == null) {
      setState(() {
        _loading = true;
        _error = null;
      });
    } else {
      setState(() {
        _error = null;
      });
    }

    try {
      final response = await _dio.get('/groups/${widget.groupId}');
      final payload = response.data as Map<String, dynamic>;
      final group = Map<String, dynamic>.from(payload['group'] ?? payload);
      final wasOwner = _groupData?['is_owner'] as bool? ??
          widget.initialGroup?['is_owner'] as bool? ??
          false;

      if (group['is_owner'] == null && wasOwner) {
        group['is_owner'] = true;
      }

      if (!mounted) return;

      setState(() {
        _groupData = group;
        _paymentRequests = _extractPaymentRequests(group);
        _loading = false;
      });

      if (Env.debug) {
        debugPrint('Loaded group: ${group['name']}');
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Error loading group: ${e.response?.data}');
      }

      if (!mounted) return;

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Failed to load group details';
        _loading = _groupData == null;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected error loading group: $e');
      }

      if (!mounted) return;

      setState(() {
        _error = 'An unexpected error occurred';
        _loading = _groupData == null;
      });
    }
  }

  List<Map<String, dynamic>> _extractPaymentRequests(
    Map<String, dynamic> group,
  ) {
    final results = <Map<String, dynamic>>[];
    final seenCodes = <String>{};

    void addRequest(Map<String, dynamic> raw, Map<String, dynamic>? cycle) {
      final map = Map<String, dynamic>.from(raw);
      final code = _codeOf(map) ??
          map['payment_code'] as String? ??
          map['pr_code'] as String?;

      if (code == null) return;
      if (!seenCodes.add(code)) return;

      map['code'] ??= code;
      map['pr_code'] ??= code;
      map['group'] = {
        'id': group['id'],
        'name': group['name'],
      };
      map['amount_display'] ??= _formatAmount(map['amount_cents']);
      map['deep_link'] ??= map['link'];
      map['due_date'] ??= map['due_at'];

      if (cycle != null) {
        map['cycle'] ??= cycle;
        map['cycle_id'] ??= cycle['id'];
        map['due_date'] ??= cycle['due_at'] ?? cycle['due_date'];
      }

      results.add(map);
    }

    final direct = group['payment_requests'];
    if (direct is List) {
      for (final item in direct) {
        if (item is Map<String, dynamic>) {
          addRequest(item, null);
        }
      }
    }

    final cycles = group['cycles'];
    if (cycles is List) {
      for (final cycle in cycles) {
        if (cycle is Map<String, dynamic>) {
          final requests = cycle['payment_requests'];
          if (requests is List) {
            for (final item in requests) {
              if (item is Map<String, dynamic>) {
                addRequest(item, cycle);
              }
            }
          }
        }
      }
    }

    results.sort((a, b) {
      final dateA = _parseDate(
        a['sent_at'] ?? a['created_at'] ?? a['due_date'],
      );
      final dateB = _parseDate(
        b['sent_at'] ?? b['created_at'] ?? b['due_date'],
      );

      if (dateA == null && dateB == null) return 0;
      if (dateA == null) return 1;
      if (dateB == null) return -1;

      return dateB.compareTo(dateA);
    });

    return results;
  }

  String? _formatAmount(dynamic centsValue) {
    if (centsValue == null) return null;

    num? cents;
    if (centsValue is num) {
      cents = centsValue;
    } else if (centsValue is String) {
      cents = num.tryParse(centsValue);
    }

    if (cents == null) return null;
    return '\$${(cents / 100).toStringAsFixed(2)}';
  }

  DateTime? _parseDate(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value;
    if (value is String) {
      try {
        return DateTime.parse(value);
      } catch (_) {
        return null;
      }
    }
    return null;
  }

  String? _codeOf(Map<String, dynamic> paymentRequest) {
    return paymentRequest['code'] as String? ??
        paymentRequest['pr_code'] as String?;
  }

  bool get _isOwner => _groupData?['is_owner'] as bool? ?? false;

  void _navigateToPayScreen(Map<String, dynamic> paymentRequest) {
    final prCode = _codeOf(paymentRequest);

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
      // Always reload when returning from PayScreen
      if (mounted) {
        _loadGroupDetails();
      }
    });
  }

  Future<void> _sendNudge(String prCode) async {
    if (_nudgeInProgress.contains(prCode)) return;

    setState(() {
      _nudgeInProgress.add(prCode);
    });

    try {
      await _dio.post('/requests/by-code/$prCode/nudge');

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Reminder sent')),
        );
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Nudge error: ${e.response?.data}');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data?['message'] as String? ??
                  'Failed to send reminder',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected nudge error: $e');
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('An unexpected error occurred'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _nudgeInProgress.remove(prCode);
        });
      }
    }
  }

  Future<void> _confirmPayment(String prCode) async {
    if (_confirmInProgress.contains(prCode)) return;

    setState(() {
      _confirmInProgress.add(prCode);
    });

    try {
      final response = await _dio.post('/requests/by-code/$prCode/confirm');
      final confirmedAt = response.data?['pr']?['confirmed_at'];

      final index = _paymentRequests.indexWhere(
        (pr) => _codeOf(pr) == prCode,
      );

      if (index != -1) {
        final updated = Map<String, dynamic>.from(_paymentRequests[index]);
        updated['status'] = 'confirmed';
        updated['confirmed_at'] = confirmedAt;

        setState(() {
          _paymentRequests[index] = updated;
        });
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Payment confirmed'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Confirm error: ${e.response?.data}');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data?['message'] as String? ??
                  'Failed to confirm payment',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected confirm error: $e');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('An unexpected error occurred'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _confirmInProgress.remove(prCode);
        });
      }
    }
  }

  Future<void> _cancelRequest(String prCode) async {
    if (_cancelInProgress.contains(prCode)) return;

    // Confirm cancellation
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancel Payment Request?'),
        content: const Text(
          'Are you sure you want to cancel this payment request? This action cannot be undone.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('No'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(
              backgroundColor: Colors.red,
            ),
            child: const Text('Yes, Cancel'),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() {
      _cancelInProgress.add(prCode);
    });

    try {
      await _dio.post('/requests/by-code/$prCode/cancel');

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Payment request cancelled'),
            backgroundColor: Colors.orange,
          ),
        );
      }

      await _loadGroupDetails();
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Cancel error: ${e.response?.data}');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data?['message'] as String? ??
                  'Failed to cancel payment request',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected cancel error: $e');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('An unexpected error occurred'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _cancelInProgress.remove(prCode);
        });
      }
    }
  }

  Future<void> _showEditGroupSheet() async {
    if (_groupData == null) return;

    final wasOwner = _isOwner;

    final result = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      builder: (_) => GroupFormSheet(group: _groupData),
    );

    if (result != null && mounted) {
      result['is_owner'] ??= wasOwner;

      setState(() {
        _groupData = Map<String, dynamic>.from(result);
        _paymentRequests = _extractPaymentRequests(_groupData!);
      });

      await _loadGroupDetails();
    }
  }

  Future<void> _showAddMemberSheet() async {
    if (!_isOwner) return;

    final existingIds = (_groupData?['members'] as List?)
            ?.map((member) => (member as Map?)?['user']?['id'] as int?)
            .whereType<int>()
            .toList() ??
        const <int>[];

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (_) => AddMemberSheet(
        groupId: widget.groupId,
        existingMemberUserIds: existingIds,
      ),
    );

    if (result == true) {
      await _loadGroupDetails();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Member added successfully'),
            backgroundColor: Colors.green,
          ),
        );
      }
    }
  }

  Future<void> _confirmDeleteGroup() async {
    if (!_isOwner) return;

    final name = _groupData?['name'] as String? ?? 'this group';
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete group'),
        content: Text(
          'Are you sure you want to delete "$name"?\nThis action cannot be undone.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      await _deleteGroup();
    }
  }

  Future<void> _deleteGroup() async {
    try {
      await ApiService().dio.delete('/groups/${widget.groupId}');

      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Group deleted'),
          backgroundColor: Colors.green,
        ),
      );

      Navigator.of(context).pop(true);
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Delete group error: ${e.response?.data}');
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data?['message'] as String? ??
                  'Failed to delete group',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected delete group error: $e');
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('An unexpected error occurred'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          _groupData?['name'] as String? ?? 'Group Details',
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'Refresh',
            onPressed: _loadGroupDetails,
          ),
          if (_isOwner)
            IconButton(
              icon: const Icon(Icons.edit_calendar_outlined),
              tooltip: 'Edit schedule',
              onPressed: _showEditGroupSheet,
            ),
          if (_isOwner)
            PopupMenuButton<String>(
              icon: const Icon(Icons.more_vert_rounded),
              onSelected: (value) {
                switch (value) {
                  case 'add_member':
                    _showAddMemberSheet();
                    break;
                  case 'delete_group':
                    _confirmDeleteGroup();
                    break;
                }
              },
              itemBuilder: (context) => [
                PopupMenuItem(
                  value: 'add_member',
                  child: ListTile(
                    leading: Icon(
                      Icons.person_add_alt_1_rounded,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                    title: const Text('Add member'),
                    dense: true,
                  ),
                ),
                const PopupMenuItem(
                  value: 'delete_group',
                  child: ListTile(
                    leading: Icon(
                      Icons.delete_outline_rounded,
                      color: Colors.red,
                    ),
                    title: Text(
                      'Delete group',
                      style: TextStyle(color: Colors.red),
                    ),
                    dense: true,
                  ),
                ),
              ],
            ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading && _groupData == null) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null && _groupData == null) {
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
                label: const Text('Try again'),
              ),
            ],
          ),
        ),
      );
    }

    if (_groupData == null) {
      return const Center(child: Text('No data available'));
    }

    final content = RefreshIndicator(
      onRefresh: _loadGroupDetails,
      child: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        children: [
          _buildGroupInfoCard(),
          const SizedBox(height: 24),
          _buildMembersSection(),
          const SizedBox(height: 24),
          _buildPaymentRequestsSection(),
          const SizedBox(height: 20),
          if (_error != null) ...[
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.orange.shade50,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: Colors.orange.shade300,
                  width: 1,
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.warning_amber_rounded,
                    color: Colors.orange.shade700,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      _error!,
                      style: TextStyle(color: Colors.orange.shade700),
                    ),
                  ),
                ],
              ),
            ),
          ],
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
            child: LinearProgressIndicator(minHeight: 3),
          ),
        ],
      );
    }

    return content;
  }

  Widget _buildGroupInfoCard() {
    final name = _groupData!['name'] as String? ?? 'Unnamed Group';
    final description = _groupData!['description'] as String?;
    final ownerName = _groupData!['owner']?['name'] as String?;
    final cadence = (_groupData!['cadence'] as String?) ?? 'MONTHLY';
    final dueDay = _groupData!['due_day'] as int?;
    final graceDays = _groupData!['grace_days'] as int?;
    final timezone = _groupData!['timezone'] as String?;
    final totalAmount = _groupData!['total_amount'] ??
        (_groupData!['total_amount_cents'] != null
            ? (_groupData!['total_amount_cents'] as num) / 100
            : null);

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            Theme.of(context).colorScheme.primaryContainer,
            Theme.of(context).colorScheme.primaryContainer.withValues(alpha: 0.7),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.1),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Group name and amount
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                name,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.onPrimaryContainer,
                  fontWeight: FontWeight.bold,
                  fontSize: 28,
                  letterSpacing: -0.5,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 8),
              if (ownerName != null)
                Row(
                  children: [
                    Icon(
                      Icons.person_outline_rounded,
                      size: 16,
                      color: Theme.of(context).colorScheme.onPrimaryContainer.withValues(alpha: 0.7),
                    ),
                    const SizedBox(width: 6),
                    Flexible(
                      child: Text(
                        'Owner: $ownerName',
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.onPrimaryContainer.withValues(alpha: 0.7),
                          fontSize: 14,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
            ],
          ),
          if (totalAmount != null) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(
                horizontal: 20,
                vertical: 12,
              ),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primary,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.attach_money_rounded,
                    size: 24,
                    color: Theme.of(context).colorScheme.onPrimary,
                  ),
                  Text(
                    '\$${totalAmount.toStringAsFixed(2)}',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 32,
                      color: Theme.of(context).colorScheme.onPrimary,
                      letterSpacing: -1,
                    ),
                  ),
                ],
              ),
            ),
          ],
          if (description != null && description.isNotEmpty) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.onPrimaryContainer.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                description,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.onPrimaryContainer,
                  fontSize: 14,
                ),
              ),
            ),
          ],
          const SizedBox(height: 20),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _buildInfoChip(
                icon: Icons.calendar_month_rounded,
                label: 'Cadence',
                value: cadence == 'EVERY_4_WEEKS'
                    ? 'Every 4 Weeks'
                    : 'Monthly',
              ),
              if (dueDay != null)
                _buildInfoChip(
                  icon: Icons.event_available_rounded,
                  label: 'Due day',
                  value: 'Day $dueDay',
                ),
              if (graceDays != null)
                _buildInfoChip(
                  icon: Icons.hourglass_bottom_rounded,
                  label: 'Grace',
                  value: '$graceDays day${graceDays == 1 ? '' : 's'}',
                ),
              if (timezone != null)
                _buildInfoChip(
                  icon: Icons.public_rounded,
                  label: 'Timezone',
                  value: timezone,
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildInfoChip({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: Theme.of(context).colorScheme.outline,
          width: 1,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 18,
            color: Theme.of(context).colorScheme.primary,
          ),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w500,
                  color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6),
                  letterSpacing: 0.5,
                ),
              ),
              Text(
                value,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: Theme.of(context).colorScheme.onSurface,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMembersSection() {
    final members = _groupData!['members'];
    final List<Map<String, dynamic>> activeMembers = [];

    if (members is List) {
      for (final member in members) {
        if (member is Map<String, dynamic>) {
          final status = (member['status'] as String?)?.toLowerCase();
          if (status == null || status == 'active') {
            activeMembers.add(Map<String, dynamic>.from(member));
          }
        }
      }

      activeMembers.sort((a, b) {
        final nameA = (a['user']?['name'] as String?) ??
            (a['name'] as String?) ??
            '';
        final nameB = (b['user']?['name'] as String?) ??
            (b['name'] as String?) ??
            '';
        return nameA.toLowerCase().compareTo(nameB.toLowerCase());
      });
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 4),
          child: Row(
            children: [
              Text(
                'Members',
                style: TextStyle(
                  color: Theme.of(context).colorScheme.onSurface,
                  fontWeight: FontWeight.bold,
                  fontSize: 22,
                  letterSpacing: -0.5,
                ),
              ),
              const SizedBox(width: 12),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.primary,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '${activeMembers.length}',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.onPrimary,
                    fontSize: 14,
                  ),
                ),
              ),
              const Spacer(),
              if (_isOwner)
                Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.primaryContainer,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: Theme.of(context).colorScheme.primary,
                      width: 1,
                    ),
                  ),
                  child: Material(
                    color: Colors.transparent,
                    child: InkWell(
                      onTap: _showAddMemberSheet,
                      borderRadius: BorderRadius.circular(12),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 10,
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                              Icons.person_add_alt_1_rounded,
                              size: 18,
                              color: Theme.of(context).colorScheme.primary,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              'Add',
                              style: TextStyle(
                                color: Theme.of(context).colorScheme.primary,
                                fontWeight: FontWeight.w600,
                                fontSize: 15,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        if (activeMembers.isEmpty)
          Container(
            padding: const EdgeInsets.all(32),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: Theme.of(context).colorScheme.outline,
              ),
            ),
            child: Center(
              child: Column(
                children: [
                  Icon(
                    Icons.group_outlined,
                    size: 48,
                    color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.3),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'No members added yet',
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6),
                      fontSize: 16,
                    ),
                  ),
                ],
              ),
            ),
          )
        else
          ...activeMembers.map(_buildMemberTile),
      ],
    );
  }

  Widget _buildMemberTile(Map<String, dynamic> member) {
    final user = member['user'] as Map<String, dynamic>?;
    final name =
        user?['name'] as String? ?? member['name'] as String? ?? 'Unknown';
    final share = _formatMemberShare(member);

    final isGroupOwnerMember = _isGroupOwner(member);
    final canManageMember = _isOwner; // Owner can edit all members including themselves
    final amountDisplay = share ?? '--';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).colorScheme.outline,
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Theme.of(context).shadowColor.withValues(alpha: 0.05),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: canManageMember ? () => _handleMemberTap(member, true) : null,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                // Avatar with gradient
                Container(
                  width: 52,
                  height: 52,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [
                        Theme.of(context).colorScheme.primary,
                        Theme.of(context).colorScheme.primaryContainer,
                      ],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Center(
                    child: Text(
                      name.isNotEmpty ? name[0].toUpperCase() : '?',
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onPrimary,
                        fontWeight: FontWeight.bold,
                        fontSize: 24,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Flexible(
                            child: Text(
                              name,
                              style: TextStyle(
                                color: Theme.of(context).colorScheme.onSurface,
                                fontWeight: FontWeight.w600,
                                fontSize: 17,
                                letterSpacing: -0.3,
                              ),
                              overflow: TextOverflow.ellipsis,
                              maxLines: 1,
                            ),
                          ),
                          if (isGroupOwnerMember) ...[
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 3,
                              ),
                              decoration: BoxDecoration(
                                color: Theme.of(context).colorScheme.primaryContainer,
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(
                                  color: Theme.of(context).colorScheme.primary,
                                  width: 1,
                                ),
                              ),
                              child: Text(
                                'OWNER',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                  color: Theme.of(context).colorScheme.primary,
                                  letterSpacing: 0.5,
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(
                        amountDisplay,
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Theme.of(context).colorScheme.primary,
                          fontSize: 20,
                          letterSpacing: -0.5,
                        ),
                      ),
                    ],
                  ),
                ),
                if (canManageMember)
                  IconButton(
                    icon: Icon(
                      Icons.more_vert_rounded,
                      color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6),
                    ),
                    onPressed: () => _handleMemberTap(member, true),
                    tooltip: 'Member options',
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  bool _isGroupOwner(Map<String, dynamic> member) {
    final ownerId = (_groupData?['owner'] as Map?)?['id'] as int?;
    final memberUserId = (member['user'] as Map?)?['id'] as int?;
    return ownerId != null && memberUserId != null && ownerId == memberUserId;
  }

  Future<void> _handleMemberAction(
    String value,
    Map<String, dynamic> member,
  ) async {
    switch (value) {
      case 'manual':
        await _showManualPaymentDialog(member);
        break;
      case 'edit':
        await _showEditMemberDialog(member);
        break;
      case 'remove':
        await _confirmRemoveMember(member);
        break;
    }
  }

  void _handleMemberTap(
    Map<String, dynamic> member,
    bool canManageMember,
  ) {
    if (!canManageMember) return;

    showModalBottomSheet<void>(
      context: context,
      builder: (context) => SafeArea(
        child: Wrap(
          children: [
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: Center(
                child: Text(
                  'Member Actions',
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.onSurface,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
            Divider(color: Theme.of(context).colorScheme.outline),
            ListTile(
              leading: Icon(
                Icons.request_page_outlined,
                color: Theme.of(context).colorScheme.primary,
              ),
              title: Text(
                'Request payment',
                style: TextStyle(color: Theme.of(context).colorScheme.onSurface),
              ),
              onTap: () {
                Navigator.pop(context);
                _handleMemberAction('manual', member);
              },
            ),
            ListTile(
              leading: Icon(
                Icons.edit_outlined,
                color: Theme.of(context).colorScheme.primary,
              ),
              title: Text(
                'Edit member',
                style: TextStyle(color: Theme.of(context).colorScheme.onSurface),
              ),
              onTap: () {
                Navigator.pop(context);
                _handleMemberAction('edit', member);
              },
            ),
            ListTile(
              leading: const Icon(Icons.delete_outline, color: Colors.red),
              title: const Text(
                'Remove member',
                style: TextStyle(color: Colors.red),
              ),
              onTap: () {
                Navigator.pop(context);
                _handleMemberAction('remove', member);
              },
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  String? _formatMemberShare(Map<String, dynamic> member) {
    final shareType = member['share_type'] as String? ?? 'AMOUNT';
    final rawValue = member['share_value'];

    num? value;
    if (rawValue is num) {
      value = rawValue;
    } else if (rawValue is String) {
      value = num.tryParse(rawValue);
    }

    if (value == null) return null;

    if (shareType == 'PERCENT') {
      return '${value.toStringAsFixed(0)}%';
    }

    return '\$${value.toStringAsFixed(2)}';
  }

  Future<void> _showEditMemberDialog(Map<String, dynamic> member) async {
    if (!_isOwner) return;
    final memberId = member['id'] as int?;
    if (memberId == null) return;

    final result = await showDialog<bool>(
      context: context,
      builder: (_) => _EditMemberDialog(
        groupId: widget.groupId,
        member: member,
      ),
    );

    if (result == true) {
      await _loadGroupDetails();
    }
  }

  Future<void> _confirmRemoveMember(Map<String, dynamic> member) async {
    final memberId = member['id'] as int?;
    if (memberId == null) return;

    final name =
        (member['user']?['name'] as String?) ?? member['name'] as String? ?? '';

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Remove member'),
        content: Text(
          name.isNotEmpty
              ? 'Remove $name from this group?'
              : 'Remove this member from the group?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Remove'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      await _deleteMember(memberId);
    }
  }

  Future<void> _deleteMember(int memberId) async {
    try {
      await ApiService()
          .dio
          .delete('/groups/${widget.groupId}/members/$memberId');

      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Member removed'),
          backgroundColor: Colors.green,
        ),
      );

      await _loadGroupDetails();
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Remove member error: ${e.response?.data}');
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data?['message'] as String? ??
                  'Failed to remove member',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected remove member error: $e');
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('An unexpected error occurred'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _showManualPaymentDialog(
    Map<String, dynamic> member,
  ) async {
    if (!_isOwner) return;
    final memberId = member['id'] as int?;
    if (memberId == null) return;

    final name =
        (member['user']?['name'] as String?) ?? member['name'] as String? ?? '';

    final result = await showDialog<bool>(
      context: context,
      builder: (_) => _ManualPaymentDialog(
        groupId: widget.groupId,
        memberId: memberId,
        memberName: name.isNotEmpty ? name : 'Member',
      ),
    );

    if (result == true) {
      await _loadGroupDetails();
    }
  }

  Widget _buildPaymentRequestsSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 4),
          child: Text(
            'Recent Payments',
            style: TextStyle(
              color: Theme.of(context).colorScheme.onSurface,
              fontWeight: FontWeight.bold,
              fontSize: 22,
              letterSpacing: -0.5,
            ),
          ),
        ),
        const SizedBox(height: 16),
        if (_paymentRequests.isEmpty)
          Container(
            padding: const EdgeInsets.all(32),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: Theme.of(context).colorScheme.outline,
              ),
            ),
            child: Center(
              child: Column(
                children: [
                  Icon(
                    Icons.payment_outlined,
                    size: 48,
                    color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.3),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'No payment requests yet',
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.6),
                      fontSize: 16,
                    ),
                  ),
                  if (_isOwner) ...[
                    const SizedBox(height: 8),
                    Text(
                      'Create a cycle to generate payment requests',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.4),
                        fontSize: 14,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          )
        else
          ..._paymentRequests.map((request) {
            final status = (request['status'] as String?) ?? 'pending';
            final prCode = _codeOf(request);

            final canPay = _isPayableStatus(status);
            final canNudge = _isOwner && _isNudgeableStatus(status);
            final canConfirm = _isOwner && _isConfirmableStatus(status);
            final canCancel = _isOwner && _isCancellableStatus(status);

            return PaymentRequestCard(
              paymentRequest: request,
              onTap: () => _navigateToPayScreen(request),
              onPay: canPay ? () => _navigateToPayScreen(request) : null,
              onNudge:
                  canNudge && prCode != null ? () => _sendNudge(prCode) : null,
              onConfirm: canConfirm && prCode != null
                  ? () => _confirmPayment(prCode)
                  : null,
              onCancel: canCancel && prCode != null
                  ? () => _cancelRequest(prCode)
                  : null,
              isProcessingNudge:
                  prCode != null && _nudgeInProgress.contains(prCode),
              isProcessingConfirm:
                  prCode != null && _confirmInProgress.contains(prCode),
              isProcessingCancel:
                  prCode != null && _cancelInProgress.contains(prCode),
            );
          }),
      ],
    );
  }

  bool _isPayableStatus(String status) {
    final normalized = status.toLowerCase();
    return normalized == 'pending' ||
        normalized == 'sent' ||
        normalized == 'overdue' ||
        normalized == 'late';
  }

  bool _isNudgeableStatus(String status) {
    final normalized = status.toLowerCase();
    return normalized == 'pending' ||
        normalized == 'sent' ||
        normalized == 'member_marked' ||
        normalized == 'overdue' ||
        normalized == 'late';
  }

  bool _isConfirmableStatus(String status) {
    return status.toLowerCase() == 'member_marked';
  }

  bool _isCancellableStatus(String status) {
    final normalized = status.toLowerCase();
    return normalized == 'pending' ||
        normalized == 'sent' ||
        normalized == 'member_marked';
  }
}

class _EditMemberDialog extends StatefulWidget {
  final int groupId;
  final Map<String, dynamic> member;

  const _EditMemberDialog({
    required this.groupId,
    required this.member,
  });

  @override
  State<_EditMemberDialog> createState() => _EditMemberDialogState();
}

class _EditMemberDialogState extends State<_EditMemberDialog> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _shareValueController;
  late TextEditingController _venmoUsernameController;
  late TextEditingController _cashTagController;
  late TextEditingController _cashPhoneController;
  late TextEditingController _cashEmailController;

  late String _preferredChannel;
  late String _shareType;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final member = widget.member;
    _preferredChannel =
        (member['preferred_channel'] as String? ?? 'VENMO').toUpperCase();
    _shareType = (member['share_type'] as String? ?? 'AMOUNT').toUpperCase();

    _shareValueController = TextEditingController(
      text: _formatNumber(member['share_value']),
    );
    _venmoUsernameController = TextEditingController(
      text: member['venmo_username']?.toString() ?? '',
    );
    _cashTagController = TextEditingController(
      text: member['cash_tag']?.toString() ?? '',
    );
    _cashPhoneController = TextEditingController(
      text: member['cash_phone']?.toString() ?? '',
    );
    _cashEmailController = TextEditingController(
      text: member['cash_email']?.toString() ?? '',
    );
  }

  @override
  void dispose() {
    _shareValueController.dispose();
    _venmoUsernameController.dispose();
    _cashTagController.dispose();
    _cashPhoneController.dispose();
    _cashEmailController.dispose();
    super.dispose();
  }

  String _formatNumber(dynamic value) {
    if (value is num) {
      return value.toString();
    }
    if (value is String && value.isNotEmpty) {
      return value;
    }
    return '0';
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    final memberId = widget.member['id'] as int?;
    if (memberId == null) {
      setState(() {
        _error = 'Member ID is missing';
      });
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    final payload = <String, dynamic>{
      'preferred_channel': _preferredChannel,
      'share_type': _shareType,
      'share_value': double.tryParse(_shareValueController.text) ?? 0,
    };

    if (_preferredChannel == 'VENMO') {
      payload['venmo_username'] = _venmoUsernameController.text.trim();
      payload['cash_tag'] = null;
      payload['cash_phone'] = null;
      payload['cash_email'] = null;
    } else if (_preferredChannel == 'CASH_APP') {
      final cashTag = _cashTagController.text.trim();
      final cashPhone = _cashPhoneController.text.trim();
      final cashEmail = _cashEmailController.text.trim();
      payload['cash_tag'] = cashTag.isNotEmpty ? cashTag : null;
      payload['cash_phone'] = cashPhone.isNotEmpty ? cashPhone : null;
      payload['cash_email'] = cashEmail.isNotEmpty ? cashEmail : null;
      payload['venmo_username'] = null;
    }

    try {
      await ApiService().dio.put(
            '/groups/${widget.groupId}/members/$memberId',
            data: payload,
          );

      if (mounted) {
        Navigator.of(context).pop(true);
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Update member error: ${e.response?.data}');
      }
      if (!mounted) return;
      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Failed to update member';
        _saving = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected update member error: $e');
      }
      if (!mounted) return;
      setState(() {
        _error = 'An unexpected error occurred';
        _saving = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = widget.member['user'] as Map<String, dynamic>?;
    final name = user?['name'] as String? ?? widget.member['name'] as String? ?? 'Member';

    return AlertDialog(
      title: Text('Edit $name'),
      content: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              DropdownButtonFormField<String>(
                key: ValueKey('edit_channel_$_preferredChannel'),
                initialValue: _preferredChannel,
                decoration: const InputDecoration(
                  labelText: 'Payment channel',
                  border: OutlineInputBorder(),
                ),
                items: const [
                  DropdownMenuItem(
                    value: 'VENMO',
                    child: Text('Venmo'),
                  ),
                  DropdownMenuItem(
                    value: 'CASH_APP',
                    child: Text('Cash App'),
                  ),
                ],
                onChanged: _saving
                    ? null
                    : (value) {
                        if (value != null) {
                          setState(() {
                            _preferredChannel = value;
                          });
                        }
                      },
              ),
              const SizedBox(height: 16),
              if (_preferredChannel == 'VENMO')
                TextFormField(
                  controller: _venmoUsernameController,
                  enabled: !_saving,
                  decoration: const InputDecoration(
                    labelText: 'Venmo username',
                    hintText: '@username',
                    border: OutlineInputBorder(),
                  ),
                  validator: (value) {
                    if (_preferredChannel == 'VENMO') {
                      if (value == null || value.trim().isEmpty) {
                        return 'Venmo username is required';
                      }
                    }
                    return null;
                  },
                ),
              if (_preferredChannel == 'VENMO') const SizedBox(height: 16),
              if (_preferredChannel == 'CASH_APP') ...[
                TextFormField(
                  controller: _cashTagController,
                  enabled: !_saving,
                  decoration: const InputDecoration(
                    labelText: 'Cash tag (optional)',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _cashPhoneController,
                  enabled: !_saving,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'Cash App phone (optional)',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _cashEmailController,
                  enabled: !_saving,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    labelText: 'Cash App email (optional)',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
              ],
              DropdownButtonFormField<String>(
                key: ValueKey('edit_share_$_shareType'),
                initialValue: _shareType,
                decoration: const InputDecoration(
                  labelText: 'Contribution type',
                  border: OutlineInputBorder(),
                ),
                items: const [
                  DropdownMenuItem(
                    value: 'AMOUNT',
                    child: Text('Fixed amount'),
                  ),
                  DropdownMenuItem(
                    value: 'PERCENT',
                    child: Text('Percentage'),
                  ),
                ],
                onChanged: _saving
                    ? null
                    : (value) {
                        if (value != null) {
                          setState(() {
                            _shareType = value;
                          });
                        }
                      },
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _shareValueController,
                enabled: !_saving,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: InputDecoration(
                  labelText: _shareType == 'PERCENT' ? 'Percent' : 'Amount',
                  border: const OutlineInputBorder(),
                  prefixText: _shareType == 'AMOUNT' ? '\$' : null,
                  suffixText: _shareType == 'PERCENT' ? '%' : null,
                ),
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'Required';
                  }
                  final parsed = double.tryParse(value);
                  if (parsed == null || parsed <= 0) {
                    return 'Enter a valid value';
                  }
                  if (_shareType == 'PERCENT' && parsed > 100) {
                    return 'Maximum 100%';
                  }
                  return null;
                },
              ),
              if (_error != null) ...[
                const SizedBox(height: 16),
                Text(
                  _error!,
                  style: TextStyle(color: Colors.red.shade700),
                ),
              ],
            ],
          ),
        ),
      ),
      actions: [
        TextButton(
          onPressed: _saving ? null : () => Navigator.pop(context, false),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: _saving ? null : _save,
          child: _saving
              ? const SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('Save'),
        ),
      ],
    );
  }
}

class _ManualPaymentDialog extends StatefulWidget {
  final int groupId;
  final int memberId;
  final String memberName;

  const _ManualPaymentDialog({
    required this.groupId,
    required this.memberId,
    required this.memberName,
  });

  @override
  State<_ManualPaymentDialog> createState() => _ManualPaymentDialogState();
}

class _ManualPaymentDialogState extends State<_ManualPaymentDialog> {
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController(text: '0');
  final _noteController = TextEditingController();
  bool _sendNow = true;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _amountController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    final amount = double.tryParse(_amountController.text) ?? 0;
    final note = _noteController.text.trim();

    final payload = <String, dynamic>{
      'amount': amount,
      'send_now': _sendNow,
    };

    if (note.isNotEmpty) {
      payload['note'] = note;
    }

    try {
      await ApiService().dio.post(
            '/groups/${widget.groupId}/members/${widget.memberId}/payment-requests',
            data: payload,
          );

      if (mounted) {
        Navigator.of(context).pop(true);
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Manual payment error: ${e.response?.data}');
      }

      if (!mounted) return;

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Failed to create payment request';
        _submitting = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected manual payment error: $e');
      }

      if (!mounted) return;

      setState(() {
        _error = 'An unexpected error occurred';
        _submitting = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text('Request payment from ${widget.memberName}'),
      content: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextFormField(
                controller: _amountController,
                enabled: !_submitting,
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(
                  labelText: 'Amount',
                  prefixText: '\$',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'Amount is required';
                  }
                  final parsed = double.tryParse(value);
                  if (parsed == null || parsed <= 0) {
                    return 'Enter a valid amount';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _noteController,
                enabled: !_submitting,
                maxLength: 160,
                decoration: const InputDecoration(
                  labelText: 'Note (optional)',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 8),
              SwitchListTile(
                value: _sendNow,
                onChanged: _submitting
                    ? null
                    : (value) {
                        setState(() {
                          _sendNow = value;
                        });
                      },
                title: const Text('Send notification now'),
                subtitle: const Text('Notify the member immediately'),
              ),
              if (_error != null) ...[
                const SizedBox(height: 8),
                Text(
                  _error!,
                  style: TextStyle(color: Colors.red.shade700),
                ),
              ],
            ],
          ),
        ),
      ),
      actions: [
        TextButton(
          onPressed: _submitting ? null : () => Navigator.pop(context, false),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: _submitting ? null : _submit,
          child: _submitting
              ? const SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('Request'),
        ),
      ],
    );
  }
}
