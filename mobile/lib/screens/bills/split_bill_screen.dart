import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../utils/theme.dart';

/// Split a bill screen with customizable amounts per person
///
/// Allows users to split a bill between multiple people with custom or even splits
class SplitBillScreen extends StatefulWidget {
  final double? initialAmount;

  const SplitBillScreen({
    super.key,
    this.initialAmount,
  });

  @override
  State<SplitBillScreen> createState() => _SplitBillScreenState();
}

class _SplitBillScreenState extends State<SplitBillScreen> {
  late double _totalBill;
  bool _splitEvenly = false;
  final List<BillPerson> _people = [];

  @override
  void initState() {
    super.initState();
    _totalBill = widget.initialAmount ?? 120.00;

    // Add default people
    _people.addAll([
      BillPerson(
        id: '1',
        name: 'You',
        avatar: Icons.person,
        amount: 30.00,
      ),
      BillPerson(
        id: '2',
        name: 'Jane Doe',
        avatar: Icons.person_outline,
        amount: 30.00,
      ),
      BillPerson(
        id: '3',
        name: 'John Smith',
        avatar: Icons.person_outline,
        amount: 60.00,
      ),
    ]);
  }

  double get _totalAllocated {
    return _people.fold(0.0, (sum, person) => sum + person.amount);
  }

  double get _remaining {
    return _totalBill - _totalAllocated;
  }

  void _splitEvenlyToggled(bool value) {
    setState(() {
      _splitEvenly = value;
      if (_splitEvenly) {
        final evenAmount = _totalBill / _people.length;
        for (var person in _people) {
          person.amount = double.parse(evenAmount.toStringAsFixed(2));
        }
      }
    });
  }

  void _updatePersonAmount(String personId, double newAmount) {
    setState(() {
      final person = _people.firstWhere((p) => p.id == personId);
      person.amount = newAmount;
    });
  }

  void _addPerson() {
    setState(() {
      _people.add(
        BillPerson(
          id: DateTime.now().millisecondsSinceEpoch.toString(),
          name: 'New Person',
          avatar: Icons.person_add_outlined,
          amount: 0.00,
        ),
      );

      if (_splitEvenly) {
        _splitEvenlyToggled(true);
      }
    });
  }

  void _removePerson(String personId) {
    setState(() {
      _people.removeWhere((p) => p.id == personId);

      if (_splitEvenly) {
        _splitEvenlyToggled(true);
      }
    });
  }

