import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../services/fcm_service.dart';
import '../../utils/theme.dart';
import 'reset_onboarding.dart';

/// Firebase debug screen to test FCM functionality
///
/// Shows FCM token, permission status, and allows testing
class FirebaseDebugScreen extends StatefulWidget {
  const FirebaseDebugScreen({super.key});

  @override
  State<FirebaseDebugScreen> createState() => _FirebaseDebugScreenState();
}

class _FirebaseDebugScreenState extends State<FirebaseDebugScreen> {
  String? _fcmToken;
  bool _loading = true;
  String _status = 'Checking Firebase status...';

  @override
  void initState() {
    super.initState();
    _checkFirebase();
  }

  Future<void> _checkFirebase() async {
    setState(() {
      _loading = true;
      _status = 'Checking Firebase...';
    });

    try {
      // Get FCM token
      final token = await FCMService.getToken();

      setState(() {
        _fcmToken = token;
        _loading = false;
        if (token != null) {
          _status = '✅ Firebase is working!';
        } else {
          _status = '❌ No FCM token - Firebase may not be initialized';
        }
      });
    } catch (e) {
      setState(() {
        _loading = false;
        _status = '❌ Error: $e';
      });
    }
  }

  Future<void> _reinitializeFirebase() async {
    setState(() {
      _loading = true;
      _status = 'Re-initializing Firebase...';
    });

    try {
      await FCMService.initialize();
      await _checkFirebase();
    } catch (e) {
      setState(() {
        _loading = false;
        _status = '❌ Re-initialization failed: $e';
      });
    }
  }

  void _copyToken() {
    if (_fcmToken != null) {
      Clipboard.setData(ClipboardData(text: _fcmToken!));
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('FCM token copied to clipboard')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Firebase Debug'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loading ? null : _checkFirebase,
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _checkFirebase,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Status Card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(
                          _fcmToken != null ? Icons.check_circle : Icons.error,
                          color: _fcmToken != null
                              ? AppTheme.primary
                              : Colors.red,
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            _status,
                            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                    if (_loading) ...[
                      const SizedBox(height: 16),
                      const LinearProgressIndicator(),
                    ],
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // FCM Token Card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'FCM Token',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    if (_fcmToken != null) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.surfaceContainerHighest,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: SelectableText(
                          _fcmToken!,
                          style: const TextStyle(
                            fontFamily: 'monospace',
                            fontSize: 12,
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      FilledButton.icon(
                        onPressed: _copyToken,
                        icon: const Icon(Icons.copy),
                        label: const Text('Copy Token'),
                        style: FilledButton.styleFrom(
                          backgroundColor: AppTheme.primary,
                        ),
                      ),
                    ] else ...[
                      Text(
                        'No token available',
                        style: TextStyle(color: Colors.grey[600]),
                      ),
                    ],
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Instructions Card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'How to Test Notifications',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),
                    _buildStep(
                      '1',
                      'Copy the FCM token above',
                    ),
                    const SizedBox(height: 8),
                    _buildStep(
                      '2',
                      'Go to Firebase Console → Cloud Messaging',
                    ),
                    const SizedBox(height: 8),
                    _buildStep(
                      '3',
                      'Click "Send test message"',
                    ),
                    const SizedBox(height: 8),
                    _buildStep(
                      '4',
                      'Paste the FCM token and send',
                    ),
                    const SizedBox(height: 12),
                    const Divider(),
                    const SizedBox(height: 12),
                    Text(
                      'Expected Notification Data',
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.surfaceContainerHighest,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const SelectableText(
                        '{\n'
                        '  "pr_code": "TEST123",\n'
                        '  "deep_link": "cashapp://..."\n'
                        '}',
                        style: TextStyle(
                          fontFamily: 'monospace',
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Actions
            OutlinedButton.icon(
              onPressed: _loading ? null : _reinitializeFirebase,
              icon: const Icon(Icons.refresh),
              label: const Text('Re-initialize Firebase'),
            ),

            const SizedBox(height: 16),

            // Debug Tools
            Card(
              child: Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        const Icon(Icons.bug_report_outlined),
                        const SizedBox(width: 8),
                        Text(
                          'Debug Tools',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 1),
                  const ResetOnboardingButton(),
                ],
              ),
            ),

            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  Widget _buildStep(String number, String text) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 24,
          height: 24,
          decoration: BoxDecoration(
            color: AppTheme.primary,
            shape: BoxShape.circle,
          ),
          child: Center(
            child: Text(
              number,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 12,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Text(text),
        ),
      ],
    );
  }
}
