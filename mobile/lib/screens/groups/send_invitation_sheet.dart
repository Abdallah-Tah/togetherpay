import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../env.dart';
import '../../main.dart';

/// Bottom sheet for sending group invitations via email
class SendInvitationSheet extends StatefulWidget {
  final int groupId;

  const SendInvitationSheet({
    super.key,
    required this.groupId,
  });

  @override
  State<SendInvitationSheet> createState() => _SendInvitationSheetState();
}

class _SendInvitationSheetState extends State<SendInvitationSheet> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _shareCountController = TextEditingController(text: '1');
  final _venmoController = TextEditingController();
  final _cashAppController = TextEditingController();

  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _shareCountController.dispose();
    _venmoController.dispose();
    _cashAppController.dispose();
    super.dispose();
  }

  Future<void> _sendInvitation() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await dio.post(
        '/groups/${widget.groupId}/invitations',
        data: {
          'email': _emailController.text.trim(),
          'name': _nameController.text.trim(),
          'phone': _phoneController.text.trim().isEmpty
              ? null
              : _phoneController.text.trim(),
          'share_count': int.parse(_shareCountController.text),
          'venmo_username': _venmoController.text.trim().isEmpty
              ? null
              : _venmoController.text.trim(),
          'cashapp_cashtag': _cashAppController.text.trim().isEmpty
              ? null
              : _cashAppController.text.trim(),
        },
      );

      if (!mounted) return;

      final invitationData = response.data['invitation'] as Map<String, dynamic>?;
      final url = invitationData?['invitation_url'] as String?;

      setState(() {
        _loading = false;
      });

      // Show success message
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            response.data['message'] ?? 'Invitation sent successfully!',
          ),
          backgroundColor: Colors.green,
          action: url != null
              ? SnackBarAction(
                  label: 'Copy Link',
                  textColor: Colors.white,
                  onPressed: () {
                    Clipboard.setData(ClipboardData(text: url));
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('Invitation link copied to clipboard'),
                        duration: Duration(seconds: 2),
                      ),
                    );
                  },
                )
              : null,
        ),
      );

      // Wait a moment to show success, then close
      await Future.delayed(const Duration(seconds: 2));
      if (mounted) {
        Navigator.of(context).pop(true);
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Send invitation error: ${e.response?.data}');
      }

      if (!mounted) return;

      setState(() {
        _loading = false;
        _error = e.response?.data?['message'] as String? ??
                 'Failed to send invitation';
      });

      // Show error in snackbar
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_error!),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Header
                Row(
                  children: [
                    Icon(
                      Icons.mail_outline,
                      size: 28,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Send Invitation',
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.of(context).pop(),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  'Member will receive an email invitation to join this group',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Colors.grey.shade600,
                      ),
                ),
                const SizedBox(height: 24),

                // Email (required)
                TextFormField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    labelText: 'Email *',
                    hintText: 'member@example.com',
                    prefixIcon: Icon(Icons.email_outlined),
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return 'Email is required';
                    }
                    if (!value.contains('@')) {
                      return 'Enter a valid email';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Name (required)
                TextFormField(
                  controller: _nameController,
                  decoration: const InputDecoration(
                    labelText: 'Full Name *',
                    hintText: 'John Doe',
                    prefixIcon: Icon(Icons.person_outline),
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return 'Name is required';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Phone (optional)
                TextFormField(
                  controller: _phoneController,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'Phone Number (optional)',
                    hintText: '+1234567890',
                    prefixIcon: Icon(Icons.phone_outlined),
                  ),
                ),
                const SizedBox(height: 16),

                // Share count (required)
                TextFormField(
                  controller: _shareCountController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Number of Shares *',
                    hintText: '1',
                    prefixIcon: Icon(Icons.pie_chart_outline),
                    helperText: 'How many shares this member gets',
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return 'Share count is required';
                    }
                    final number = int.tryParse(value);
                    if (number == null || number < 1) {
                      return 'Enter a valid number (minimum 1)';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 24),

                // Payment methods section
                Text(
                  'Payment Methods (Optional)',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Add payment handles to make it easier for them',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Colors.grey.shade600,
                      ),
                ),
                const SizedBox(height: 16),

                // Venmo username
                TextFormField(
                  controller: _venmoController,
                  decoration: const InputDecoration(
                    labelText: 'Venmo Username',
                    hintText: 'username',
                    prefixIcon: Icon(Icons.account_balance_wallet_outlined),
                    helperText: 'Without @ symbol',
                  ),
                ),
                const SizedBox(height: 16),

                // Cash App tag
                TextFormField(
                  controller: _cashAppController,
                  decoration: const InputDecoration(
                    labelText: 'Cash App Tag',
                    hintText: 'cashtag',
                    prefixIcon: Icon(Icons.payments_outlined),
                    helperText: 'Without \$ symbol',
                  ),
                ),
                const SizedBox(height: 24),

                // Error message
                if (_error != null)
                  Container(
                    padding: const EdgeInsets.all(12),
                    margin: const EdgeInsets.only(bottom: 16),
                    decoration: BoxDecoration(
                      color: Colors.red.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.red.shade200),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.error_outline, color: Colors.red.shade700),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            _error!,
                            style: TextStyle(color: Colors.red.shade900),
                          ),
                        ),
                      ],
                    ),
                  ),

                // Submit button
                FilledButton.icon(
                  onPressed: _loading ? null : _sendInvitation,
                  icon: _loading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                          ),
                        )
                      : const Icon(Icons.send),
                  label: Text(_loading ? 'Sending...' : 'Send Invitation'),
                  style: FilledButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                ),
                const SizedBox(height: 8),

                // Info text
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.blue.shade50,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(
                        Icons.info_outline,
                        color: Colors.blue.shade700,
                        size: 20,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'The member will receive an email with a link to join this group. They can create an account through the app.',
                          style: TextStyle(
                            color: Colors.blue.shade900,
                            fontSize: 13,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
