import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../main.dart';
import '../../utils/theme.dart';

/// Full-screen modal for creating and editing a group with animations
///
/// When `group` is provided, the form will update that group; otherwise a new
/// group is created. Returns the saved group payload on success.
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
    if (group == null) {
      return;
    }

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
        response = await dio.put('/groups/$groupId', data: payload);
      } else {
        response = await dio.post('/groups', data: payload);
      }

      final data = response.data as Map<String, dynamic>;
      final group = Map<String, dynamic>.from(
        data['group'] ?? data,
      );

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

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Failed to save group. Please try again.';
        _submitting = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected group save error: $e');
      }

      setState(() {
        _error = 'An unexpected error occurred.';
        _submitting = false;
      });
    }
  }

  double get _formProgress {
    int filledFields = 0;
    const totalFields = 7;

    if (_nameController.text.trim().isNotEmpty) filledFields++;
    if (_amountController.text.trim().isNotEmpty) filledFields++;
    if (_currency.isNotEmpty) filledFields++;
    if (_timezone.isNotEmpty) filledFields++;
    if (_cadence.isNotEmpty) filledFields++;
    if (_dueDay > 0) filledFields++;
    if (_graceDays >= 0) filledFields++;

    return filledFields / totalFields;
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    final size = MediaQuery.of(context).size;
    final isSmallScreen = size.width < 600;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        automaticallyImplyLeading: true,
        leading: IconButton(
          onPressed: _submitting ? null : () => Navigator.of(context).pop(),
          icon: const Icon(Icons.arrow_back_rounded),
          tooltip: 'Back',
        ),
        title: Text(widget.isEditing ? 'Edit Group' : 'Create Group'),
        centerTitle: false,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(4),
          child: TweenAnimationBuilder<double>(
            tween: Tween(begin: 0.0, end: _formProgress),
            duration: const Duration(milliseconds: 300),
            curve: Curves.easeInOut,
            builder: (context, value, child) {
              return LinearProgressIndicator(
                value: value,
                backgroundColor: Colors.transparent,
                minHeight: 4,
              );
            },
          ),
        ),
      ),
      body: Form(
        key: _formKey,
        onChanged: () {
          setState(() {}); // Trigger progress update
        },
        child: SingleChildScrollView(
          padding: EdgeInsets.only(
            left: AppTheme.spaceLarge,
            right: AppTheme.spaceLarge,
            top: AppTheme.spaceLarge,
            bottom: bottomInset + AppTheme.spaceLarge,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header with icon
              Row(
                children: [
                  Container(
                    width: 56,
                    height: 56,
                    decoration: BoxDecoration(
                      gradient: AppTheme.primaryGradient,
                      borderRadius:
                          BorderRadius.circular(AppTheme.radiusMedium),
                      boxShadow: [
                        BoxShadow(
                          color: AppTheme.primary.withValues(alpha:0.3),
                          blurRadius: 12,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.groups_rounded,
                      color: Colors.white,
                      size: 28,
                    ),
                  ),
                  const SizedBox(width: AppTheme.spaceMedium),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          widget.isEditing
                              ? 'Update group settings'
                              : 'Set up your group',
                          style:
                              Theme.of(context).textTheme.titleLarge?.copyWith(
                                    fontWeight: FontWeight.bold,
                                  ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          widget.isEditing
                              ? 'Modify billing details'
                              : 'Configure billing and schedule',
                          style:
                              Theme.of(context).textTheme.bodyMedium?.copyWith(
                                    color: Theme.of(context)
                                        .colorScheme
                                        .onSurface
                                        .withValues(alpha:0.6),
                                  ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppTheme.spaceXLarge),

              // Section: Basic Info
              _buildSectionHeader('Basic Information', Icons.info_outline),
              const SizedBox(height: AppTheme.spaceMedium),

              TextFormField(
                controller: _nameController,
                enabled: !_submitting,
                textCapitalization: TextCapitalization.words,
                decoration: InputDecoration(
                  labelText: 'Group name',
                  hintText: 'e.g. T-Mobile Family Plan',
                  prefixIcon: const Icon(Icons.group_outlined),
                  suffixIcon: _nameController.text.trim().isNotEmpty
                      ? const Icon(Icons.check_circle, color: Colors.green)
                      : null,
                ),
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'Please enter a group name';
                  }
                  return null;
                },
              ),
              const SizedBox(height: AppTheme.spaceMedium),

              TextFormField(
                controller: _amountController,
                enabled: !_submitting,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: const InputDecoration(
                  labelText: 'Total amount per cycle (optional)',
                  hintText: 'e.g. 120.00',
                  prefixIcon: Icon(Icons.attach_money_rounded),
                  helperText: 'Leave empty to calculate from member shares',
                ),
              ),
              const SizedBox(height: AppTheme.spaceXLarge),

              // Section: Schedule
              _buildSectionHeader('Billing Schedule', Icons.calendar_month_outlined),
              const SizedBox(height: AppTheme.spaceMedium),

              DropdownButtonFormField<String>(
                initialValue: _cadence,
                decoration: const InputDecoration(
                  labelText: 'Collection schedule',
                  prefixIcon: Icon(Icons.repeat_rounded),
                  helperText: 'How often to collect payments',
                ),
                items: _cadenceLabels.entries
                    .map(
                      (entry) => DropdownMenuItem(
                        value: entry.key,
                        child: Row(
                          children: [
                            Icon(
                              entry.key == 'MONTHLY'
                                  ? Icons.calendar_month
                                  : Icons.calendar_view_week,
                              size: 20,
                            ),
                            const SizedBox(width: 12),
                            Text(entry.value),
                          ],
                        ),
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
              const SizedBox(height: AppTheme.spaceMedium),

              // Due day and grace days - responsive layout
              isSmallScreen
                  ? Column(
                      children: [
                        DropdownButtonFormField<int>(
                          initialValue: _dueDay,
                          decoration: const InputDecoration(
                            labelText: 'Due day',
                            prefixIcon: Icon(Icons.event_rounded),
                            helperText: 'Payment due date',
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
                        const SizedBox(height: AppTheme.spaceMedium),
                        DropdownButtonFormField<int>(
                          initialValue: _graceDays,
                          decoration: const InputDecoration(
                            labelText: 'Grace days',
                            prefixIcon: Icon(Icons.hourglass_bottom_rounded),
                            helperText: 'Before overdue',
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
                      ],
                    )
                  : Row(
                      children: [
                        Expanded(
                          child: DropdownButtonFormField<int>(
                            initialValue: _dueDay,
                            decoration: const InputDecoration(
                              labelText: 'Due day',
                              prefixIcon: Icon(Icons.event_rounded),
                              helperText: 'Payment due date',
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
                        const SizedBox(width: AppTheme.spaceMedium),
                        Expanded(
                          child: DropdownButtonFormField<int>(
                            initialValue: _graceDays,
                            decoration: const InputDecoration(
                              labelText: 'Grace days',
                              prefixIcon: Icon(Icons.hourglass_bottom_rounded),
                              helperText: 'Before overdue',
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
              const SizedBox(height: AppTheme.spaceXLarge),

              // Section: Settings
              _buildSectionHeader('Settings', Icons.settings_outlined),
              const SizedBox(height: AppTheme.spaceMedium),

              // Currency and timezone - responsive layout
              isSmallScreen
                  ? Column(
                      children: [
                        DropdownButtonFormField<String>(
                          initialValue: _currency,
                          decoration: const InputDecoration(
                            labelText: 'Currency',
                            prefixIcon: Icon(Icons.monetization_on_outlined),
                          ),
                          items: const [
                            DropdownMenuItem(
                              value: 'USD',
                              child: Text('\$ USD - US Dollar'),
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
                        const SizedBox(height: AppTheme.spaceMedium),
                        DropdownButtonFormField<String>(
                          initialValue: _timezone,
                          decoration: const InputDecoration(
                            labelText: 'Timezone',
                            prefixIcon: Icon(Icons.public_rounded),
                          ),
                          items: _timezones.entries
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
                                    setState(() => _timezone = value);
                                  }
                                },
                        ),
                      ],
                    )
                  : Row(
                      children: [
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            initialValue: _currency,
                            decoration: const InputDecoration(
                              labelText: 'Currency',
                              prefixIcon: Icon(Icons.monetization_on_outlined),
                            ),
                            items: const [
                              DropdownMenuItem(
                                value: 'USD',
                                child: Text('\$ USD - US Dollar'),
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
                        const SizedBox(width: AppTheme.spaceMedium),
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            initialValue: _timezone,
                            decoration: const InputDecoration(
                              labelText: 'Timezone',
                              prefixIcon: Icon(Icons.public_rounded),
                            ),
                            items: _timezones.entries
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
                                      setState(() => _timezone = value);
                                    }
                                  },
                          ),
                        ),
                      ],
                    ),

              if (_error != null) ...[
                const SizedBox(height: AppTheme.spaceXLarge),
                AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  curve: Curves.easeInOut,
                  width: double.infinity,
                  padding: const EdgeInsets.all(AppTheme.spaceMedium),
                  decoration: BoxDecoration(
                    gradient: AppTheme.errorGradient,
                    borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.red.withValues(alpha:0.3),
                        blurRadius: 12,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.error_outline_rounded,
                        color: Colors.white,
                      ),
                      const SizedBox(width: AppTheme.spaceMedium),
                      Expanded(
                        child: Text(
                          _error!,
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              const SizedBox(height: AppTheme.spaceXLarge),

              // Submit button
              SizedBox(
                width: double.infinity,
                height: 56,
                child: FilledButton(
                  onPressed: _submitting ? null : _handleSubmit,
                  style: FilledButton.styleFrom(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
                    ),
                  ),
                  child: _submitting
                      ? const SizedBox(
                          width: 24,
                          height: 24,
                          child: CircularProgressIndicator(
                            strokeWidth: 3,
                            color: Colors.white,
                          ),
                        )
                      : Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              widget.isEditing
                                  ? Icons.check_circle_outline_rounded
                                  : Icons.add_circle_outline_rounded,
                            ),
                            const SizedBox(width: AppTheme.spaceSmall),
                            Text(
                              widget.isEditing
                                  ? 'Save Changes'
                                  : 'Create Group',
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title, IconData icon) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.primaryContainer,
            borderRadius: BorderRadius.circular(AppTheme.radiusSmall),
          ),
          child: Icon(
            icon,
            size: 20,
            color: Theme.of(context).colorScheme.primary,
          ),
        ),
        const SizedBox(width: AppTheme.spaceMedium),
        Text(
          title,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.primary,
              ),
        ),
      ],
    );
  }
}
