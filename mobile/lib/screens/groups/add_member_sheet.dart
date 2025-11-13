import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../services/api_service.dart';

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
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _shareValueController = TextEditingController(text: '0');
  final _venmoUsernameController = TextEditingController();
  final _cashTagController = TextEditingController();
  final _searchController = TextEditingController();

  late final TabController _tabController;
  String _preferredChannel = 'VENMO';
  String _shareType = 'AMOUNT';
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
    _tabController = TabController(length: 2, vsync: this);
    _searchController.addListener(_filterExistingMembers);
    _loadExistingMembers();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _shareValueController.dispose();
    _venmoUsernameController.dispose();
    _cashTagController.dispose();
    super.dispose();
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
        groupsIterable = [
          ...groupList(payload['owned_groups']),
          ...groupList(payload['member_groups']),
        ];
      } else if (payload is List) {
        groupsIterable = payload;
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

  Future<void> _submitNewMember() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    final email = _emailController.text.trim();
    final phone = _phoneController.text.trim();
    final name = _nameController.text.trim();

    if (email.isEmpty && phone.isEmpty) {
      setState(() {
        _errorNew = 'Enter an email or phone number';
      });
      return;
    }

    setState(() {
      _submittingNew = true;
      _errorNew = null;
    });

    final payload = <String, dynamic>{
      'preferred_channel': _preferredChannel,
      'share_type': _shareType,
      'share_value': double.tryParse(_shareValueController.text) ?? 0,
    };

    if (email.isNotEmpty) payload['email'] = email;
    if (phone.isNotEmpty) payload['phone'] = phone;
    if (name.isNotEmpty) payload['name'] = name;

    if (_preferredChannel == 'VENMO') {
      payload['venmo_username'] = _venmoUsernameController.text.trim();
    } else if (_preferredChannel == 'CASH_APP') {
      final cashTag = _cashTagController.text.trim();
      if (cashTag.isNotEmpty) payload['cash_tag'] = cashTag;
    }

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
        debugPrint('Add member error: ${e.response?.data}');
      }
      if (!mounted) return;
      setState(() {
        _errorNew = e.response?.data?['message'] as String? ??
            'Failed to add member';
        _submittingNew = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected add member error: $e');
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
                    'Add Group Member',
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
            SizedBox(
              height: 420,
              child: TabBarView(
                controller: _tabController,
                children: [
                  _buildNewMemberForm(),
                  _buildExistingMembersView(),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildNewMemberForm() {
    return SingleChildScrollView(
      child: Form(
        key: _formKey,
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
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              key: ValueKey('new_channel_$_preferredChannel'),
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
              onChanged: _submittingNew
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
                enabled: !_submittingNew,
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
                enabled: !_submittingNew,
                decoration: const InputDecoration(
                  labelText: 'Cash tag',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (_preferredChannel == 'CASH_APP') {
                    if (value == null || value.trim().isEmpty) {
                      return 'Cash tag is required';
                    }
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
            ],
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
                onPressed: _submittingNew ? null : _submitNewMember,
                icon: _submittingNew
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.person_add_alt_rounded),
                label: Text(_submittingNew ? 'Adding...' : 'Add member'),
              ),
            ),
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
