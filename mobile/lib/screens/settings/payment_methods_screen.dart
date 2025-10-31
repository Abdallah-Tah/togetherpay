import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../main.dart';
import '../../utils/theme.dart';

/// Payment methods management screen
///
/// Allows users to manage their Cash App and Venmo payment accounts
class PaymentMethodsScreen extends StatefulWidget {
  const PaymentMethodsScreen({super.key});

  @override
  State<PaymentMethodsScreen> createState() => _PaymentMethodsScreenState();
}

class _PaymentMethodsScreenState extends State<PaymentMethodsScreen> {
  bool _loading = true;
  String? _error;
  Map<String, dynamic>? _paymentAccounts;

  final _venmoUsernameController = TextEditingController();
  final _cashTagController = TextEditingController();
  final _cashPhoneController = TextEditingController();
  final _cashEmailController = TextEditingController();

  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _loadPaymentAccounts();
  }

  @override
  void dispose() {
    _venmoUsernameController.dispose();
    _cashTagController.dispose();
    _cashPhoneController.dispose();
    _cashEmailController.dispose();
    super.dispose();
  }

  Future<void> _loadPaymentAccounts() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await dio.get('/auth/me');
      final data = response.data as Map<String, dynamic>;
      final user = data['user'] ?? data;

      setState(() {
        _paymentAccounts = Map<String, dynamic>.from(user as Map);
        _loading = false;
      });

      // Populate form fields
      _venmoUsernameController.text = _paymentAccounts?['venmo_username'] ?? '';
      _cashTagController.text = _paymentAccounts?['cash_tag'] ?? '';
      _cashPhoneController.text = _paymentAccounts?['cash_phone'] ?? '';
      _cashEmailController.text = _paymentAccounts?['cash_email'] ?? '';
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Payment accounts load error: ${e.response?.data}');
      }

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Failed to load payment accounts';
        _loading = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected error: $e');
      }

      setState(() {
        _error = 'An unexpected error occurred';
        _loading = false;
      });
    }
  }

  Future<void> _savePaymentAccounts() async {
    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final data = <String, dynamic>{};

      if (_venmoUsernameController.text.trim().isNotEmpty) {
        data['venmo_username'] = _venmoUsernameController.text.trim();
      }

      if (_cashTagController.text.trim().isNotEmpty) {
        data['cash_tag'] = _cashTagController.text.trim();
      }

      if (_cashPhoneController.text.trim().isNotEmpty) {
        data['cash_phone'] = _cashPhoneController.text.trim();
      }

      if (_cashEmailController.text.trim().isNotEmpty) {
        data['cash_email'] = _cashEmailController.text.trim();
      }

      await dio.put('/auth/me', data: data);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Payment accounts updated successfully'),
            backgroundColor: Colors.green,
          ),
        );

        await _loadPaymentAccounts();
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Save payment accounts error: ${e.response?.data}');
      }

      if (mounted) {
        setState(() {
          _error = e.response?.data?['message'] as String? ??
              'Failed to update payment accounts';
        });

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_error!),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _saving = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Payment Methods'),
        actions: [
          if (!_loading)
            IconButton(
              icon: const Icon(Icons.refresh),
              onPressed: _loadPaymentAccounts,
              tooltip: 'Refresh',
            ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadPaymentAccounts,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(AppTheme.spaceMedium),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (_error != null) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.orange.shade50,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.orange.shade200),
                        ),
                        child: Row(
                          children: [
                            Icon(Icons.error_outline,
                                color: Colors.orange.shade700),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                _error!,
                                style: TextStyle(color: Colors.orange.shade900),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: AppTheme.spaceMedium),
                    ],

                    // Venmo Section
                    _buildSectionHeader(
                      'Venmo',
                      Icons.account_balance_wallet,
                      Colors.blue.shade700,
                    ),
                    const SizedBox(height: AppTheme.spaceSmall),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(AppTheme.spaceMedium),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            TextFormField(
                              controller: _venmoUsernameController,
                              enabled: !_saving,
                              decoration: const InputDecoration(
                                labelText: 'Venmo Username',
                                hintText: '@username',
                                border: OutlineInputBorder(),
                                prefixIcon: Icon(Icons.alternate_email),
                                helperText:
                                    'Your Venmo username (e.g., @johndoe)',
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),

                    const SizedBox(height: AppTheme.spaceLarge),

                    // Cash App Section
                    _buildSectionHeader(
                      'Cash App',
                      Icons.attach_money,
                      Colors.green.shade700,
                    ),
                    const SizedBox(height: AppTheme.spaceSmall),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(AppTheme.spaceMedium),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            TextFormField(
                              controller: _cashTagController,
                              enabled: !_saving,
                              decoration: const InputDecoration(
                                labelText: 'Cash Tag',
                                hintText: '\$cashtag',
                                border: OutlineInputBorder(),
                                prefixIcon: Icon(Icons.tag),
                                helperText:
                                    'Your Cash App tag (e.g., \$johndoe)',
                              ),
                            ),
                            const SizedBox(height: AppTheme.spaceMedium),
                            TextFormField(
                              controller: _cashPhoneController,
                              enabled: !_saving,
                              keyboardType: TextInputType.phone,
                              decoration: const InputDecoration(
                                labelText: 'Phone Number (Optional)',
                                hintText: '+1234567890',
                                border: OutlineInputBorder(),
                                prefixIcon: Icon(Icons.phone),
                                helperText:
                                    'Associated with your Cash App account',
                              ),
                            ),
                            const SizedBox(height: AppTheme.spaceMedium),
                            TextFormField(
                              controller: _cashEmailController,
                              enabled: !_saving,
                              keyboardType: TextInputType.emailAddress,
                              decoration: const InputDecoration(
                                labelText: 'Email (Optional)',
                                hintText: 'email@example.com',
                                border: OutlineInputBorder(),
                                prefixIcon: Icon(Icons.email_outlined),
                                helperText:
                                    'Associated with your Cash App account',
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),

                    const SizedBox(height: AppTheme.spaceLarge),

                    // Info Card
                    Container(
                      padding: const EdgeInsets.all(AppTheme.spaceMedium),
                      decoration: BoxDecoration(
                        color: Colors.blue.shade50,
                        borderRadius:
                            BorderRadius.circular(AppTheme.radiusMedium),
                        border: Border.all(color: Colors.blue.shade200),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(Icons.info_outline,
                              color: Colors.blue.shade700),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              'These payment accounts are used when group owners send you payment requests. Make sure they are accurate to receive payments smoothly.',
                              style: TextStyle(
                                color: Colors.blue.shade900,
                                fontSize: 14,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: AppTheme.spaceLarge),

                    // Save Button
                    FilledButton.icon(
                      onPressed: _saving ? null : _savePaymentAccounts,
                      icon: _saving
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Icon(Icons.save),
                      label: Text(_saving ? 'Saving...' : 'Save Changes'),
                    ),

                    const SizedBox(height: AppTheme.spaceXLarge),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildSectionHeader(String title, IconData icon, Color color) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(AppTheme.radiusSmall),
          ),
          child: Icon(icon, size: 20, color: color),
        ),
        const SizedBox(width: AppTheme.spaceSmall),
        Text(
          title,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
                color: color,
              ),
        ),
      ],
    );
  }
}
