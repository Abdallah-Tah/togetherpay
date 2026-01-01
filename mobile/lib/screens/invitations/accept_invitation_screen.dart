import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../main.dart';
import '../../env.dart';
import '../groups/group_details_screen.dart';

class AcceptInvitationScreen extends StatefulWidget {
  final String token;

  const AcceptInvitationScreen({
    super.key,
    required this.token,
  });

  @override
  State<AcceptInvitationScreen> createState() => _AcceptInvitationScreenState();
}

class _AcceptInvitationScreenState extends State<AcceptInvitationScreen> {
  bool _loading = true;
  String? _error;
  Map<String, dynamic>? _invitation;
  bool _isAuthenticated = false;
  bool _processing = false;

  @override
  void initState() {
    super.initState();
    _checkAuthAndLoadInvitation();
  }

  Future<void> _checkAuthAndLoadInvitation() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      // Check if user is authenticated
      final token = await storage.read(key: 'auth_token');
      _isAuthenticated = token != null;

      // Load invitation details
      final response = await dio.get('/invitations/${widget.token}');

      setState(() {
        _invitation = response.data['invitation'] as Map<String, dynamic>;
        _loading = false;
      });
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Invitation load error: ${e.response?.data}');
      }

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Failed to load invitation. The link may be invalid or expired.';
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

  Future<void> _acceptInvitation() async {
    if (_invitation == null || _processing) return;

    setState(() {
      _processing = true;
    });

    try {
      final response = await dio.post(
        '/invitations/${widget.token}/accept',
        data: {'accept': true},
      );

      if (!mounted) return;

      // Show success message
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
              response.data['message'] ?? 'Successfully joined the group!'),
          backgroundColor: Colors.green,
        ),
      );

      // Navigate to group details
      final groupId = _invitation!['group']['id'] as int;

      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => GroupDetailsScreen(groupId: groupId),
        ),
      );
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Accept invitation error: ${e.response?.data}');
      }

      if (!mounted) return;

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Failed to accept invitation';
        _processing = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_error!),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _declineInvitation() async {
    if (_invitation == null || _processing) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Decline Invitation'),
        content: const Text(
          'Are you sure you want to decline this invitation?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(
              backgroundColor: Colors.red,
            ),
            child: const Text('Decline'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    setState(() {
      _processing = true;
    });

    try {
      await dio.post(
        '/invitations/${widget.token}/accept',
        data: {'accept': false},
      );

      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Invitation declined'),
        ),
      );

      Navigator.of(context).pop();
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Decline invitation error: ${e.response?.data}');
      }

      if (!mounted) return;

      setState(() {
        _processing = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            e.response?.data?['message'] as String? ??
                'Failed to decline invitation',
          ),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  void _navigateToSignup() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => InvitationSignupScreen(
          token: widget.token,
          invitation: _invitation!,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Group Invitation'),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(
        child: CircularProgressIndicator(),
      );
    }

    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.error_outline,
                size: 80,
                color: Colors.red.shade300,
              ),
              const SizedBox(height: 24),
              Text(
                'Invalid Invitation',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 16),
              Text(
                _error!,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: Colors.grey.shade600,
                    ),
              ),
              const SizedBox(height: 32),
              FilledButton(
                onPressed: () => Navigator.of(context).pop(),
                child: const Text('Go Back'),
              ),
            ],
          ),
        ),
      );
    }

    final invitation = _invitation!;
    final isValid = invitation['is_valid'] as bool? ?? false;
    final status = invitation['status'] as String;
    final groupName = invitation['group']['name'] as String;
    final invitedBy = invitation['invited_by']['name'] as String;
    final expiresAt = DateTime.parse(invitation['expires_at'] as String);
    final memberData = invitation['member_data'] as Map<String, dynamic>?;

    if (!isValid) {
      String message = 'This invitation is no longer valid.';
      if (status == 'expired') {
        message = 'This invitation has expired.';
      } else if (status == 'accepted') {
        message = 'This invitation has already been accepted.';
      } else if (status == 'cancelled') {
        message = 'This invitation has been cancelled.';
      }

      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.block,
                size: 80,
                color: Colors.orange.shade300,
              ),
              const SizedBox(height: 24),
              Text(
                'Invitation Unavailable',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 16),
              Text(
                message,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: Colors.grey.shade600,
                    ),
              ),
              const SizedBox(height: 32),
              FilledButton(
                onPressed: () => Navigator.of(context).pop(),
                child: const Text('Go Back'),
              ),
            ],
          ),
        ),
      );
    }

    return SingleChildScrollView(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Invitation icon
            Center(
              child: Container(
                width: 100,
                height: 100,
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.primaryContainer,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.mail_outline,
                  size: 50,
                  color: Theme.of(context).colorScheme.onPrimaryContainer,
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Invitation title
            Text(
              'You\'re Invited!',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 8),

            // Invited by
            Text(
              '$invitedBy has invited you to join',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                    color: Colors.grey.shade600,
                  ),
            ),
            const SizedBox(height: 24),

            // Group name card
            Card(
              elevation: 2,
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: [
                    Icon(
                      Icons.groups,
                      size: 48,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                    const SizedBox(height: 12),
                    Text(
                      groupName,
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Member details
            if (memberData != null) ...[
              Text(
                'Your Details',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 12),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      _buildDetailRow(
                        Icons.person_outline,
                        'Name',
                        memberData['name'] as String? ?? 'N/A',
                      ),
                      const Divider(height: 24),
                      _buildDetailRow(
                        Icons.pie_chart_outline,
                        'Share',
                        '${memberData['share_count']} share(s)',
                      ),
                      const Divider(height: 24),
                      _buildDetailRow(
                        Icons.payments_outlined,
                        'Payment',
                        'Member chooses at pay time',
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 24),
            ],

            // Expiration notice
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.orange.shade50,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.orange.shade200),
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.access_time,
                    color: Colors.orange.shade700,
                    size: 20,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Expires ${_formatExpiration(expiresAt)}',
                      style: TextStyle(
                        color: Colors.orange.shade900,
                        fontSize: 13,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 32),

            // Action buttons
            if (_isAuthenticated) ...[
              FilledButton.icon(
                onPressed: _processing ? null : _acceptInvitation,
                icon: _processing
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          valueColor:
                              AlwaysStoppedAnimation<Color>(Colors.white),
                        ),
                      )
                    : const Icon(Icons.check_circle_outline),
                label: Text(_processing ? 'Accepting...' : 'Accept Invitation'),
                style: FilledButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
              ),
              const SizedBox(height: 12),
              OutlinedButton(
                onPressed: _processing ? null : _declineInvitation,
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
                child: const Text('Decline'),
              ),
            ] else ...[
              FilledButton.icon(
                onPressed: _navigateToSignup,
                icon: const Icon(Icons.person_add),
                label: const Text('Create Account & Join'),
                style: FilledButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
              ),
              const SizedBox(height: 12),
              OutlinedButton.icon(
                onPressed: () {
                  // Navigate to login screen
                  Navigator.of(context).pop();
                },
                icon: const Icon(Icons.login),
                label: const Text('Already have an account? Login'),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildDetailRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, size: 20, color: Colors.grey.shade600),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey.shade600,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  String _formatExpiration(DateTime expiresAt) {
    final now = DateTime.now();
    final difference = expiresAt.difference(now);

    if (difference.isNegative) {
      return 'expired';
    } else if (difference.inDays > 1) {
      return 'in ${difference.inDays} days';
    } else if (difference.inDays == 1) {
      return 'tomorrow';
    } else if (difference.inHours > 1) {
      return 'in ${difference.inHours} hours';
    } else {
      return 'soon';
    }
  }
}

/// Signup screen specifically for invitation acceptance
class InvitationSignupScreen extends StatefulWidget {
  final String token;
  final Map<String, dynamic> invitation;

  const InvitationSignupScreen({
    super.key,
    required this.token,
    required this.invitation,
  });

  @override
  State<InvitationSignupScreen> createState() => _InvitationSignupScreenState();
}

class _InvitationSignupScreenState extends State<InvitationSignupScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _otpController = TextEditingController();
  final _passwordController = TextEditingController();
  final _passwordConfirmController = TextEditingController();

  bool _obscurePassword = true;
  bool _obscureConfirm = true;
  bool _loading = false;
  bool _sendingCode = false;
  String? _sendError;

  @override
  void initState() {
    super.initState();
    // Pre-fill name from invitation if available
    final memberData =
        widget.invitation['member_data'] as Map<String, dynamic>?;
    if (memberData != null) {
      _nameController.text = memberData['name'] as String? ?? '';
    }
    _requestOtp();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _otpController.dispose();
    _passwordController.dispose();
    _passwordConfirmController.dispose();
    super.dispose();
  }

  Future<void> _requestOtp() async {
    if (_sendingCode) return;
    setState(() {
      _sendingCode = true;
      _sendError = null;
    });

    try {
      await dio.post('/invitations/${widget.token}/otp/request');
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Verification code sent to your email'),
        ),
      );
    } on DioException catch (e) {
      if (!mounted) return;
      setState(() {
        _sendError = e.response?.data?['message'] as String? ??
            'Failed to send verification code';
      });
    } finally {
      if (mounted) {
        setState(() {
          _sendingCode = false;
        });
      }
    }
  }

  Future<void> _handleSignup() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _loading = true;
    });

    try {
      final response = await dio.post(
        '/invitations/${widget.token}/otp/verify',
        data: {
          'code': _otpController.text.trim(),
          'name': _nameController.text.trim(),
          'password': _passwordController.text,
          'password_confirmation': _passwordConfirmController.text,
        },
      );

      // Save auth token
      final token = response.data['token'] as String;
      await storage.write(key: 'auth_token', value: token);
      dio.options.headers['Authorization'] = 'Bearer $token';

      if (!mounted) return;

      // Show success message
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            response.data['message'] ?? 'Account created successfully!',
          ),
          backgroundColor: Colors.green,
        ),
      );

      // Navigate to app (this will trigger a full app restart with auth)
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(
          builder: (_) => const TogetherPayApp(),
        ),
        (route) => false,
      );
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Signup error: ${e.response?.data}');
      }

      if (!mounted) return;

      setState(() {
        _loading = false;
      });

      final errorData = e.response?.data;
      String errorMessage = 'Failed to create account';

      if (errorData is Map && errorData.containsKey('errors')) {
        final errors = errorData['errors'] as Map;
        errorMessage = errors.values.first.first as String;
      } else if (errorData is Map && errorData.containsKey('message')) {
        errorMessage = errorData['message'] as String;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(errorMessage),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final email = widget.invitation['email'] as String;
    final groupName = widget.invitation['group']['name'] as String;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Create Account'),
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'Join $groupName',
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Verify your email and create a password to accept this invitation.',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Colors.grey.shade600,
                      ),
                ),
                const SizedBox(height: 32),

                // Email (read-only)
                TextFormField(
                  initialValue: email,
                  enabled: false,
                  decoration: const InputDecoration(
                    labelText: 'Email',
                    prefixIcon: Icon(Icons.email_outlined),
                  ),
                ),
                const SizedBox(height: 12),

                if (_sendError != null) ...[
                  Text(
                    _sendError!,
                    style: TextStyle(color: Colors.red.shade600),
                  ),
                  const SizedBox(height: 12),
                ],

                Row(
                  children: [
                    Expanded(
                      child: Text(
                        'We sent a 6‑digit code to your email.',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Colors.grey.shade600,
                            ),
                      ),
                    ),
                    TextButton(
                      onPressed: _sendingCode ? null : _requestOtp,
                      child: Text(_sendingCode ? 'Sending…' : 'Resend'),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                TextFormField(
                  controller: _otpController,
                  keyboardType: TextInputType.number,
                  textInputAction: TextInputAction.next,
                  decoration: const InputDecoration(
                    labelText: 'Verification Code',
                    prefixIcon: Icon(Icons.verified_outlined),
                    hintText: '123456',
                  ),
                  validator: (value) {
                    final v = (value ?? '').trim();
                    if (v.isEmpty) return 'Enter the verification code';
                    if (v.length < 4) return 'Code looks too short';
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Name
                TextFormField(
                  controller: _nameController,
                  decoration: const InputDecoration(
                    labelText: 'Full Name',
                    prefixIcon: Icon(Icons.person_outline),
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return 'Please enter your name';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Password
                TextFormField(
                  controller: _passwordController,
                  obscureText: _obscurePassword,
                  decoration: InputDecoration(
                    labelText: 'Password',
                    prefixIcon: const Icon(Icons.lock_outline),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscurePassword
                            ? Icons.visibility
                            : Icons.visibility_off,
                      ),
                      onPressed: () {
                        setState(() {
                          _obscurePassword = !_obscurePassword;
                        });
                      },
                    ),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter a password';
                    }
                    if (value.length < 8) {
                      return 'Password must be at least 8 characters';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Confirm Password
                TextFormField(
                  controller: _passwordConfirmController,
                  obscureText: _obscureConfirm,
                  decoration: InputDecoration(
                    labelText: 'Confirm Password',
                    prefixIcon: const Icon(Icons.lock_outline),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscureConfirm
                            ? Icons.visibility
                            : Icons.visibility_off,
                      ),
                      onPressed: () {
                        setState(() {
                          _obscureConfirm = !_obscureConfirm;
                        });
                      },
                    ),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please confirm your password';
                    }
                    if (value != _passwordController.text) {
                      return 'Passwords do not match';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 24),

                const SizedBox(height: 24),

                // Submit button
                FilledButton(
                  onPressed: _loading ? null : _handleSignup,
                  style: FilledButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: _loading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            valueColor:
                                AlwaysStoppedAnimation<Color>(Colors.white),
                          ),
                        )
                      : const Text('Verify & Join'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
