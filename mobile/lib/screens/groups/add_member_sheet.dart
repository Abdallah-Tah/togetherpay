import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../main.dart';
import '../../utils/theme.dart';

/// Maps backend share type values to the dropdown options we support.
String _normalizeShareType(dynamic raw) {
  if (raw is String) {
    switch (raw.toUpperCase()) {
      case 'FIXED':
      case 'AMOUNT':
        return 'AMOUNT';
      case 'PERCENT':
        return 'PERCENT';
    }
  }
  return 'AMOUNT';
}

/// Normalizes preferred payment channel values returned by the API.
String _normalizePreferredChannel(dynamic raw) {
  if (raw is String) {
    switch (raw.toUpperCase()) {
      case 'VENMO':
        return 'VENMO';
      case 'CASH_APP':
      case 'CASHAPP':
      case 'CASH-APP':
        return 'CASH_APP';
    }
  }
  return 'CASH_APP';
}

String _extractSuccessMessage(dynamic payload, String fallback) {
  if (payload is Map) {
    final value = payload['message'];
    if (value is String && value.trim().isNotEmpty) {
      return value;
    }
  }
  return fallback;
}

/// Enhanced Add Member Sheet with Apple-level UX
///
/// Features:
/// - Tab-based UI: New Member vs Existing Member
/// - Search existing members from other groups
/// - Smart pre-filling of payment details
/// - Better validation and error messages
class AddMemberSheet extends StatefulWidget {
  final int groupId;
  final List<int> existingMemberUserIds;

  const AddMemberSheet({
    super.key,
    required this.groupId,
    this.existingMemberUserIds = const [],
  });

  @override
  State<AddMemberSheet> createState() => _AddMemberSheetState();
}

