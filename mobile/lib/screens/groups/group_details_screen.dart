import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../main.dart';
import '../../pay_screen.dart';
import '../../utils/theme.dart';
import '../../widgets/payment_request_card.dart';
import 'add_member_sheet.dart';
import 'group_form_sheet.dart';

/// Group details screen showing members and payment requests.
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
  bool _loading = true;
  String? _error;
  Map<String, dynamic>? _groupData;
  List<Map<String, dynamic>> _paymentRequests = [];

  final Set<String> _nudgeInProgress = {};
  final Set<String> _confirmInProgress = {};

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
      final response = await dio.get('/groups/${widget.groupId}');
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
      final code =
          _codeOf(map) ?? map['payment_code'] as String? ?? map['pr_code'] as String?;

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

  List<Map<String, dynamic>> get _members =>
      (_groupData?['members'] as List<dynamic>?)
          ?.cast<Map<String, dynamic>>() ??
      [];

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
    ).then((_) => _loadGroupDetails());
  }

  Future<void> _showManualPaymentRequest() async {
    if (_members.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Add members first before sending payment requests'),
        ),
      );
      return;
    }

    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (context) => _ManualPaymentDialog(
        groupId: widget.groupId,
        members: _members,
      ),
    );

    if (result != null && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Payment request sent'),
          backgroundColor: Colors.green,
        ),
      );
      await _loadGroupDetails();
    }
  }

  @override
  Widget build(BuildContext context) {
    final groupName = _groupData?['name'] as String? ?? 'Group Details';

    return Scaffold(
      appBar: AppBar(
        title: Text(groupName),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
            onPressed: _loadGroupDetails,
          ),
          if (_isOwner)
            IconButton(
              icon: const Icon(Icons.edit_calendar),
              tooltip: 'Edit schedule',
              onPressed: _showEditGroupSheet,
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
                label: const Text('Try Again'),
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
        padding: const EdgeInsets.all(16),
        children: [
          _buildHeader(),
          const SizedBox(height: 16),
          _buildActionButtons(),
          const SizedBox(height: 24),
          _buildMembersSection(),
          const SizedBox(height: 24),
          _buildPaymentRequestsSection(),
          if (_error != null) ...[
            const SizedBox(height: 16),
            Text(
              _error!,
              style: TextStyle(color: Colors.orange.shade700),
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

  Widget _buildHeader() {
    final name = _groupData!['name'] as String? ?? 'Unnamed Group';
    final totalAmount = _groupData!['total_amount'] ??
        (_groupData!['total_amount_cents'] != null
            ? (_groupData!['total_amount_cents'] as num) / 100
            : null);

    return Column(
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
            if (_isOwner)
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: AppTheme.primary,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Text(
                  'OWNER',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ),
          ],
        ),
        if (totalAmount != null) ...[
          const SizedBox(height: 8),
          Text(
            '\$ Total per cycle: \$${(totalAmount is num ? totalAmount : num.tryParse('$totalAmount'))?.toStringAsFixed(2) ?? totalAmount.toString()}',
            style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                  color: Colors.grey[600],
                ),
          ),
        ],
      ],
    );
  }

  Widget _buildActionButtons() {
    return Row(
      children: [
        Expanded(
          child: _ActionButton(
            icon: Icons.info_outline,
            onTap: _showGroupInfo,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _ActionButton(
            icon: Icons.calendar_month,
            onTap: _showEditGroupSheet,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _ActionButton(
            icon: Icons.person_add_outlined,
            onTap: _showAddMemberDialog,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _ActionButton(
            icon: Icons.attach_money,
            onTap: _showManualPaymentRequest,
          ),
        ),
      ],
    );
  }

  void _showGroupInfo() {
    final cadence = (_groupData!['cadence'] as String?) ?? 'MONTHLY';
    final dueDay = _groupData!['due_day'] as int?;
    final graceDays = _groupData!['grace_days'] as int?;
    final timezone = _groupData!['timezone'] as String?;

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Group Information'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildInfoRow(
              icon: Icons.schedule,
              label: 'Schedule',
              value: _friendlyCadence(cadence),
            ),
            if (dueDay != null) ...[
              const SizedBox(height: 8),
              _buildInfoRow(
                icon: Icons.calendar_today,
                label: 'Due Day',
                value: 'Day $dueDay',
              ),
            ],
            if (graceDays != null) ...[
              const SizedBox(height: 8),
              _buildInfoRow(
                icon: Icons.hourglass_bottom,
                label: 'Grace Period',
                value: '$graceDays day${graceDays == 1 ? '' : 's'}',
              ),
            ],
            if (timezone != null) ...[
              const SizedBox(height: 8),
              _buildInfoRow(
                icon: Icons.public,
                label: 'Timezone',
                value: timezone,
              ),
            ],
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Row(
      children: [
        Icon(icon, size: 18, color: Colors.grey[600]),
        const SizedBox(width: 8),
        Text(
          '$label: ',
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
        ),
        Expanded(
          child: Text(
            value,
            style: Theme.of(context).textTheme.bodyMedium,
          ),
        ),
      ],
    );
  }

  String _friendlyCadence(String cadence) {
    switch (cadence) {
      case 'EVERY_4_WEEKS':
        return 'Every 4 weeks';
      case 'MONTHLY':
      default:
        return 'Monthly';
    }
  }

  Future<void> _showAddMemberDialog() async {
    // Get existing member user IDs to exclude from selection
    final existingUserIds = ((_groupData?['members'] as List?) ?? [])
        .map((m) => (m['user'] as Map?)?['id'] as int?)
        .where((id) => id != null)
        .cast<int>()
        .toList();

    final result = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => AddMemberSheet(
        groupId: widget.groupId,
        existingMemberUserIds: existingUserIds,
      ),
    );

    if (result != null && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Member added successfully'),
          backgroundColor: Colors.green,
        ),
      );

      if (result['refresh'] == true) {
        await _loadGroupDetails();
      }
    }
  }

  Future<void> _showEditGroupSheet() async {
    if (_groupData == null) return;

    final wasOwner = _isOwner;

    final result = await Navigator.push<Map<String, dynamic>>(
      context,
      MaterialPageRoute(
        builder: (_) => GroupFormSheet(group: _groupData),
      ),
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

  Widget _buildMembersSection() {
    final members = _members;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Members (${members.length})',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
        ),
        const SizedBox(height: 12),
        if (members.isEmpty)
          Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Text(
                'No members yet',
                style: TextStyle(color: Colors.grey[600]),
              ),
            ),
          )
        else
          ...members.map((member) => _buildMemberTile(member)),
      ],
    );
  }

  Widget _buildMemberTile(Map<String, dynamic> member) {
    final user = member['user'] as Map<String, dynamic>?;
    final name = user?['name'] as String? ??
        member['name'] as String? ??
        'Unknown';
    final email = user?['email'] as String? ?? member['email'] as String?;
    final share = _formatMemberShare(member);

    return ListTile(
      contentPadding: EdgeInsets.zero,
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
      title: Text(name),
      subtitle: email != null ? Text(email) : null,
      trailing: share != null
          ? Text(
              share,
              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            )
          : null,
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

  Widget _buildPaymentRequestsSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Recent Payments',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
        ),
        const SizedBox(height: 12),
        if (_paymentRequests.isEmpty)
          Center(
            child: Padding(
              padding: const EdgeInsets.all(32),
              child: Column(
                children: [
                  Icon(
                    Icons.payment_outlined,
                    size: 64,
                    color: Colors.grey.shade300,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'No payment requests yet',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 4),
                  if (_isOwner)
                    Text(
                      'Create a cycle to generate payment requests.',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.grey[600]),
                    ),
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

            return PaymentRequestCard(
              paymentRequest: request,
              onTap: () => _navigateToPayScreen(request),
              onPay: canPay ? () => _navigateToPayScreen(request) : null,
              onNudge: canNudge && prCode != null
                  ? () => _sendNudge(prCode)
                  : null,
              onConfirm: canConfirm && prCode != null
                  ? () => _confirmPayment(prCode)
                  : null,
              isProcessingNudge:
                  prCode != null && _nudgeInProgress.contains(prCode),
              isProcessingConfirm:
                  prCode != null && _confirmInProgress.contains(prCode),
            );
          }),
      ],
    );
  }

  Future<void> _sendNudge(String prCode) async {
    if (_nudgeInProgress.contains(prCode)) return;

    setState(() {
      _nudgeInProgress.add(prCode);
    });

    try {
      await dio.post('/requests/by-code/$prCode/nudge');

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
      final response = await dio.post('/requests/by-code/$prCode/confirm');
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
}

/// Action button widget for group actions
class _ActionButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;

  const _ActionButton({
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          height: 80,
          decoration: BoxDecoration(
            border: Border.all(color: Colors.grey.shade300),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Center(
            child: Icon(
              icon,
              size: 32,
              color: AppTheme.primary,
            ),
          ),
        ),
      ),
    );
  }
}

