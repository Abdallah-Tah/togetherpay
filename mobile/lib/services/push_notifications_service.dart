import 'dart:async';

import 'package:dio/dio.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import '../env.dart';

/// Handles push notification registration and device sync with the API.
class PushNotificationsService {
  PushNotificationsService._();

  static final PushNotificationsService instance = PushNotificationsService._();

  bool _initialized = false;
  bool _initializing = false;
  String? _lastRegisteredToken;
  String? _pendingToken;
  Dio? _dio;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  Future<void> _ensureInitialized() async {
    if (_initialized || _initializing) {
      while (_initializing) {
        await Future.delayed(const Duration(milliseconds: 50));
      }
      return;
    }

    if (!_isMobilePlatform) return;

    _initializing = true;
    try {
      await Firebase.initializeApp();

      // Initialize local notifications
      await _initializeLocalNotifications();

      await FirebaseMessaging.instance
          .setForegroundNotificationPresentationOptions(
        alert: true,
        badge: true,
        sound: true,
      );

      final settings = await FirebaseMessaging.instance.requestPermission(
        alert: true,
        badge: true,
        sound: true,
        provisional: false,
      );

      if (Env.debug) {
        debugPrint(
          'Push permission status: ${settings.authorizationStatus.name}',
        );
      }

      FirebaseMessaging.instance.onTokenRefresh.listen((token) {
        if (Env.debug) {
          debugPrint('FCM token refreshed: ${token.substring(0, 50)}...');
        }
        _pendingToken = token;
        _registerTokenWithApi();
      });

      // Handle foreground messages
      FirebaseMessaging.onMessage.listen((message) {
        if (Env.debug) {
          debugPrint(
            'Push message received: '
            '${message.notification?.title ?? 'no-title'}',
          );
        }
        _showLocalNotification(message);
      });

      _initialized = true;
    } catch (e, stack) {
      if (Env.debug) {
        debugPrint('Push init failed: $e');
        debugPrint(stack.toString());
      }
    } finally {
      _initializing = false;
    }
  }

  Future<void> syncDeviceRegistration(Dio dio) async {
    _dio = dio;

    if (dio.options.headers['Authorization'] == null) {
      return;
    }

    await _ensureInitialized();
    if (!_initialized) return;

    try {
      _pendingToken ??= await FirebaseMessaging.instance.getToken();
    } on FirebaseException catch (e) {
      if (e.code == 'apns-token-not-set') {
        if (Env.debug) {
          debugPrint(
            'APNS token not ready yet; will retry device registration with exponential backoff...',
          );
        }
        // Retry with exponential backoff: 3s, 6s, 12s
        _retryRegistrationWithBackoff([3, 6, 12], 0);
        return;
      }
      rethrow;
    }
    await _registerTokenWithApi();
  }

  void clearCachedAuthState() {
    _lastRegisteredToken = null;
    _dio = null;
  }

  /// Retry device registration with exponential backoff
  void _retryRegistrationWithBackoff(List<int> delays, int attemptIndex) {
    if (attemptIndex >= delays.length) {
      if (Env.debug) {
        debugPrint('Max retry attempts reached for device registration');
      }
      return;
    }

    final delaySeconds = delays[attemptIndex];
    if (Env.debug) {
      debugPrint(
        'Retrying device registration in $delaySeconds seconds (attempt ${attemptIndex + 1}/${delays.length})...',
      );
    }

    Future.delayed(Duration(seconds: delaySeconds), () async {
      try {
        final token = await FirebaseMessaging.instance.getToken();
        if (token != null) {
          _pendingToken = token;
          await _registerTokenWithApi();
          if (Env.debug) {
            debugPrint('Device registration successful on retry!');
          }
        } else {
          // Token still null, try next retry
          _retryRegistrationWithBackoff(delays, attemptIndex + 1);
        }
      } on FirebaseException catch (e) {
        if (e.code == 'apns-token-not-set') {
          // APNS still not ready, try next retry
          _retryRegistrationWithBackoff(delays, attemptIndex + 1);
        } else {
          if (Env.debug) {
            debugPrint('Retry error: $e');
          }
        }
      } catch (e) {
        if (Env.debug) {
          debugPrint('Unexpected retry error: $e');
        }
      }
    });
  }

  Future<void> _registerTokenWithApi() async {
    final dio = _dio;
    final token = _pendingToken;

    if (Env.debug) {
      debugPrint('=== FCM Token Registration Debug ===');
      debugPrint('Token: ${token ?? "NULL"}');
      debugPrint('Dio: ${dio != null ? "SET" : "NULL"}');
      debugPrint('Auth Header: ${dio?.options.headers['Authorization'] != null ? "SET" : "NULL"}');
      debugPrint('Last Token: $_lastRegisteredToken');
      debugPrint('Is Mobile: $_isMobilePlatform');
    }

    if (token == null || dio == null) return;
    if (dio.options.headers['Authorization'] == null) return;
    if (_lastRegisteredToken == token) {
      if (Env.debug) {
        debugPrint('Token already registered, skipping...');
      }
      return;
    }
    if (!_isMobilePlatform) return;

    final platform =
        defaultTargetPlatform == TargetPlatform.iOS ? 'ios' : 'android';

    try {
      if (Env.debug) {
        debugPrint('Sending device registration to API...');
        debugPrint('Platform: $platform');
        debugPrint('Token (first 50): ${token.substring(0, 50)}...');
      }

      final response = await dio.post(
        '/devices/register',
        data: {
          'fcm_token': token,
          'platform': platform,
        },
      );

      _lastRegisteredToken = token;
      if (Env.debug) {
        debugPrint('✓ Device registered successfully!');
        debugPrint('Response: ${response.data}');
      }
    } catch (e, stack) {
      if (Env.debug) {
        debugPrint('✗ Failed to register device for push: $e');
        debugPrint(stack.toString());
      }
    }
  }

  /// Initialize local notifications plugin
  Future<void> _initializeLocalNotifications() async {
    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosSettings = DarwinInitializationSettings(
      requestAlertPermission: false,
      requestBadgePermission: false,
      requestSoundPermission: false,
    );

    const settings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );

    await _localNotifications.initialize(settings);

    if (Env.debug) {
      debugPrint('Local notifications initialized');
    }
  }

  /// Show local notification when app is in foreground
  Future<void> _showLocalNotification(RemoteMessage message) async {
    final notification = message.notification;

    if (notification == null) return;

    const androidDetails = AndroidNotificationDetails(
      'togetherpay_default',
      'Payment Requests',
      channelDescription: 'Notifications for payment requests and updates',
      importance: Importance.high,
      priority: Priority.high,
      showWhen: true,
      enableLights: true,
      enableVibration: true,
      playSound: true,
    );

    const iosDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
    );

    const details = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );

    await _localNotifications.show(
      notification.hashCode,
      notification.title,
      notification.body,
      details,
      payload: message.data['payment_request_id']?.toString(),
    );

    if (Env.debug) {
      debugPrint('Local notification displayed: ${notification.title}');
    }
  }
}

bool get _isMobilePlatform =>
    !kIsWeb &&
    (defaultTargetPlatform == TargetPlatform.iOS ||
        defaultTargetPlatform == TargetPlatform.android);