class _AddMemberSheetState extends State<AddMemberSheet>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _loading = false;
  List<Map<String, dynamic>> _existingMembers = [];
  List<Map<String, dynamic>> _filteredMembers = [];
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadExistingMembers();
    _searchController.addListener(_filterMembers);
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadExistingMembers() async {
    setState(() {
      _loading = true;
    });

    try {
      // Fetch all groups to find existing members
      final response = await dio.get('/groups');

      if (Env.debug) {
        debugPrint('=== Loading Existing Members ===');
        debugPrint('Response status: ${response.statusCode}');
        debugPrint('Response data type: ${response.data.runtimeType}');
        debugPrint('Response data: ${response.data}');
      }

      // Handle both array and object responses
      List<dynamic> groups = [];
      if (response.data is List) {
        groups = response.data as List;
      } else if (response.data is Map) {
        // API returns {owned_groups: [...], member_groups: [...]}
        final ownedGroups = (response.data['owned_groups'] as List?) ?? [];
        final memberGroups = (response.data['member_groups'] as List?) ?? [];
        groups = [...ownedGroups, ...memberGroups];

        if (Env.debug) {
          debugPrint('Owned groups: ${ownedGroups.length}, Member groups: ${memberGroups.length}');
        }
      }

      if (Env.debug) {
        debugPrint('Total groups found: ${groups.length}');
      }

      final Set<String> uniqueMembers = {};
      final List<Map<String, dynamic>> members = [];

      for (final groupData in groups) {
        final group = groupData is Map<String, dynamic>
            ? groupData
            : Map<String, dynamic>.from(groupData as Map);

        final groupName = group['name'] as String? ?? 'Unknown Group';
        final groupMembers = (group['members'] as List?) ?? [];

        if (Env.debug) {
          debugPrint('Processing group: $groupName with ${groupMembers.length} members');
        }

        for (final memberData in groupMembers) {
          final member = memberData is Map<String, dynamic>
              ? memberData
              : Map<String, dynamic>.from(memberData as Map);

          final user = member['user'];
          if (user == null) {
            if (Env.debug) {
              debugPrint('Skipping member with no user data');
            }
            continue;
          }

          final userId = user['id'];
          if (userId == null) {
            if (Env.debug) {
              debugPrint('Skipping user with no ID');
            }
            continue;
          }

          // Skip if already in current group
          if (widget.existingMemberUserIds.contains(userId)) {
            if (Env.debug) {
              debugPrint('Skipping user $userId - already in current group');
            }
            continue;
          }

          final memberKey = '${user['email']}_$userId';
          if (!uniqueMembers.contains(memberKey)) {
            uniqueMembers.add(memberKey);
            members.add({
              'user': user,
              'member_data': member,
              'group_name': groupName,
            });

            if (Env.debug) {
              debugPrint('Added member: ${user['name']} (${user['email']})');
            }
          }
        }
      }

      if (Env.debug) {
        debugPrint('Total unique members found: ${members.length}');
      }

      setState(() {
        _existingMembers = members;
        _filteredMembers = members;
        _loading = false;
      });
    } catch (e, stackTrace) {
      if (Env.debug) {
        debugPrint('Error loading existing members: $e');
        debugPrint('Stack trace: $stackTrace');
      }
      setState(() {
        _loading = false;
      });
    }
  }

  void _filterMembers() {
    final query = _searchController.text.toLowerCase();
    setState(() {
      _filteredMembers = _existingMembers.where((member) {
        final name = (member['user']['name'] as String? ?? '').toLowerCase();
        final email = (member['user']['email'] as String? ?? '').toLowerCase();
        return name.contains(query) || email.contains(query);
      }).toList();
    });
  }

  Future<void> _addExistingMember(Map<String, dynamic> memberData) async {
    final user = memberData['user'];
    final existingMember = memberData['member_data'];

    // Show confirmation dialog with pre-filled details
    final confirmed = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (context) => _ExistingMemberConfirmDialog(
        user: user,
        existingMember: existingMember,
        groupId: widget.groupId,
      ),
    );

    if (confirmed != null && mounted) {
      Navigator.pop(context, confirmed);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          // Handle bar
          Container(
            margin: const EdgeInsets.only(top: 8),
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.grey.shade300,
              borderRadius: BorderRadius.circular(2),
            ),
          ),

          // Header
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
                const SizedBox(width: 8),
                Text(
                  'Add Member',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
              ],
            ),
          ),

          // Tabs
          Container(
            decoration: BoxDecoration(
              border: Border(
                bottom: BorderSide(
                  color: Colors.grey.shade200,
                  width: 1,
                ),
              ),
            ),
            child: TabBar(
              controller: _tabController,
              tabs: const [
                Tab(text: 'New Member'),
                Tab(text: 'Existing Member'),
              ],
            ),
          ),

          // Tab content
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                // New Member Tab
                _NewMemberTab(groupId: widget.groupId),

                // Existing Member Tab
                _ExistingMemberTab(
                  loading: _loading,
                  filteredMembers: _filteredMembers,
                  searchController: _searchController,
                  onMemberTap: _addExistingMember,
                  onRefresh: _loadExistingMembers,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// New Member Tab - For inviting new users
class _NewMemberTab extends StatefulWidget {
  final int groupId;

  const _NewMemberTab({required this.groupId});

  @override
  State<_NewMemberTab> createState() => _NewMemberTabState();
}

class _NewMemberTabState extends State<_NewMemberTab> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _shareValueController = TextEditingController();
  final _venmoUsernameController = TextEditingController();
  final _cashTagController = TextEditingController();
  final _cashPhoneController = TextEditingController();
  final _cashEmailController = TextEditingController();

  String _shareType = 'AMOUNT';
  String _preferredChannel = 'CASH_APP';
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
        'share_value': double.parse(_shareValueController.text),
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

      final body = response.data;
      if (body is! Map) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Connection error. Please try again.'),
              backgroundColor: Colors.red,
            ),
          );
        }
        return;
      }

      if (mounted) {
        final message = _extractSuccessMessage(body, 'Member added successfully');
        Navigator.pop(context, {
          'message': message,
          'refresh': true,
        });
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Add member error: ${e.response?.data}');
      }

      if (mounted) {
        String errorMessage = 'Failed to add member';

        // Check if response data is JSON (Map) or HTML (String)
        final responseData = e.response?.data;
        if (responseData is String && responseData.contains('<!DOCTYPE html>')) {
          errorMessage = 'Connection error. Please try again.';
          if (Env.debug) {
            debugPrint('Received HTML response instead of JSON. Possible ngrok redirect or server error.');
          }
        } else if (e.response?.statusCode == 404) {
          errorMessage = 'User not found. They need to register first.';
        } else if (e.response?.statusCode == 422 && responseData is Map) {
          final errors = responseData['errors'];
          if (errors is Map) {
            final emailErrors = errors['email'];
            if (emailErrors is List && emailErrors.isNotEmpty) {
              errorMessage = emailErrors[0].toString();
            } else {
              errorMessage = responseData['message'] as String? ?? errorMessage;
            }
          } else {
            errorMessage = responseData['message'] as String? ?? errorMessage;
          }
        } else if (responseData is Map) {
          errorMessage = responseData['message'] as String? ?? errorMessage;
        }

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(errorMessage),
            backgroundColor: Colors.red,
            duration: const Duration(seconds: 4),
            action: SnackBarAction(
              label: 'Dismiss',
              textColor: Colors.white,
              onPressed: () {},
            ),
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
    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Info card
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.blue.shade50,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.blue.shade200),
            ),
            child: Row(
              children: [
                Icon(Icons.info_outline, color: Colors.blue.shade700),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'The person must have a TogetherPay account to be added.',
                    style: TextStyle(color: Colors.blue.shade900, fontSize: 13),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 20),

          TextFormField(
            controller: _emailController,
            enabled: !_adding,
            keyboardType: TextInputType.emailAddress,
            decoration: const InputDecoration(
              labelText: 'Email Address',
              border: OutlineInputBorder(),
              prefixIcon: Icon(Icons.email_outlined),
              hintText: 'member@example.com',
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
              DropdownMenuItem(value: 'CASH_APP', child: Text('Cash App')),
              DropdownMenuItem(value: 'VENMO', child: Text('Venmo')),
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
                if (_preferredChannel == 'VENMO' &&
                    (value == null || value.trim().isEmpty)) {
                  return 'Venmo username is required';
                }
                return null;
              },
            ),
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
                hintText: '+1234567890',
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
                hintText: 'cashapp@example.com',
              ),
            ),
          ],

          const SizedBox(height: 24),

          // Share configuration
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
                    DropdownMenuItem(value: 'AMOUNT', child: Text('Fixed Amount')),
                    DropdownMenuItem(value: 'PERCENT', child: Text('Percentage')),
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
                  keyboardType: TextInputType.numberWithOptions(decimal: true),
                  decoration: InputDecoration(
                    labelText: _shareType == 'PERCENT' ? 'Percentage' : 'Amount',
                    border: const OutlineInputBorder(),
                    prefixText: _shareType == 'AMOUNT' ? '\$ ' : null,
                    suffixText: _shareType == 'PERCENT' ? '%' : null,
                    hintText: _shareType == 'AMOUNT' ? '25.00' : '33',
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return 'Required';
                    }
                    final num = double.tryParse(value);
                    if (num == null || num <= 0) {
                      return 'Invalid';
                    }
                    if (_shareType == 'PERCENT' && num > 100) {
                      return '> 100%';
                    }
                    return null;
                  },
                ),
              ),
            ],
          ),

          const SizedBox(height: 24),

          FilledButton.icon(
            onPressed: _adding ? null : _addMember,
            icon: _adding
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.person_add),
            label: Text(_adding ? 'Adding...' : 'Add Member'),
          ),
        ],
      ),
    );
  }
}