/// Manual payment request dialog
class _ManualPaymentDialog extends StatefulWidget {
  final int groupId;
  final List<Map<String, dynamic>> members;

  const _ManualPaymentDialog({
    required this.groupId,
    required this.members,
  });

  @override
  State<_ManualPaymentDialog> createState() => _ManualPaymentDialogState();
}

class _ManualPaymentDialogState extends State<_ManualPaymentDialog> {
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  final _noteController = TextEditingController();

  Map<String, dynamic>? _selectedMember;
  bool _sending = false;

  @override
  void dispose() {
    _amountController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _sendPaymentRequest() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedMember == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a member')),
      );
      return;
    }

    setState(() {
      _sending = true;
    });

    try {
      final amount = double.parse(_amountController.text);
      final memberId = _selectedMember!['id'];

      final response = await dio.post(
        '/groups/${widget.groupId}/members/$memberId/payment-requests',
        data: {
          'amount': amount,
          'note': _noteController.text.trim().isEmpty
              ? null
              : _noteController.text.trim(),
          'send_now': true,
        },
      );

      if (mounted) {
        Navigator.pop(context, {
          'message': response.data['message'] ?? 'Payment request sent',
        });
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Manual payment request error: ${e.response?.data}');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data?['message'] as String? ??
                  'Failed to send payment request',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _sending = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Send Payment Request'),
      content: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              DropdownButtonFormField<Map<String, dynamic>>(
                value: _selectedMember,
                decoration: const InputDecoration(
                  labelText: 'Select Member',
                  border: OutlineInputBorder(),
                ),
                items: widget.members.map((member) {
                  final user = member['user'] as Map<String, dynamic>?;
                  final name = user?['name'] as String? ?? 'Unknown';
                  return DropdownMenuItem(
                    value: member,
                    child: Text(name),
                  );
                }).toList(),
                onChanged: _sending
                    ? null
                    : (value) {
                        setState(() {
                          _selectedMember = value;
                        });
                      },
                validator: (value) {
                  if (value == null) return 'Please select a member';
                  return null;
                },
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _amountController,
                enabled: !_sending,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: const InputDecoration(
                  labelText: 'Amount',
                  prefixText: '\$',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'Please enter an amount';
                  }
                  final amount = double.tryParse(value);
                  if (amount == null || amount <= 0) {
                    return 'Please enter a valid amount';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _noteController,
                enabled: !_sending,
                maxLines: 2,
                decoration: const InputDecoration(
                  labelText: 'Note (optional)',
                  border: OutlineInputBorder(),
                  hintText: 'e.g., Netflix Premium - November',
                ),
              ),
            ],
          ),
        ),
      ),
      actions: [
        TextButton(
          onPressed: _sending ? null : () => Navigator.pop(context),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: _sending ? null : _sendPaymentRequest,
          child: _sending
              ? const SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('Send Request'),
        ),
      ],
    );
  }
}

