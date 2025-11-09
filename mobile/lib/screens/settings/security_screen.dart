import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../services/api_service.dart';
import '../../utils/theme.dart';

class SecurityScreen extends StatefulWidget {
  const SecurityScreen({super.key});

  @override
  State<SecurityScreen> createState() => _SecurityScreenState();
}

class _SecurityScreenState extends State<SecurityScreen> {
  final Dio _dio = ApiService().dio;

  final _formKey = GlobalKey<FormState>();
  final _currentPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  bool _isSubmitting = false;
  bool _showCurrent = false;
  bool _showNew = false;
  bool _showConfirm = false;
  String? _error;
  String? _success;

  @override
  void dispose() {
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _handleChangePassword() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() {
      _isSubmitting = true;
      _error = null;
      _success = null;
    });

    try {
      await _dio.put('/auth/password', data: {
        'current_password': _currentPasswordController.text,
        'password': _newPasswordController.text,
        'password_confirmation': _confirmPasswordController.text,
      });

      if (!mounted) return;

      setState(() {
        _success = 'Password updated successfully';
        _isSubmitting = false;
        _currentPasswordController.clear();
        _newPasswordController.clear();
        _confirmPasswordController.clear();
      });

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Password updated successfully')),
      );
    } on DioException catch (e) {
      if (!mounted) return;
      final response = e.response;
      String message = 'Failed to update password';
      if (response?.data is Map<String, dynamic>) {
        final data = response!.data as Map<String, dynamic>;
        if (data['message'] is String) {
          message = data['message'] as String;
        } else if (data['errors'] is Map<String, dynamic>) {
          final errors = data['errors'] as Map<String, dynamic>;
          if (errors.values.isNotEmpty) {
            final firstError = errors.values.first;
            if (firstError is List && firstError.isNotEmpty) {
              message = firstError.first.toString();
            }
          }
        }
      }

      setState(() {
        _error = message;
        _isSubmitting = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _error = 'An unexpected error occurred';
        _isSubmitting = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Security'),
      ),
      body: ListView(
        padding: const EdgeInsets.all(AppTheme.spaceMedium),
        children: [
          _buildOverviewCard(context),
          const SizedBox(height: AppTheme.spaceLarge),
          _buildPasswordForm(context),
        ],
      ),
    );
  }

  Widget _buildOverviewCard(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppTheme.spaceMedium),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Account Security',
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: AppTheme.spaceSmall),
            Text(
              'Keep your account safe by using a strong password and updating it regularly. Use at least 8 characters with a mix of letters, numbers, and symbols.',
              style: Theme.of(context)
                  .textTheme
                  .bodyMedium
                  ?.copyWith(color: Colors.grey.shade600),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPasswordForm(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppTheme.spaceMedium),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Change Password',
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: AppTheme.spaceMedium),
              _buildPasswordField(
                label: 'Current password',
                controller: _currentPasswordController,
                showValue: _showCurrent,
                onToggle: () => setState(() => _showCurrent = !_showCurrent),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Enter your current password';
                  }
                  return null;
                },
              ),
              const SizedBox(height: AppTheme.spaceMedium),
              _buildPasswordField(
                label: 'New password',
                controller: _newPasswordController,
                showValue: _showNew,
                onToggle: () => setState(() => _showNew = !_showNew),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Enter a new password';
                  }
                  if (value.length < 8) {
                    return 'Password must be at least 8 characters';
                  }
                  if (value == _currentPasswordController.text) {
                    return 'Use a different password from the current one';
                  }
                  return null;
                },
              ),
              const SizedBox(height: AppTheme.spaceMedium),
              _buildPasswordField(
                label: 'Confirm new password',
                controller: _confirmPasswordController,
                showValue: _showConfirm,
                onToggle: () => setState(() => _showConfirm = !_showConfirm),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Confirm your new password';
                  }
                  if (value != _newPasswordController.text) {
                    return 'Passwords do not match';
                  }
                  return null;
                },
              ),
              if (_error != null) ...[
                const SizedBox(height: AppTheme.spaceMedium),
                _buildMessage(text: _error!, color: Colors.red.shade700),
              ],
              if (_success != null) ...[
                const SizedBox(height: AppTheme.spaceMedium),
                _buildMessage(text: _success!, color: Colors.green.shade700),
              ],
              const SizedBox(height: AppTheme.spaceLarge),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: _isSubmitting ? null : _handleChangePassword,
                  icon: _isSubmitting
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.lock_reset_rounded),
                  label: Text(_isSubmitting ? 'Updating...' : 'Update password'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPasswordField({
    required String label,
    required TextEditingController controller,
    required bool showValue,
    required VoidCallback onToggle,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      obscureText: !showValue,
      validator: validator,
      enabled: !_isSubmitting,
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
        suffixIcon: IconButton(
          icon: Icon(showValue ? Icons.visibility_off : Icons.visibility),
          onPressed: _isSubmitting ? null : onToggle,
        ),
      ),
    );
  }

  Widget _buildMessage({required String text, required Color color}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Icon(Icons.info_outline, color: color),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: TextStyle(color: color),
            ),
          ),
        ],
      ),
    );
  }
}