/// Existing Member Tab - For adding members from other groups
class _ExistingMemberTab extends StatelessWidget {
  final bool loading;
  final List<Map<String, dynamic>> filteredMembers;
  final TextEditingController searchController;
  final Function(Map<String, dynamic>) onMemberTap;
  final Future<void> Function() onRefresh;

  const _ExistingMemberTab({
    required this.loading,
    required this.filteredMembers,
    required this.searchController,
    required this.onMemberTap,
    required this.onRefresh,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // Search bar
        Padding(
          padding: const EdgeInsets.all(16),
          child: TextField(
            controller: searchController,
            decoration: InputDecoration(
              hintText: 'Search members...',
              prefixIcon: const Icon(Icons.search),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              filled: true,
              fillColor: Colors.grey.shade50,
            ),
          ),
        ),

        // Member list
        Expanded(
          child: loading
              ? const Center(child: CircularProgressIndicator())
              : filteredMembers.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.people_outline,
                            size: 64,
                            color: Colors.grey.shade300,
                          ),
                          const SizedBox(height: 16),
                          Text(
                            'No members found',
                            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                  color: Colors.grey,
                                ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Add new members from the other tab',
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                  color: Colors.grey,
                                ),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: onRefresh,
                      child: ListView.builder(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: filteredMembers.length,
                        itemBuilder: (context, index) {
                          final member = filteredMembers[index];
                          final user = member['user'];
                          final groupName = member['group_name'];

                          return Card(
                            margin: const EdgeInsets.only(bottom: 8),
                            child: ListTile(
                              leading: CircleAvatar(
                                backgroundColor:
                                    Theme.of(context).colorScheme.primaryContainer,
                                child: Text(
                                  _getInitials(user['name'] ?? '?'),
                                  style: TextStyle(
                                    color: Theme.of(context)
                                        .colorScheme
                                        .onPrimaryContainer,
                                  ),
                                ),
                              ),
                              title: Text(user['name'] ?? 'Unknown'),
                              subtitle: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(user['email'] ?? ''),
                                  const SizedBox(height: 4),
                                  Text(
                                    'From: $groupName',
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Theme.of(context).colorScheme.primary,
                                    ),
                                  ),
                                ],
                              ),
                              trailing: IconButton(
                                icon: const Icon(Icons.add_circle),
                                color: Theme.of(context).colorScheme.primary,
                                onPressed: () => onMemberTap(member),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
        ),
      ],
    );
  }

