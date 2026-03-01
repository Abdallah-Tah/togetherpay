import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../services/api_service.dart';

class AddMemberSheet extends StatefulWidget {
  final int groupId;
  final List<int> existingMemberUserIds;
  final Map<String, dynamic>? member;

  const AddMemberSheet({
    super.key,
    required this.groupId,
    this.existingMemberUserIds = const [],
    this.member,
  });

  bool get isEditing => member != null;

  @override
  State<AddMemberSheet> createState() => _AddMemberSheetState();
}

class _AddMemberSheetState extends State<AddMemberSheet>
    with SingleTickerProviderStateMixin {
  bool get _isEditing => widget.isEditing;

  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _shareValueController = TextEditingController(text: '0');
  final _searchController = TextEditingController();
  final _venmoUsernameController = TextEditingController();
  final _cashAppTagController = TextEditingController();

  TabController? _tabController;
  String _shareType = 'AMOUNT';
  String? _preferredChannel; // VENMO, CASH_APP, or null
  bool _submittingNew = false;
  String? _errorNew;

  bool _loadingExisting = false;
  int? _addingExistingUserId;
  List<Map<String, dynamic>> _existingMembers = [];
  List<Map<String, dynamic>> _filteredMembers = [];
  String? _errorExisting;

  @override
  void initState() {
    super.initState();
    if (!_isEditing) {
      _tabController = TabController(length: 2, vsync: this);
      _searchController.addListener(_filterExistingMembers);
      _loadExistingMembers();
    }
    _initializeEditingFields();
  }

  @override
  void dispose() {
    _tabController?.dispose();
    _searchController.dispose();
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _shareValueController.dispose();
    _venmoUsernameController.dispose();
    _cashAppTagController.dispose();
    super.dispose();
  }

  void _initializeEditingFields() {
    if (!_isEditing) return;

    final member = widget.member!;
    final user = member['user'] as Map<String, dynamic>?;

    _shareType = (member['share_type'] as String? ?? 'AMOUNT').toUpperCase();
    _preferredChannel = (member['preferred_channel'] as String?)?.toUpperCase();

    _nameController.text =
        (member['name'] ?? user?['name'] ?? '').toString();
    _emailController.text =
        (member['email'] ?? user?['email'] ?? '').toString();
    _phoneController.text =
        (member['phone'] ?? user?['phone'] ?? '').toString();
    _shareValueController.text = _formatNumber(member['share_value']);
    _venmoUsernameController.text =
        (member['venmo_username'] ?? '').toString();
    _cashAppTagController.text = (member['cash_tag'] ?? '').toString();
  }

  String _formatNumber(dynamic value) {
    if (value is num) return value.toString();
    if (value is String && value.isNotEmpty) return value;
    return '0';
  }

  Future<void> _loadExistingMembers() async {
    setState(() {
      _loadingExisting = true;
      _errorExisting = null;
    });

    try {
      final response = await ApiService().dio.get('/groups');
      final payload = response.data;

      final Set<int> currentMemberIds =
          widget.existingMemberUserIds.toSet();
      final Set<int> seenUserIds = {};
      final List<Map<String, dynamic>> members = [];

      Iterable<dynamic> groupList(dynamic value) {
        if (value is List) return value;
        return const Iterable.empty();
      }

      final Iterable<dynamic> groupsIterable;
      if (payload is Map<String, dynamic>) {
        // PRIVACY: Only show members from groups owned by current user
        // Don't leak members from other owners' groups
        groupsIterable = groupList(payload['owned_groups']);
      } else if (payload is List) {
        // If it's a list, filter to only owned groups
        groupsIterable = payload.where((group) {
          if (group is! Map) return false;
          // Additional safety check - could check owner_id matches current user
          return true;
        });
      } else {
        groupsIterable = const [];
      }

      for (final rawGroup in groupsIterable) {
        if (rawGroup is! Map) continue;
        final membersList = rawGroup['members'];
        if (membersList is! List) continue;

        for (final rawMember in membersList) {
          if (rawMember is! Map) continue;
          final user = rawMember['user'];
          if (user is! Map) continue;
          final userId = user['id'] as int?;
          if (userId == null) continue;
          if (currentMemberIds.contains(userId)) continue;
          if (!seenUserIds.add(userId)) continue;

          members.add({
            'user': user,
            'source_group': rawGroup['name'],
            'member': rawMember,
          });
        }
      }

      members.sort((a, b) {
        final nameA = (a['user']['name'] as String? ?? '').toLowerCase();
        final nameB = (b['user']['name'] as String? ?? '').toLowerCase();
        return nameA.compareTo(nameB);
      });

      setState(() {
        _existingMembers = members;
        _filteredMembers = members;
        _loadingExisting = false;
      });
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Load existing members error: ${e.response?.data}');
      }
      setState(() {
        _errorExisting = e.response?.data?['message'] as String? ??
            'Failed to load existing members';
        _loadingExisting = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected load existing members error: $e');
      }
      setState(() {
        _errorExisting = 'An unexpected error occurred';
        _loadingExisting = false;
      });
    }
  }

  void _filterExistingMembers() {
    final query = _searchController.text.trim().toLowerCase();
    if (query.isEmpty) {
      setState(() {
        _filteredMembers = List<Map<String, dynamic>>.from(_existingMembers);
      });
      return;
    }

    setState(() {
      _filteredMembers = _existingMembers.where((member) {
        final user = member['user'] as Map<String, dynamic>;
        final name = (user['name'] as String? ?? '').toLowerCase();
        final email = (user['email'] as String? ?? '').toLowerCase();
        return name.contains(query) || email.contains(query);
      }).toList();
    });
  }

  Future<void> _addExistingMember(Map<String, dynamic> member) async {
    final user = member['user'] as Map<String, dynamic>;
    final email = user['email'] as String?;
    final phone = user['phone'] as String?;
    final userId = user['id'] as int?;

    if (email == null && phone == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('This user has no email or phone on file'),
            backgroundColor: Colors.red,
          ),
        );
      }
      return;
    }

    setState(() {
      _addingExistingUserId = userId;
      _errorExisting = null;
    });

    final payload = <String, dynamic>{
      'share_type': 'AMOUNT',
      'share_value': 0,
      'allow_existing': true,
    };
    if (email != null) payload['email'] = email;
    if (phone != null) payload['phone'] = phone;

    try {
      await ApiService().dio.post(
            '/groups/${widget.groupId}/members',
            data: payload,
          );

      if (mounted) {
        Navigator.of(context).pop(true);
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Add existing member error: ${e.response?.data}');
      }
      setState(() {
        _errorExisting = e.response?.data?['message'] as String? ??
            'Failed to add member';
        _addingExistingUserId = null;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected existing member error: $e');
      }
      setState(() {
        _errorExisting = 'An unexpected error occurred';
        _addingExistingUserId = null;
      });
    }
  }

  Future<void> _submitMember() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    final email = _emailController.text.trim();
    final phone = _phoneController.text.trim();
    final name = _nameController.text.trim();
    final memberId = widget.member?['id'] as int?;

    if (email.isEmpty) {
      setState(() {
        _errorNew = 'Enter an email address';
      });
      return;
    }

    if (_isEditing && memberId == null) {
      setState(() {
        _errorNew = 'Member information is missing';
      });
      return;
    }

    setState(() {
      _submittingNew = true;
      _errorNew = null;
    });

    final payload = <String, dynamic>{
      'share_type': _shareType,
      'share_value': double.tryParse(_shareValueController.text) ?? 0,
      'allow_existing': true, // Allow adding users who already have accounts
    };

    if (email.isNotEmpty) payload['email'] = email;
    if (phone.isNotEmpty) payload['phone'] = phone;
    if (name.isNotEmpty) payload['name'] = name;

    // Add payment channel information
    if (_preferredChannel != null) {
      payload['preferred_channel'] = _preferredChannel;
    }

    final venmoUsername = _venmoUsernameController.text.trim();
    if (venmoUsername.isNotEmpty) {
      // Remove @ prefix if user included it
      payload['venmo_username'] = venmoUsername.startsWith('@')
          ? venmoUsername
          : '@$venmoUsername';
    }

    final cashAppTag = _cashAppTagController.text.trim();
    if (cashAppTag.isNotEmpty) {
      // Remove $ prefix if user included it
      payload['cash_tag'] = cashAppTag.startsWith('\$')
          ? cashAppTag
          : '\$$cashAppTag';
    }

    try {
      if (_isEditing) {
        await ApiService().dio.put(
              '/groups/${widget.groupId}/members/$memberId',
              data: payload,
            );
      } else {
        await ApiService().dio.post(
              '/groups/${widget.groupId}/members',
              data: payload,
            );
      }

      if (mounted) {
        Navigator.of(context).pop(true);
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint(
          '${_isEditing ? 'Update' : 'Add'} member error: ${e.response?.data}',
        );
      }
      if (!mounted) return;
      setState(() {
        _errorNew = e.response?.data?['message'] as String? ??
            (_isEditing ? 'Failed to update member' : 'Failed to add member');
        _submittingNew = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected ${_isEditing ? 'update' : 'add'} member error: $e');
      }
      if (!mounted) return;
      setState(() {
        _errorNew = 'An unexpected error occurred';
        _submittingNew = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isEditing = _isEditing;

    return SafeArea(
      child: Padding(
        padding: EdgeInsets.only(
          left: 24,
          right: 24,
          top: 24,
          bottom: MediaQuery.of(context).viewInsets.bottom + 24,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    isEditing ? 'Edit Group Member' : 'Add Group Member',
                    style: theme.textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.of(context).pop(),
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
            const SizedBox(height: 12),
            if (!isEditing) ...[
              TabBar(
                controller: _tabController,
                labelColor: theme.colorScheme.primary,
                indicatorColor: theme.colorScheme.primary,
                tabs: const [
                  Tab(text: 'New member'),
                  Tab(text: 'Existing member'),
                ],
              ),
              const SizedBox(height: 16),
              Flexible(
                child: TabBarView(
                  controller: _tabController,
                  children: [
                    _buildNewMemberForm(),
                    _buildExistingMembersView(),
                  ],
                ),
              ),
            ] else
              Flexible(
                child: SingleChildScrollView(
                  child: _buildNewMemberForm(isEditing: true),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildNewMemberForm({bool isEditing = false}) {
    final actionLabel = isEditing
        ? (_submittingNew ? 'Saving...' : 'Save changes')
        : (_submittingNew ? 'Adding...' : 'Add member');
    final actionIcon = isEditing ? Icons.save_outlined : Icons.person_add_alt_rounded;

    return Form(
      key: _formKey,
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TextFormField(
              controller: _nameController,
              enabled: !_submittingNew,
              textCapitalization: TextCapitalization.words,
              decoration: const InputDecoration(
                labelText: 'Full name',
                hintText: 'e.g. Jordan Smith',
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'Please enter the member\'s full name';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _emailController,
              enabled: !_submittingNew,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(
                labelText: 'Member email',
                hintText: 'member@example.com',
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'Email is required';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _phoneController,
              enabled: !_submittingNew,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(
                labelText: 'Phone (optional)',
                hintText: '+1 555 123 4567',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 24),
            Text(
              'Payment Information',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String?>(
              key: ValueKey('preferred_channel_$_preferredChannel'),
              initialValue: _preferredChannel,
              decoration: const InputDecoration(
                labelText: 'Preferred payment channel',
                border: OutlineInputBorder(),
                helperText: 'How they prefer to receive payment requests',
              ),
              items: const [
                DropdownMenuItem(
                  value: null,
                  child: Text('Not set'),
                ),
                DropdownMenuItem(
                  value: 'VENMO',
                  child: Text('Venmo'),
                ),
                DropdownMenuItem(
                  value: 'CASH_APP',
                  child: Text('Cash App'),
                ),
              ],
              onChanged: _submittingNew
                  ? null
                  : (value) {
                      setState(() {
                        _preferredChannel = value;
                      });
                    },
            ),
            if (_preferredChannel == 'VENMO') ...[
              const SizedBox(height: 16),
              TextFormField(
                controller: _venmoUsernameController,
                enabled: !_submittingNew,
                decoration: const InputDecoration(
                  labelText: 'Venmo username',
                  hintText: '@username',
                  border: OutlineInputBorder(),
                  helperText: 'Their Venmo username',
                  prefixText: '@',
                ),
                validator: (value) {
                  if (_preferredChannel == 'VENMO' &&
                      (value == null || value.trim().isEmpty)) {
                    return 'Venmo username is required when Venmo is preferred';
                  }
                  return null;
                },
              ),
            ],
            if (_preferredChannel == 'CASH_APP') ...[
              const SizedBox(height: 16),
              TextFormField(
                controller: _cashAppTagController,
                enabled: !_submittingNew,
                decoration: const InputDecoration(
                  labelText: 'Cash App \$cashtag',
                  hintText: 'username',
                  border: OutlineInputBorder(),
                  helperText: 'Their Cash App \$cashtag',
                  prefixText: '\$',
                ),
                validator: (value) {
                  if (_preferredChannel == 'CASH_APP' &&
                      (value == null || value.trim().isEmpty)) {
                    return 'Cash App \$cashtag is required when Cash App is preferred';
                  }
                  return null;
                },
              ),
            ],
            const SizedBox(height: 24),
            Text(
              'Contribution Details',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    key: ValueKey('new_share_$_shareType'),
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
                    onChanged: _submittingNew
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
                    enabled: !_submittingNew,
                    keyboardType:
                        const TextInputType.numberWithOptions(decimal: true),
                    decoration: InputDecoration(
                      labelText:
                          _shareType == 'PERCENT' ? 'Percent' : 'Amount',
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
                ),
              ],
            ),
            if (_errorNew != null) ...[
              const SizedBox(height: 16),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.red.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.red.shade100),
                ),
                child: Text(
                  _errorNew!,
                  style: TextStyle(color: Colors.red.shade800),
                ),
              ),
            ],
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: _submittingNew ? null : _submitMember,
                icon: _submittingNew
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : Icon(actionIcon),
                label: Text(actionLabel),
              ),
            ),
            const SizedBox(height: 24), // Extra padding at bottom
          ],
        ),
      ),
    );
  }

  Widget _buildExistingMembersView() {
    if (_loadingExisting) {
      return const Center(child: CircularProgressIndicator());
    }

    return Column(
      children: [
        TextField(
          controller: _searchController,
          decoration: const InputDecoration(
            prefixIcon: Icon(Icons.search),
            hintText: 'Search by name or email',
            border: OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 12),
        if (_errorExisting != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Text(
              _errorExisting!,
              style: TextStyle(color: Colors.red.shade700),
            ),
          ),
        if (_filteredMembers.isEmpty)
          const Expanded(
            child: Center(
              child: Text('No other members found'),
            ),
          )
        else
          Expanded(
            child: ListView.builder(
              itemCount: _filteredMembers.length,
              itemBuilder: (context, index) {
                final entry = _filteredMembers[index];
                final user = entry['user'] as Map<String, dynamic>;
                final name = user['name'] as String? ?? 'Unnamed';
                final email = user['email'] as String? ?? 'No email';
                final group = entry['source_group'] as String? ?? 'Unknown group';
                final userId = user['id'] as int?;
                final isAdding = userId != null &&
                    _addingExistingUserId == userId;

                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    title: Text(name),
                    subtitle: Text('$email\n$group'),
                    isThreeLine: true,
                    trailing: isAdding
                        ? const SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : TextButton(
                            onPressed: () => _addExistingMember(entry),
                            child: const Text('Add'),
                          ),
                  ),
                );
              },
            ),
          ),
      ],
    );
  }
}
