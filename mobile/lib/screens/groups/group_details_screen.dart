import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../main.dart';
import '../../pay_screen.dart';
import '../../widgets/payment_request_card.dart';
import 'group_form_sheet.dart';

/// Group details screen showing members and payment requests.
///
/// Displays group information, member list, and owner actions such as nudging
/// members or confirming payments.
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
        print('Loaded group: ${group['name']}');
      }
    } on DioException catch (e) {
      if (Env.debug) {
        print('Error loading group: ${e.response?.data}');
      }

      if (!mounted) return;

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Failed to load group details';
        _loading = _groupData == null;
      });
    } catch (e) {
      if (Env.debug) {
        print('Unexpected error loading group: $e');
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
        print('Nudge error: ${e.response?.data}');
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
        print('Unexpected nudge error: $e');
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
        print('Confirm error: ${e.response?.data}');
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
        print('Unexpected confirm error: $e');
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_groupData?['name'] as String? ?? 'Group Details'),
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
          _buildGroupInfoCard(),
          const SizedBox(height: 16),
          _buildMembersSection(),
          const SizedBox(height: 16),
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
                if (_isOwner)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
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
                        color:
                            Theme.of(context).colorScheme.onPrimaryContainer,
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
            if (ownerName != null && ownerName.isNotEmpty) ...[
              const SizedBox(height: 8),
              _buildInfoRow(
                icon: Icons.person_outline,
                label: 'Owner',
                value: ownerName,
              ),
            ],
            if (totalAmount != null) ...[
              const SizedBox(height: 8),
              _buildInfoRow(
                icon: Icons.attach_money,
                label: 'Total per cycle',
                value:
                    '\$${(totalAmount is num ? totalAmount : num.tryParse('$totalAmount'))?.toStringAsFixed(2) ?? totalAmount.toString()}',
              ),
            ],
            const SizedBox(height: 12),
            Wrap(
              spacing: 16,
              runSpacing: 8,
              children: [
                _buildInfoChip(
                  icon: Icons.schedule,
                  label: _friendlyCadence(cadence),
                ),
                if (dueDay != null)
                  _buildInfoChip(
                    icon: Icons.calendar_today,
                    label: 'Due day ${dueDay.toString()}',
                  ),
                if (graceDays != null)
                  _buildInfoChip(
                    icon: Icons.hourglass_bottom,
                    label: '${graceDays.toString()} grace day${graceDays == 1 ? '' : 's'}',
                  ),
                if (timezone != null)
                  _buildInfoChip(
                    icon: Icons.public,
                    label: timezone,
                  ),
              ],
            ),
          ],
        ),
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
        const SizedBox(width: 6),
        Text(
          '$label: ',
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Colors.grey[700],
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

  Widget _buildInfoChip({
    required IconData icon,
    required String label,
  }) {
    return Chip(
      avatar: Icon(icon, size: 18),
      label: Text(label),
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
                  child: Center(child: Text('No members yet')),
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
    final user = member['user'] as Map<String, dynamic>?;
    final name = user?['name'] as String? ??
        member['name'] as String? ??
        'Unknown';
    final email = user?['email'] as String? ??
        member['email'] as String?;
    final status = member['status'] as String? ?? 'active';
    final channel = (member['preferred_channel'] as String? ?? 'VENMO')
        .replaceAll('_', ' ')
        .toLowerCase();
    final share = _formatMemberShare(member);

    final subtitleLines = <String>[];
    if (email != null && email.isNotEmpty) {
      subtitleLines.add(email);
    }
    final handle = _formatPaymentHandle(member);
    if (handle != null) {
      subtitleLines.add(handle);
    }
    subtitleLines.add('Channel: $channel • Status: $status');

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
      title: Text(name),
      subtitle: Text(subtitleLines.join('\n')),
      trailing: share != null
          ? Text(
              share,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
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

  String? _formatPaymentHandle(Map<String, dynamic> member) {
    return member['venmo_username'] as String? ??
        member['cash_tag'] as String? ??
        member['cash_phone'] as String? ??
        member['cash_email'] as String?;
  }

  Widget _buildPaymentRequestsSection() {
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
        if (_paymentRequests.isEmpty)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                children: [
                  Icon(
                    Icons.payment_outlined,
                    size: 48,
                    color: Colors.grey.shade300,
                  ),
                  const SizedBox(height: 8),
                  const Text('No payment requests yet'),
                  const SizedBox(height: 4),
                  if (_isOwner)
                    const Text(
                      'Create a cycle to generate payment requests.',
                      textAlign: TextAlign.center,
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
