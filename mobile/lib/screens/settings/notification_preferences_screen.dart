import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

import '../../env.dart';
import '../../main.dart';
import '../../utils/theme.dart';

/// Notification preferences screen
///
/// Allows users to manage their notification settings
class NotificationPreferencesScreen extends StatefulWidget {
  const NotificationPreferencesScreen({super.key});

  @override
  State<NotificationPreferencesScreen> createState() =>
      _NotificationPreferencesScreenState();
}

class _NotificationPreferencesScreenState
    extends State<NotificationPreferencesScreen> {
  bool _loading = true;
  String? _error;
  bool _saving = false;
  bool? _systemPushAuthorized;

  // Notification preferences
  bool _emailNotifications = true;
  bool _pushNotifications = true;
  bool _smsNotifications = false;

  // Notification types
  bool _paymentRequests = true;
  bool _paymentReminders = true;
  bool _paymentConfirmations = true;
  bool _groupInvitations = true;
  bool _cycleCompletions = false;

  @override
  void initState() {
    super.initState();
    _loadPreferences();
    _loadSystemPushStatus();
  }

  Future<void> _loadSystemPushStatus() async {
    if (kIsWeb) return;
    try {
      final settings =
          await FirebaseMessaging.instance.getNotificationSettings();
      final status = settings.authorizationStatus;
      if (!mounted) return;
      setState(() {
        _systemPushAuthorized = status == AuthorizationStatus.authorized ||
            status == AuthorizationStatus.provisional;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _systemPushAuthorized = null;
      });
    }
  }

  Future<void> _loadPreferences() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await dio.get('/auth/me');
      final data = response.data as Map<String, dynamic>;
      final user = data['user'] ?? data;

      final prefs = user['notification_preferences'] as Map<String, dynamic>?;

      if (prefs != null) {
        setState(() {
          _emailNotifications = prefs['email'] ?? true;
          _pushNotifications = prefs['push'] ?? true;
          _smsNotifications = prefs['sms'] ?? false;
          _paymentRequests = prefs['payment_requests'] ?? true;
          _paymentReminders = prefs['payment_reminders'] ?? true;
          _paymentConfirmations = prefs['payment_confirmations'] ?? true;
          _groupInvitations = prefs['group_invitations'] ?? true;
          _cycleCompletions = prefs['cycle_completions'] ?? false;
        });
      }

      setState(() {
        _loading = false;
      });
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Load preferences error: ${e.response?.data}');
      }

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Failed to load preferences';
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

  Future<void> _savePreferences() async {
    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final data = {
        'notification_preferences': {
          'email': _emailNotifications,
          'push': _pushNotifications,
          'sms': _smsNotifications,
          'payment_requests': _paymentRequests,
          'payment_reminders': _paymentReminders,
          'payment_confirmations': _paymentConfirmations,
          'group_invitations': _groupInvitations,
          'cycle_completions': _cycleCompletions,
        },
      };

      if (Env.debug) {
        debugPrint('Saving preferences: ${json.encode(data)}');
      }

      final response = await dio.put('/auth/me', data: data);

      if (Env.debug) {
        debugPrint('Save response: ${response.data}');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('✓ Notification preferences saved'),
            backgroundColor: Colors.green,
            duration: Duration(seconds: 2),
          ),
        );
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Save preferences error: ${e.response?.statusCode}');
        debugPrint('Error data: ${e.response?.data}');
        debugPrint('Error message: ${e.message}');
      }

      if (mounted) {
        final errorMessage = e.response?.data?['message'] as String? ??
            'Failed to save preferences. Please try again.';

        setState(() {
          _error = errorMessage;
        });

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('✗ $errorMessage'),
            backgroundColor: Colors.red,
            duration: const Duration(seconds: 3),
          ),
        );
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected save error: $e');
      }

      if (mounted) {
        setState(() {
          _error = 'An unexpected error occurred';
        });

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('✗ An unexpected error occurred'),
            backgroundColor: Colors.red,
            duration: Duration(seconds: 3),
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
        title: const Text('Notification Preferences'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(AppTheme.spaceMedium),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Container(
                    padding: const EdgeInsets.all(AppTheme.spaceLarge),
                    decoration: BoxDecoration(
                      gradient: AppTheme.primaryGradient,
                      borderRadius: BorderRadius.circular(AppTheme.radiusXL),
                      boxShadow: AppTheme.softShadow,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Stay in the loop',
                          style: Theme.of(context)
                              .textTheme
                              .headlineSmall
                              ?.copyWith(color: Colors.white),
                        ),
                        const SizedBox(height: AppTheme.spaceS),
                        Text(
                          'Control how and when TogetherPay alerts you about new requests and reminders.',
                          style:
                              Theme.of(context).textTheme.bodyMedium?.copyWith(
                                    color: Colors.white.withOpacity(0.9),
                                  ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: AppTheme.spaceLarge),

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

                  if (_systemPushAuthorized == false) ...[
                    _buildSystemPushWarning(),
                    const SizedBox(height: AppTheme.spaceLarge),
                  ],

                  // Notification Channels
                  _buildSectionHeader(
                    'Notification Channels',
                    Icons.notifications_outlined,
                  ),
                  const SizedBox(height: AppTheme.spaceSmall),
                  Card(
                    elevation: 0,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(AppTheme.radiusL),
                      side: BorderSide(
                        color: Theme.of(context)
                            .colorScheme
                            .outline
                            .withOpacity(0.12),
                      ),
                    ),
                    child: Column(
                      children: [
                        SwitchListTile(
                          secondary: const Icon(Icons.email_outlined),
                          title: const Text('Email Notifications'),
                          subtitle:
                              const Text('Receive notifications via email'),
                          value: _emailNotifications,
                          onChanged: (value) {
                            setState(() {
                              _emailNotifications = value;
                            });
                          },
                        ),
                        const Divider(height: 1),
                        SwitchListTile(
                          secondary:
                              const Icon(Icons.notifications_active_outlined),
                          title: const Text('Push Notifications'),
                          subtitle: const Text('Receive push notifications'),
                          value: _pushNotifications,
                          onChanged: (value) {
                            setState(() {
                              _pushNotifications = value;
                            });
                          },
                        ),
                        const Divider(height: 1),
                        SwitchListTile(
                          secondary: const Icon(Icons.sms_outlined),
                          title: const Text('SMS Notifications'),
                          subtitle: const Text('Receive text messages'),
                          value: _smsNotifications,
                          onChanged: (value) {
                            setState(() {
                              _smsNotifications = value;
                            });
                          },
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: AppTheme.spaceLarge),

                  // Notification Types
                  _buildSectionHeader(
                    'Notification Types',
                    Icons.category_outlined,
                  ),
                  const SizedBox(height: AppTheme.spaceSmall),
                  Card(
                    elevation: 0,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(AppTheme.radiusL),
                      side: BorderSide(
                        color: Theme.of(context)
                            .colorScheme
                            .outline
                            .withOpacity(0.12),
                      ),
                    ),
                    child: Column(
                      children: [
                        SwitchListTile(
                          secondary: const Icon(Icons.request_page_outlined),
                          title: const Text('Payment Requests'),
                          subtitle:
                              const Text('New payment requests sent to you'),
                          value: _paymentRequests,
                          onChanged: (value) {
                            setState(() {
                              _paymentRequests = value;
                            });
                          },
                        ),
                        const Divider(height: 1),
                        SwitchListTile(
                          secondary:
                              const Icon(Icons.notifications_none_outlined),
                          title: const Text('Payment Reminders'),
                          subtitle:
                              const Text('Reminders for pending payments'),
                          value: _paymentReminders,
                          onChanged: (value) {
                            setState(() {
                              _paymentReminders = value;
                            });
                          },
                        ),
                        const Divider(height: 1),
                        SwitchListTile(
                          secondary: const Icon(Icons.check_circle_outline),
                          title: const Text('Payment Confirmations'),
                          subtitle: const Text(
                              'Confirmations when payments complete'),
                          value: _paymentConfirmations,
                          onChanged: (value) {
                            setState(() {
                              _paymentConfirmations = value;
                            });
                          },
                        ),
                        const Divider(height: 1),
                        SwitchListTile(
                          secondary: const Icon(Icons.group_add_outlined),
                          title: const Text('Group Invitations'),
                          subtitle:
                              const Text('Invitations to join new groups'),
                          value: _groupInvitations,
                          onChanged: (value) {
                            setState(() {
                              _groupInvitations = value;
                            });
                          },
                        ),
                        const Divider(height: 1),
                        SwitchListTile(
                          secondary: const Icon(Icons.event_available_outlined),
                          title: const Text('Cycle Completions'),
                          subtitle: const Text('When billing cycles complete'),
                          value: _cycleCompletions,
                          onChanged: (value) {
                            setState(() {
                              _cycleCompletions = value;
                            });
                          },
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: AppTheme.spaceLarge),

                  // Info Card
                  Container(
                    padding: const EdgeInsets.all(AppTheme.spaceMedium),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryLight,
                      borderRadius: BorderRadius.circular(AppTheme.radiusL),
                      border: Border.all(
                        color: AppTheme.primary.withOpacity(0.25),
                      ),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.info_outline, color: AppTheme.primaryDark),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            'We recommend keeping push notifications enabled so you never miss a payment request.',
                            style: TextStyle(
                              color: AppTheme.gray900,
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
                    onPressed: _saving ? null : _savePreferences,
                    style: FilledButton.styleFrom(
                      backgroundColor: AppTheme.primary,
                      padding: const EdgeInsets.symmetric(
                        vertical: AppTheme.spaceM,
                      ),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(AppTheme.radiusL),
                      ),
                    ),
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
                    label: Text(_saving ? 'Saving...' : 'Save Preferences'),
                  ),

                  const SizedBox(height: AppTheme.spaceXLarge),
                ],
              ),
            ),
    );
  }

  Widget _buildSectionHeader(String title, IconData icon) {
    return Row(
      children: [
        Icon(icon, size: 20, color: Theme.of(context).colorScheme.primary),
        const SizedBox(width: AppTheme.spaceSmall),
        Text(
          title,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
        ),
      ],
    );
  }

  Widget _buildSystemPushWarning() {
    return Container(
      padding: const EdgeInsets.all(AppTheme.spaceMedium),
      decoration: BoxDecoration(
        color: AppTheme.warning.withOpacity(0.08),
        borderRadius: BorderRadius.circular(AppTheme.radiusL),
        border: Border.all(
          color: AppTheme.warning.withOpacity(0.35),
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.warning_amber_rounded, color: AppTheme.warning),
          const SizedBox(width: AppTheme.spaceS),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Push notifications are off',
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                ),
                const SizedBox(height: AppTheme.spaceXS),
                Text(
                  'Enable notifications for TogetherPay in Settings to receive timely payment alerts.',
                  style: Theme.of(context)
                      .textTheme
                      .bodySmall
                      ?.copyWith(color: AppTheme.gray700),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
