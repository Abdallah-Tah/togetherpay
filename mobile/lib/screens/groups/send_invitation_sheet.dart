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
    // Separate invitations by status
    final pendingInvitations = widget.invitations
        .where((inv) => inv['status'] == 'pending')
        .toList();
    final acceptedInvitations = widget.invitations
        .where((inv) => inv['status'] == 'accepted')
        .toList();
    final otherInvitations = widget.invitations
        .where((inv) => inv['status'] != 'pending' && inv['status'] != 'accepted')
        .toList();

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
                        'Invitations',
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
                  'View invitation history and resend to pending members',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Colors.grey.shade600,
                      ),
                ),
                const SizedBox(height: 24),

                // Pending Invitations Section
                if (pendingInvitations.isNotEmpty) ...[
                  Text(
                    'Pending (${pendingInvitations.length})',
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: Colors.orange.shade700,
                        ),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<Map<String, dynamic>>(
                    initialValue: _selectedInvitation,
                    decoration: const InputDecoration(
                      labelText: 'Select invitation to resend',
                      prefixIcon: Icon(Icons.group_outlined),
                    ),
                    items: pendingInvitations.map((invitation) {
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
                    enabled: !_loading,
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
                  const SizedBox(height: 16),

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

                  // Resend button
                  FilledButton.icon(
                    onPressed: _loading ? null : _resendInvitation,
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
                  const SizedBox(height: 24),
                ],

                // No pending invitations message
                if (pendingInvitations.isEmpty)
                  Container(
                    padding: const EdgeInsets.all(16),
                    margin: const EdgeInsets.only(bottom: 24),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.blue.shade100),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.info_outline, color: Colors.blue.shade700),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            'No pending invitations to resend.',
                            style: TextStyle(color: Colors.blue.shade900),
                          ),
                        ),
                      ],
                    ),
                  ),

                // Accepted Invitations Section
                if (acceptedInvitations.isNotEmpty) ...[
                  Text(
                    'Accepted (${acceptedInvitations.length})',
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: Colors.green.shade700,
                        ),
                  ),
                  const SizedBox(height: 12),
                  ...acceptedInvitations.map((invitation) {
                    final email = invitation['email']?.toString() ?? 'Unknown';
                    final acceptedAt = invitation['accepted_at']?.toString();
                    return Container(
                      margin: const EdgeInsets.only(bottom: 8),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.green.shade50,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.green.shade200),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.check_circle,
                               color: Colors.green.shade700,
                               size: 20),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  email,
                                  style: TextStyle(
                                    fontWeight: FontWeight.w500,
                                    color: Colors.green.shade900,
                                  ),
                                ),
                                if (acceptedAt != null) ...[
                                  const SizedBox(height: 4),
                                  Text(
                                    'Joined',
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.green.shade700,
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                  const SizedBox(height: 16),
                ],

                // Other Invitations (expired, cancelled)
                if (otherInvitations.isNotEmpty) ...[
                  Text(
                    'Other (${otherInvitations.length})',
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: Colors.grey.shade700,
                        ),
                  ),
                  const SizedBox(height: 12),
                  ...otherInvitations.map((invitation) {
                    final email = invitation['email']?.toString() ?? 'Unknown';
                    final status = invitation['status']?.toString() ?? 'unknown';
                    final statusColor = status == 'expired'
                        ? Colors.orange
                        : Colors.grey;
                    final statusIcon = status == 'expired'
                        ? Icons.schedule
                        : Icons.cancel;

                    return Container(
                      margin: const EdgeInsets.only(bottom: 8),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: statusColor.shade50,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: statusColor.shade200),
                      ),
                      child: Row(
                        children: [
                          Icon(statusIcon,
                               color: statusColor.shade700,
                               size: 20),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  email,
                                  style: TextStyle(
                                    fontWeight: FontWeight.w500,
                                    color: statusColor.shade900,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  status.toUpperCase(),
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: statusColor.shade700,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                  const SizedBox(height: 16),
                ],

                // Info text
                if (pendingInvitations.isNotEmpty)
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
