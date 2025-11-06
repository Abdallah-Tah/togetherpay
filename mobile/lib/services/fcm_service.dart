import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

import '../env.dart';
import '../main.dart';
import '../pay_screen.dart';

/// Firebase Cloud Messaging service for push notifications
///
/// Handles FCM token registration, permission requests, and notification delivery
class FCMService {
  static final FirebaseMessaging _messaging = FirebaseMessaging.instance;

  /// Initialize FCM and request permissions
  static Future<void> initialize() async {
    if (kIsWeb) {
      if (Env.debug) {
        debugPrint('FCM: Skipping initialization on web platform');
      }
      return;
    }

    try {
      // Request notification permissions
      final settings = await _messaging.requestPermission(
        alert: true,
        badge: true,
        sound: true,
        provisional: false,
      );

      if (Env.debug) {
        debugPrint('FCM: Permission status: ${settings.authorizationStatus}');
      }

      if (settings.authorizationStatus == AuthorizationStatus.authorized ||
          settings.authorizationStatus == AuthorizationStatus.provisional) {

        // On iOS, we need to get APNs token first
        if (defaultTargetPlatform == TargetPlatform.iOS) {
          await _waitForApnsToken();
        }

        // Get FCM token with retry logic
        final token = await _getFCMTokenWithRetry();

        if (token != null) {
          if (Env.debug) {
            debugPrint('FCM: Token obtained: ${token.substring(0, 20)}...');
          }

          // Register token with Laravel backend
          await _registerTokenWithBackend(token);

          // Listen for token refresh
          FirebaseMessaging.instance.onTokenRefresh.listen((newToken) {
            if (Env.debug) {
              debugPrint('FCM: Token refreshed');
            }
            _registerTokenWithBackend(newToken);
          });
        } else {
          if (Env.debug) {
            debugPrint('FCM: Failed to get token after retries');
          }
        }
      } else {
        if (Env.debug) {
          debugPrint('FCM: Notification permission denied');
        }
      }

      // Set up message handlers
      _setupMessageHandlers();
    } catch (e) {
      if (Env.debug) {
        debugPrint('FCM: Initialization error: $e');
      }
    }
  }

  /// Wait for APNs token on iOS (required before FCM token)
  static Future<void> _waitForApnsToken() async {
    if (Env.debug) {
      debugPrint('FCM: Waiting for APNs token (iOS)...');
    }

    try {
      // Try to get APNs token
      String? apnsToken = await _messaging.getAPNSToken();

      // If null, wait and retry a few times
      int retries = 0;
      while (apnsToken == null && retries < 5) {
        await Future.delayed(Duration(milliseconds: 500 * (retries + 1)));
        apnsToken = await _messaging.getAPNSToken();
        retries++;

        if (Env.debug) {
          debugPrint('FCM: APNs token retry $retries/5');
        }
      }

      if (apnsToken != null) {
        if (Env.debug) {
          debugPrint('FCM: APNs token obtained: ${apnsToken.substring(0, 20)}...');
        }
      } else {
        if (Env.debug) {
          debugPrint('FCM: APNs token still null after retries - this may cause issues');
        }
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('FCM: Error getting APNs token: $e');
      }
    }
  }

  /// Get FCM token with retry logic
  static Future<String?> _getFCMTokenWithRetry() async {
    int retries = 0;
    const maxRetries = 3;

    while (retries < maxRetries) {
      try {
        final token = await _messaging.getToken();
        if (token != null) {
          return token;
        }
      } catch (e) {
        if (Env.debug) {
          debugPrint('FCM: Error getting token (attempt ${retries + 1}/$maxRetries): $e');
        }
      }

      retries++;
      if (retries < maxRetries) {
        // Wait before retrying (exponential backoff)
        await Future.delayed(Duration(seconds: retries));
      }
    }

    return null;
  }

  /// Register FCM token with Laravel backend
  static Future<void> _registerTokenWithBackend(String token) async {
    try {
      await dio.post('/devices/register', data: {
        'fcm_token': token,
        'platform': defaultTargetPlatform.name,
        'device_name': '${defaultTargetPlatform.name} Device',
      });

      if (Env.debug) {
        debugPrint('FCM: Token registered with backend');
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('FCM: Failed to register token with backend: $e');
      }
    }
  }

  /// Set up foreground, background, and terminated message handlers
  static void _setupMessageHandlers() {
    // Handle foreground messages
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      if (Env.debug) {
        debugPrint('FCM: Foreground message received');
        debugPrint('FCM: Title: ${message.notification?.title}');
        debugPrint('FCM: Body: ${message.notification?.body}');
        debugPrint('FCM: Data: ${message.data}');
      }

      // Show local notification when app is in foreground
      _showForegroundNotification(message);
    });

    // Handle notification taps when app is in background
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      if (Env.debug) {
        debugPrint('FCM: Notification tapped (background)');
      }
      _handleNotificationTap(message);
    });

    // Check if app was opened from terminated state via notification
    _checkInitialMessage();
  }

  /// Check if app was launched from a notification
  static Future<void> _checkInitialMessage() async {
    final initialMessage = await FirebaseMessaging.instance.getInitialMessage();

    if (initialMessage != null) {
      if (Env.debug) {
        debugPrint('FCM: App opened from terminated state via notification');
      }
      _handleNotificationTap(initialMessage);
    }
  }

  /// Show notification when app is in foreground
  static void _showForegroundNotification(RemoteMessage message) {
    // For now, just show a SnackBar
    // You can use flutter_local_notifications plugin for richer notifications
    final context = globalNavigatorKey.currentContext;
    if (context != null && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(message.notification?.body ?? 'New notification'),
          action: SnackBarAction(
            label: 'View',
            onPressed: () => _handleNotificationTap(message),
          ),
          duration: const Duration(seconds: 5),
        ),
      );
    }
  }

  /// Handle notification tap - navigate to appropriate screen
  static void _handleNotificationTap(RemoteMessage message) {
    final prCode = message.data['pr_code'] as String?;
    final deepLink = message.data['deep_link'] as String?;

    if (prCode != null) {
      // Navigate to payment screen
      final context = globalNavigatorKey.currentContext;
      if (context != null && context.mounted) {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => PayScreen(
              prCode: prCode,
              deepLink: deepLink,
            ),
          ),
        );
      }
    } else {
      if (Env.debug) {
        debugPrint('FCM: No pr_code in notification data, cannot navigate');
      }
    }
  }

  /// Get current FCM token (for debugging)
  static Future<String?> getToken() async {
    try {
      return await _messaging.getToken();
    } catch (e) {
      if (Env.debug) {
        debugPrint('FCM: Error getting token: $e');
      }
      return null;
    }
  }

  /// Delete FCM token (for logout)
  static Future<void> deleteToken() async {
    try {
      await _messaging.deleteToken();
      if (Env.debug) {
        debugPrint('FCM: Token deleted');
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('FCM: Error deleting token: $e');
      }
    }
  }
}

/// Background message handler (must be top-level function)
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  if (Env.debug) {
    debugPrint('FCM: Background message received');
    debugPrint('FCM: Title: ${message.notification?.title}');
    debugPrint('FCM: Body: ${message.notification?.body}');
  }
  // Don't need to do anything here - OS will show the notification
  // User tap will be handled by onMessageOpenedApp
}