  String _getInitials(String name) {
    final trimmed = name.trim();
    if (trimmed.isEmpty) return '?';

    final parts = trimmed.split(RegExp(r'\s+'));
    final buffer = StringBuffer();

    if (parts.isNotEmpty && parts.first.isNotEmpty) {
      buffer.write(parts.first[0].toUpperCase());
    }

    if (parts.length > 1 && parts.last.isNotEmpty && parts.last != parts.first) {
      buffer.write(parts.last[0].toUpperCase());
    }

    return buffer.isEmpty ? '?' : buffer.toString();
  }
}

/// Confirmation dialog for adding existing member
class _ExistingMemberConfirmDialog extends StatefulWidget {
  final Map<String, dynamic> user;
  final Map<String, dynamic> existingMember;
  final int groupId;

  const _ExistingMemberConfirmDialog({
    required this.user,
    required this.existingMember,
    required this.groupId,
  });

  @override
  State<_ExistingMemberConfirmDialog> createState() =>
      _ExistingMemberConfirmDialogState();
}

class _ExistingMemberConfirmDialogState
    extends State<_ExistingMemberConfirmDialog> {
  final _formKey = GlobalKey<FormState>();
  final _shareValueController = TextEditingController();
  final _venmoUsernameController = TextEditingController();
  final _cashTagController = TextEditingController();
  final _cashPhoneController = TextEditingController();
  final _cashEmailController = TextEditingController();
  String _shareType = 'AMOUNT';
  String _preferredChannel = 'CASH_APP';
  bool _adding = false;

  @override
  void initState() {
    super.initState();
    // Pre-fill with existing member's share
    final existingShareValue = widget.existingMember['share_value'];
    _shareValueController.text = existingShareValue?.toString() ?? '0';
    _shareType = _normalizeShareType(widget.existingMember['share_type']);
    _preferredChannel =
        _normalizePreferredChannel(widget.existingMember['preferred_channel']);

    _venmoUsernameController.text =
        widget.existingMember['venmo_username']?.toString() ??
            widget.user['venmo_username']?.toString() ??
            '';
    _cashTagController.text =
        widget.existingMember['cash_tag']?.toString() ??
            widget.user['cashapp_tag']?.toString() ??
            '';
    _cashPhoneController.text =
        widget.existingMember['cash_phone']?.toString() ??
            widget.user['cash_phone']?.toString() ??
            widget.user['phone']?.toString() ??
            '';
    _cashEmailController.text =
        widget.existingMember['cash_email']?.toString() ??
            widget.user['cash_email']?.toString() ??
            '';
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

  Future<void> _confirmAdd() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _adding = true;
    });

    try {
      final data = <String, dynamic>{
        'share_type': _shareType,
        'share_value': double.parse(_shareValueController.text),
        'preferred_channel': _preferredChannel,
      };

      final email = widget.user['email'];
      if (email is String && email.trim().isNotEmpty) {
        data['email'] = email.trim();
      }

      final phone = widget.user['phone'];
      if (phone is String && phone.trim().isNotEmpty) {
        data['phone'] = phone.trim();
      }

      if (_preferredChannel == 'VENMO') {
        data['venmo_username'] = _venmoUsernameController.text.trim();
      } else if (_preferredChannel == 'CASH_APP') {
        final tag = _cashTagController.text.trim();
        final cashPhone = _cashPhoneController.text.trim();
        final cashEmail = _cashEmailController.text.trim();

        if (tag.isNotEmpty) {
          data['cash_tag'] = tag;
        }
        if (cashPhone.isNotEmpty) {
          data['cash_phone'] = cashPhone;
        }
        if (cashEmail.isNotEmpty) {
          data['cash_email'] = cashEmail;
        }
      }

      if (!data.containsKey('email') && !data.containsKey('phone')) {
        if (mounted) {
          setState(() {
            _adding = false;
          });
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Member is missing contact details. Add an email or phone.'),
              backgroundColor: Colors.red,
            ),
          );
        }
        return;
      }

      final response = await dio.post(
        '/groups/${widget.groupId}/members',
        data: data,
      );

      final body = response.data;
      if (body is! Map) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Connection error. Please try again.'),
              backgroundColor: Colors.red,
            ),
          );
        }
        return;
      }

      if (mounted) {
        final message = _extractSuccessMessage(body, 'Member added successfully');
        Navigator.pop(context, {
          'message': message,
          'refresh': true,
        });
      }
    } on DioException catch (e) {
      if (mounted) {
        String errorMessage = 'Failed to add member';
        final responseData = e.response?.data;

        if (responseData is Map<String, dynamic>) {
          String? firstError;
          final errors = responseData['errors'];
          if (errors is Map) {
            for (final value in errors.values) {
              if (value is List) {
                for (final item in value) {
                  if (item is String && item.trim().isNotEmpty) {
                    firstError = item;
                    break;
                  }
                }
              } else if (value is String && value.trim().isNotEmpty) {
                firstError = value;
              }

              if (firstError != null) {
                break;
              }
            }
          }

          errorMessage =
              firstError ?? responseData['message'] as String? ?? errorMessage;
        } else if (responseData is String && responseData.contains('<!DOCTYPE html>')) {
          errorMessage = 'Connection error. Please try again.';
        }

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(errorMessage),
            backgroundColor: Colors.red,
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
    final paymentChannel = _preferredChannel;
    final paymentDetail = () {
      if (paymentChannel == 'VENMO') {
        final venmo = _venmoUsernameController.text.trim();
        return venmo.isNotEmpty ? venmo : null;
      }
      final tag = _cashTagController.text.trim();
      if (tag.isNotEmpty) return tag;
      final phone = _cashPhoneController.text.trim();
      if (phone.isNotEmpty) return phone;
      final email = _cashEmailController.text.trim();
      if (email.isNotEmpty) return email;
      return null;
    }();

    return AlertDialog(
      title: const Text('Add Member'),
      content: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Member info
              Row(
                children: [
                  CircleAvatar(
                    backgroundColor: Theme.of(context).colorScheme.primaryContainer,
                    child: Text(
                      _getInitials(widget.user['name'] ?? '?'),
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onPrimaryContainer,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          widget.user['name'] ?? 'Unknown',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                        Text(
                          widget.user['email'] ?? '',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 16),

              // Payment info
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(
                          Icons.payment,
                          size: 16,
                          color: Colors.grey.shade600,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          paymentChannel == 'VENMO' ? 'Venmo' : 'Cash App',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                    if (paymentDetail != null) ...[
                      const SizedBox(height: 4),
                      Text(
                        paymentDetail,
                        style: const TextStyle(fontSize: 14),
                      ),
                    ],
                  ],
                ),
              ),

              const SizedBox(height: 16),

              DropdownButtonFormField<String>(
                value: _preferredChannel,
                decoration: const InputDecoration(
                  labelText: 'Payment Channel',
                  border: OutlineInputBorder(),
                ),
                items: const [
                  DropdownMenuItem(
                    value: 'CASH_APP',
                    child: Text('Cash App'),
                  ),
                  DropdownMenuItem(
                    value: 'VENMO',
                    child: Text('Venmo'),
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

              if (_preferredChannel == 'VENMO') ...[
                const SizedBox(height: 12),
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
                    if (_preferredChannel == 'VENMO' &&
                        (value == null || value.trim().isEmpty)) {
                      return 'Venmo username is required';
                    }
                    return null;
                  },
                ),
              ],

              if (_preferredChannel == 'CASH_APP') ...[
                const SizedBox(height: 12),
                TextFormField(
                  controller: _cashTagController,
                  enabled: !_adding,
                  decoration: const InputDecoration(
                    labelText: 'Cash Tag',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.tag),
                    hintText: '\$cashtag',
                  ),
                ),
                const SizedBox(height: 12),
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
                const SizedBox(height: 12),
                TextFormField(
                  controller: _cashEmailController,
                  enabled: !_adding,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    labelText: 'Cash App Email (optional)',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.email_outlined),
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return null;
                    }
                    final trimmed = value.trim();
                    final emailRegex = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');
                    if (!emailRegex.hasMatch(trimmed)) {
                      return 'Enter a valid email';
                    }
                    return null;
                  },
                ),
              ],

              const SizedBox(height: 16),

              // Share configuration
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
                        DropdownMenuItem(value: 'AMOUNT', child: Text('Fixed')),
                        DropdownMenuItem(value: 'PERCENT', child: Text('%')),
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
                      keyboardType:
                          const TextInputType.numberWithOptions(decimal: true),
                      decoration: InputDecoration(
                        labelText: 'Value',
                        border: const OutlineInputBorder(),
                        prefixText: _shareType == 'AMOUNT' ? '\$ ' : null,
                        suffixText: _shareType == 'PERCENT' ? '%' : null,
                      ),
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Required';
                        }
                        final num = double.tryParse(value);
                        if (num == null || num <= 0) {
                          return 'Invalid';
                        }
                        if (_shareType == 'PERCENT' && num > 100) {
                          return '> 100%';
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
          onPressed: _adding ? null : _confirmAdd,
          child: _adding
              ? const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Colors.white,
                  ),
                )
              : const Text('Add'),
        ),
      ],
    );
  }

  String _getInitials(String name) {
    final trimmed = name.trim();
    if (trimmed.isEmpty) return '?';

    final parts = trimmed.split(RegExp(r'\s+'));
    final buffer = StringBuffer();

    if (parts.isNotEmpty && parts.first.isNotEmpty) {
      buffer.write(parts.first[0].toUpperCase());
    }

    if (parts.length > 1 && parts.last.isNotEmpty && parts.last != parts.first) {
      buffer.write(parts.last[0].toUpperCase());
    }

    return buffer.isEmpty ? '?' : buffer.toString();
  }
}