/// Add member dialog
class _AddMemberDialog extends StatefulWidget {
  final int groupId;

  const _AddMemberDialog({required this.groupId});

  @override
  State<_AddMemberDialog> createState() => _AddMemberDialogState();
}

class _AddMemberDialogState extends State<_AddMemberDialog> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _shareValueController = TextEditingController();
  final _venmoUsernameController = TextEditingController();
  final _cashTagController = TextEditingController();
  final _cashPhoneController = TextEditingController();
  final _cashEmailController = TextEditingController();

  String _shareType = 'AMOUNT';
  String _preferredChannel = 'VENMO';
  bool _adding = false;

  @override
  void dispose() {
    _emailController.dispose();
    _shareValueController.dispose();
    _venmoUsernameController.dispose();
    _cashTagController.dispose();
    _cashPhoneController.dispose();
    _cashEmailController.dispose();
    super.dispose();
  }

  Future<void> _addMember() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _adding = true;
    });

    try {
      final data = <String, dynamic>{
        'email': _emailController.text.trim(),
        'preferred_channel': _preferredChannel,
        'share_type': _shareType,
        'share_value': _shareType == 'PERCENT'
            ? double.parse(_shareValueController.text)
            : double.parse(_shareValueController.text),
      };

      // Add payment channel details
      if (_preferredChannel == 'VENMO') {
        if (_venmoUsernameController.text.trim().isNotEmpty) {
          data['venmo_username'] = _venmoUsernameController.text.trim();
        }
      } else if (_preferredChannel == 'CASH_APP') {
        if (_cashTagController.text.trim().isNotEmpty) {
          data['cash_tag'] = _cashTagController.text.trim();
        }
        if (_cashPhoneController.text.trim().isNotEmpty) {
          data['cash_phone'] = _cashPhoneController.text.trim();
        }
        if (_cashEmailController.text.trim().isNotEmpty) {
          data['cash_email'] = _cashEmailController.text.trim();
        }
      }

      final response = await dio.post(
        '/groups/${widget.groupId}/members',
        data: data,
      );

      if (mounted) {
        Navigator.pop(context, {
          'message': response.data['message'] ?? 'Member added successfully',
        });
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Add member error: ${e.response?.data}');
        debugPrint('Status code: ${e.response?.statusCode}');
      }

      if (mounted) {
        String errorMessage = 'Failed to add member';

        if (e.response?.statusCode == 404) {
          errorMessage = 'User not found. They need to register first.';
        } else if (e.response?.statusCode == 422) {
          final errors = e.response?.data?['errors'];
          if (errors is Map) {
            final emailErrors = errors['email'];
            if (emailErrors is List && emailErrors.isNotEmpty) {
              errorMessage = emailErrors[0].toString();
            } else {
              errorMessage = e.response?.data?['message'] ?? errorMessage;
            }
          } else {
            errorMessage = e.response?.data?['message'] ?? errorMessage;
          }
        } else {
          errorMessage = e.response?.data?['message'] as String? ?? errorMessage;
        }

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(errorMessage),
            backgroundColor: Colors.red,
            duration: const Duration(seconds: 4),
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _adding = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Add Member'),
      content: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextFormField(
                controller: _emailController,
                enabled: !_adding,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(
                  labelText: 'Email',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.email_outlined),
                  helperText: 'User must have a registered account',
                  helperMaxLines: 2,
                ),
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'Please enter an email';
                  }
                  if (!value.contains('@')) {
                    return 'Please enter a valid email';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                value: _preferredChannel,
                decoration: const InputDecoration(
                  labelText: 'Payment Channel',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.payment),
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
                onChanged: _adding
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

              // Payment channel specific fields
              if (_preferredChannel == 'VENMO') ...[
                TextFormField(
                  controller: _venmoUsernameController,
                  enabled: !_adding,
                  decoration: const InputDecoration(
                    labelText: 'Venmo Username',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.alternate_email),
                    hintText: '@username',
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return 'Venmo username is required';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
              ],

              if (_preferredChannel == 'CASH_APP') ...[
                TextFormField(
                  controller: _cashTagController,
                  enabled: !_adding,
                  decoration: const InputDecoration(
                    labelText: 'Cash Tag (optional)',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.tag),
                    hintText: '\$cashtag',
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _cashPhoneController,
                  enabled: !_adding,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'Cash App Phone (optional)',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.phone),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _cashEmailController,
                  enabled: !_adding,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    labelText: 'Cash App Email (optional)',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.email_outlined),
                  ),
                ),
                const SizedBox(height: 16),
              ],

              Row(
                children: [
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      value: _shareType,
                      decoration: const InputDecoration(
                        labelText: 'Share Type',
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(
                          value: 'AMOUNT',
                          child: Text('Fixed Amount'),
                        ),
                        DropdownMenuItem(
                          value: 'PERCENT',
                          child: Text('Percentage'),
                        ),
                      ],
                      onChanged: _adding
                          ? null
                          : (value) {
                              if (value != null) {
                                setState(() {
                                  _shareType = value;
                                });
                              }
                            },
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextFormField(
                      controller: _shareValueController,
                      enabled: !_adding,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      decoration: InputDecoration(
                        labelText: 'Share Value',
                        border: const OutlineInputBorder(),
                        prefixText: _shareType == 'AMOUNT' ? '\$' : null,
                        suffixText: _shareType == 'PERCENT' ? '%' : null,
                      ),
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Required';
                        }
                        final amount = double.tryParse(value);
                        if (amount == null || amount <= 0) {
                          return 'Invalid';
                        }
                        if (_shareType == 'PERCENT' && amount > 100) {
                          return 'Max 100%';
                        }
                        return null;
                      },
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
      actions: [
        TextButton(
          onPressed: _adding ? null : () => Navigator.pop(context),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: _adding ? null : _addMember,
          child: _adding
              ? const SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('Add Member'),
        ),
      ],
    );
  }
}
