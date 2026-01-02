import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../env.dart';
import '../../main.dart';

/// Bottom sheet for resending group invitations via email
class SendInvitationSheet extends StatefulWidget {
  final int groupId;
  final List<Map<String, dynamic>> invitations;

  const SendInvitationSheet({
    super.key,
    required this.groupId,
    this.invitations = const [],
  });

  @override
  State<SendInvitationSheet> createState() => _SendInvitationSheetState();
}

class _SendInvitationSheetState extends State<SendInvitationSheet> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  Map<String, dynamic>? _selectedInvitation;

  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    if (widget.invitations.isNotEmpty) {
      _selectedInvitation = widget.invitations.first;
      _emailController.text =
          _selectedInvitation?['email']?.toString() ?? '';
    }
  }

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _resendInvitation() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedInvitation == null) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await dio.post(
        '/groups/${widget.groupId}/invitations/${_selectedInvitation?['id']}/resend',
        data: {
          'email': _emailController.text.trim(),
        },
      );

      if (!mounted) return;

      setState(() {
        _loading = false;
      });

      // Show success message
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            response.data['message'] ?? 'Invitation resent successfully!',
          ),
          backgroundColor: Colors.green,
        ),
      );

      // Wait a moment to show success, then close
      await Future.delayed(const Duration(seconds: 2));
      if (mounted) {
        Navigator.of(context).pop(true);
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Resend invitation error: ${e.response?.data}');
      }

      if (!mounted) return;

      setState(() {
        _loading = false;
        _error = e.response?.data?['message'] as String? ??
                 'Failed to resend invitation';
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
    final invitations = widget.invitations;

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
                        'Resend Invitation',
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
                  'Resend an invitation to a pending member',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Colors.grey.shade600,
                      ),
                ),
                const SizedBox(height: 24),

                if (invitations.isEmpty)
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.blue.shade100),
                    ),
                    child: Text(
                      'No pending invitations to resend.',
                      style: TextStyle(color: Colors.blue.shade900),
                    ),
                  )
                else ...[
                  DropdownButtonFormField<Map<String, dynamic>>(
                    value: _selectedInvitation,
                    decoration: const InputDecoration(
                      labelText: 'Pending invitation',
                      prefixIcon: Icon(Icons.group_outlined),
                    ),
                    items: invitations.map((invitation) {
                      final email = invitation['email']?.toString() ?? 'Unknown';
                      return DropdownMenuItem(
                        value: invitation,
                        child: Text(email),
                      );
                    }).toList(),
                    onChanged: _loading
                        ? null
                        : (value) {
                            setState(() {
                              _selectedInvitation = value;
                              _emailController.text =
                                  value?['email']?.toString() ?? '';
                            });
                          },
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _emailController,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(
                      labelText: 'Email',
                      hintText: 'member@example.com',
                      prefixIcon: Icon(Icons.email_outlined),
                      helperText:
                          'Update the email if it was incorrect, then resend.',
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
                  const SizedBox(height: 24),
                ],

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
                  onPressed: _loading || invitations.isEmpty
                      ? null
                      : _resendInvitation,
                  icon: _loading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                          ),
                        )
                      : const Icon(Icons.refresh),
                  label: Text(_loading ? 'Sending...' : 'Resend Invitation'),
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
                          'We will resend the invitation link to the selected member.',
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
