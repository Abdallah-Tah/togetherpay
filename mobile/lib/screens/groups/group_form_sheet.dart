import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../services/api_service.dart';

/// Bottom sheet used for creating and editing a group. Pops with the saved group
/// payload when successful so the caller can refresh its local state.
class GroupFormSheet extends StatefulWidget {
  final Map<String, dynamic>? group;

  const GroupFormSheet({
    super.key,
    this.group,
  });

  bool get isEditing => group != null;

  @override
  State<GroupFormSheet> createState() => _GroupFormSheetState();
}

class _GroupFormSheetState extends State<GroupFormSheet> {
  final Dio _dio = ApiService().dio;
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _amountController = TextEditingController();

  String _currency = 'USD';
  String _timezone = 'America/New_York';
  String _cadence = 'MONTHLY';
  int _dueDay = 1;
  int _graceDays = 7;

  bool _submitting = false;
  String? _error;

  static const _timezones = <String, String>{
    'America/New_York': 'Eastern',
    'America/Chicago': 'Central',
    'America/Denver': 'Mountain',
    'America/Los_Angeles': 'Pacific',
  };

  static const _cadenceLabels = <String, String>{
    'MONTHLY': 'Monthly',
    'EVERY_4_WEEKS': 'Every 4 Weeks',
  };

  @override
  void initState() {
    super.initState();
    _hydrateFromGroup();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _amountController.dispose();
    super.dispose();
  }

  void _hydrateFromGroup() {
    final group = widget.group;
    if (group == null) return;

    _nameController.text = (group['name'] as String?) ?? '';
    _currency = (group['currency'] as String?) ?? _currency;
    _timezone = (group['timezone'] as String?) ?? _timezone;
    _cadence = (group['cadence'] as String?) ?? _cadence;
    _dueDay = (group['due_day'] as int?) ?? _dueDay;
    _graceDays = (group['grace_days'] as int?) ?? _graceDays;

    final totalAmount = group['total_amount'] ??
        (group['total_amount_cents'] != null
            ? (group['total_amount_cents'] as num) / 100
            : null);

    if (totalAmount != null) {
      _amountController.text =
          (totalAmount is num ? totalAmount : num.tryParse('$totalAmount'))
                  ?.toStringAsFixed(2) ??
              '';
    }
  }

  Future<void> _handleSubmit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    final payload = <String, dynamic>{
      'name': _nameController.text.trim(),
      'currency': _currency,
      'timezone': _timezone,
      'cadence': _cadence,
      'due_day': _dueDay,
      'grace_days': _graceDays,
    };

    final amountText = _amountController.text.trim();
    if (amountText.isNotEmpty) {
      final parsed = num.tryParse(amountText);
      if (parsed != null) {
        payload['total_amount'] = parsed;
      }
    }

    try {
      Response<dynamic> response;
      if (widget.isEditing) {
        final groupId = widget.group?['id'];
        response = await _dio.put('/groups/$groupId', data: payload);
      } else {
        response = await _dio.post('/groups', data: payload);
      }

      final data = response.data as Map<String, dynamic>;
      final group = Map<String, dynamic>.from(data['group'] ?? data);

      if (widget.isEditing && widget.group?['is_owner'] != null) {
        group['is_owner'] = widget.group!['is_owner'];
      }

      if (mounted) {
        Navigator.of(context).pop(group);
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Group save error: ${e.response?.data}');
      }

      if (!mounted) return;

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Failed to save group. Please try again.';
        _submitting = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected group save error: $e');
      }

      if (!mounted) return;

      setState(() {
        _error = 'An unexpected error occurred.';
        _submitting = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;

    return Padding(
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        bottom: bottomInset + 24,
        top: 24,
      ),
      child: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      widget.isEditing ? 'Edit Group' : 'Create Group',
                      style: Theme.of(context)
                          .textTheme
                          .headlineSmall
                          ?.copyWith(fontWeight: FontWeight.bold),
                    ),
                  ),
                  IconButton(
                    onPressed:
                        _submitting ? null : () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.close),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _nameController,
                enabled: !_submitting,
                decoration: const InputDecoration(
                  labelText: 'Group name',
                  hintText: 'e.g. T-Mobile Family Plan',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'Please enter a group name';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _amountController,
                enabled: !_submitting,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: const InputDecoration(
                  labelText: 'Total amount per cycle (optional)',
                  hintText: 'e.g. 120.00',
                  prefixText: '\$',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      isExpanded: true,
                      initialValue: _currency,
                      decoration: const InputDecoration(
                        labelText: 'Currency',
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(
                          value: 'USD',
                          child: Text('USD'),
                        ),
                      ],
                      onChanged: _submitting
                          ? null
                          : (value) {
                              if (value != null) {
                                setState(() => _currency = value);
                              }
                            },
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      isExpanded: true,
                      initialValue: _timezone,
                      decoration: const InputDecoration(
                        labelText: 'Timezone',
                        border: OutlineInputBorder(),
                      ),
                      items: _timezones.entries
                          .map(
                            (entry) => DropdownMenuItem(
                              value: entry.key,
                              child: Text('${entry.value} (${entry.key})'),
                            ),
                          )
                          .toList(),
                      onChanged: _submitting
                          ? null
                          : (value) {
                              if (value != null) {
                                setState(() => _timezone = value);
                              }
                            },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                isExpanded: true,
                initialValue: _cadence,
                decoration: const InputDecoration(
                  labelText: 'Collection schedule',
                  border: OutlineInputBorder(),
                ),
                items: _cadenceLabels.entries
                    .map(
                      (entry) => DropdownMenuItem(
                        value: entry.key,
                        child: Text(entry.value),
                      ),
                    )
                    .toList(),
                onChanged: _submitting
                    ? null
                    : (value) {
                        if (value != null) {
                          setState(() => _cadence = value);
                        }
                      },
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: DropdownButtonFormField<int>(
                      isExpanded: true,
                      initialValue: _dueDay,
                      decoration: const InputDecoration(
                        labelText: 'Due day',
                        helperText: 'Cycle payment due date',
                        border: OutlineInputBorder(),
                      ),
                      items: List.generate(
                        28,
                        (index) => DropdownMenuItem(
                          value: index + 1,
                          child: Text('Day ${index + 1}'),
                        ),
                      ),
                      onChanged: _submitting
                          ? null
                          : (value) {
                              if (value != null) {
                                setState(() => _dueDay = value);
                              }
                            },
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: DropdownButtonFormField<int>(
                      isExpanded: true,
                      initialValue: _graceDays,
                      decoration: const InputDecoration(
                        labelText: 'Grace days',
                        helperText: 'Days before marking overdue',
                        border: OutlineInputBorder(),
                      ),
                      items: List.generate(
                        21,
                        (index) => DropdownMenuItem(
                          value: index,
                          child: Text('$index day${index == 1 ? '' : 's'}'),
                        ),
                      ),
                      onChanged: _submitting
                          ? null
                          : (value) {
                              if (value != null) {
                                setState(() => _graceDays = value);
                              }
                            },
                    ),
                  ),
                ],
              ),
              if (_error != null) ...[
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
                    _error!,
                    style: TextStyle(
                      color: Colors.red.shade900,
                    ),
                  ),
                ),
              ],
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: _submitting ? null : _handleSubmit,
                  icon: _submitting
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.save),
                  label: Text(
                    widget.isEditing ? 'Save changes' : 'Create group',
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