  void _handleContinue() {
    if (_remaining != 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Please allocate the remaining \$${_remaining.abs().toStringAsFixed(2)}',
          ),
          backgroundColor: Theme.of(context).colorScheme.error,
        ),
      );
      return;
    }

    // TODO: Navigate to next step or create group
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Creating bill split...'),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: isDark
          ? const Color(0xFF1F2937)
          : const Color(0xFFF9FAFB),
      appBar: AppBar(
        title: const Text('Split a bill'),
        backgroundColor: isDark
            ? const Color(0xFF1F2937)
            : Colors.white,
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // Total bill card
                    _buildTotalBillCard(isDark),
                    const SizedBox(height: 24),

                    // Split with section
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Split with',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        TextButton(
                          onPressed: _addPerson,
                          child: const Text('Add Person'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // People list
                    ..._people.map((person) => _buildPersonRow(person, isDark)),

                    // Add another person button
                    _buildAddPersonButton(isDark),
                    const SizedBox(height: 24),

                    // Remaining amount
                    _buildRemainingCard(isDark),
                    const SizedBox(height: 24),

                    // Split evenly toggle
                    _buildSplitEvenlyToggle(isDark),
                  ],
                ),
              ),
            ),

            // Continue button
            _buildContinueButton(isDark),
          ],
        ),
      ),
    );
  }

  Widget _buildTotalBillCard(bool isDark) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: isDark
            ? const Color(0xFF374151)
            : Colors.white,
        borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
        boxShadow: isDark ? null : AppTheme.cardShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Total bill',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: isDark ? Colors.grey[400] : Colors.grey[600],
            ),
          ),
          const SizedBox(height: 8),
          Text(
            '\$${_totalBill.toStringAsFixed(2)}',
            style: Theme.of(context).textTheme.headlineLarge?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPersonRow(BillPerson person, bool isDark) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          // Avatar
          CircleAvatar(
            radius: 20,
            backgroundColor: isDark
                ? const Color(0xFF4B5563)
                : const Color(0xFFF3F4F6),
            child: Icon(
              person.avatar,
              size: 20,
              color: isDark
                  ? Colors.grey[300]
                  : Colors.grey[700],
            ),
          ),
          const SizedBox(width: 12),

          // Name
          Expanded(
            child: Text(
              person.name,
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                fontWeight: FontWeight.w500,
              ),
            ),
          ),

          // Amount input
          Container(
            width: 120,
            height: 48,
            decoration: BoxDecoration(
              color: isDark
                  ? const Color(0xFF1F2937)
                  : const Color(0xFFF9FAFB),
              borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
              border: Border.all(
                color: isDark
                    ? const Color(0xFF4B5563)
                    : const Color(0xFFE5E7EB),
              ),
            ),
            child: Row(
              children: [
                Padding(
                  padding: const EdgeInsets.only(left: 12),
                  child: Text(
                    '\$',
                    style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: isDark ? Colors.grey[400] : Colors.grey[600],
                    ),
                  ),
                ),
                Expanded(
                  child: TextField(
                    controller: TextEditingController(
                      text: person.amount.toStringAsFixed(2),
                    ),
                    textAlign: TextAlign.right,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    inputFormatters: [
                      FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
                    ],
                    decoration: const InputDecoration(
                      border: InputBorder.none,
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                    ),
                    onChanged: (value) {
                      final amount = double.tryParse(value) ?? 0.0;
                      _updatePersonAmount(person.id, amount);
                    },
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAddPersonButton(bool isDark) {
    return GestureDetector(
      onTap: _addPerson,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          border: Border.all(
            color: isDark
                ? const Color(0xFF4B5563)
                : const Color(0xFFE5E7EB),
            style: BorderStyle.solid,
            width: 2,
          ),
          borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.add,
              color: isDark ? Colors.grey[400] : Colors.grey[600],
            ),
            const SizedBox(width: 8),
            Text(
              'Add another person',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: isDark ? Colors.grey[400] : Colors.grey[600],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRemainingCard(bool isDark) {
    final isNegative = _remaining < 0;
    final isZero = _remaining == 0;

    Color remainingColor;
    if (isZero) {
      remainingColor = AppTheme.primary;
    } else if (isNegative) {
      remainingColor = Theme.of(context).colorScheme.error;
    } else {
      remainingColor = const Color(0xFFF59E0B); // Warning color
    }

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          'Remaining',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
        Text(
          '\$${_remaining.abs().toStringAsFixed(2)}',
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.bold,
            color: remainingColor,
          ),
        ),
      ],
    );
  }

  Widget _buildSplitEvenlyToggle(bool isDark) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark
            ? const Color(0xFF374151)
            : Colors.white,
        borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
        boxShadow: isDark ? null : AppTheme.cardShadow,
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Shared Item',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: isDark ? Colors.grey[400] : Colors.grey[600],
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Split evenly with everyone',
                  style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
          Switch(
            value: _splitEvenly,
            onChanged: _splitEvenlyToggled,
            activeColor: AppTheme.primary,
          ),
        ],
      ),
    );
  }

  Widget _buildContinueButton(bool isDark) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: isDark
            ? const Color(0xFF1F2937)
            : Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDark ? 0.2 : 0.05),
            blurRadius: 10,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: FilledButton(
        onPressed: _handleContinue,
        style: FilledButton.styleFrom(
          backgroundColor: AppTheme.primary,
          minimumSize: const Size(double.infinity, 56),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
          ),
        ),
        child: const Text(
          'Continue',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    );
  }
}

/// Model for a person in the bill split
class BillPerson {
  final String id;
  final String name;
  final IconData avatar;
  double amount;

  BillPerson({
    required this.id,
    required this.name,
    required this.avatar,
    required this.amount,
  });
}