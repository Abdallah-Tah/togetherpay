import 'dart:io';

import 'package:dio/dio.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'env.dart';
import 'pay_screen.dart';

/// Global Dio instance for API calls
final dio = Dio(BaseOptions(baseUrl: Env.apiBase));

/// Secure storage for auth tokens
const storage = FlutterSecureStorage();

/// Local notifications plugin for foreground messages
final localNotifications = FlutterLocalNotificationsPlugin();

/// Background message handler
/// Must be top-level function for Firebase
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  if (Env.debug) {
    print('Background message: ${message.messageId}');
  }
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Firebase only on mobile platforms
  if (!kIsWeb) {
    try {
      await Firebase.initializeApp();
      FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
    } catch (e) {
      if (Env.debug) {
        print('Firebase initialization error: $e');
      }
    }

    // Initialize local notifications
    const initSettings = InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      iOS: DarwinInitializationSettings(
        requestAlertPermission: true,
        requestBadgePermission: true,
        requestSoundPermission: true,
      ),
    );

    await localNotifications.initialize(
      initSettings,
      onDidReceiveNotificationResponse: (details) {
        if (Env.debug) {
          print('Notification tapped: ${details.payload}');
        }
      },
    );
  }

  runApp(const TogetherPayApp());
}

class TogetherPayApp extends StatefulWidget {
  const TogetherPayApp({super.key});

  @override
  State<TogetherPayApp> createState() => _TogetherPayAppState();
}

class _TogetherPayAppState extends State<TogetherPayApp> {
  final _navigatorKey = GlobalKey<NavigatorState>();
  String? _authToken;

  @override
  void initState() {
    super.initState();
    _setupApp();
    _setupPushHandlers();
  }

  Future<void> _setupApp() async {
    // Load auth token from secure storage
    if (!kIsWeb) {
      _authToken = await storage.read(key: 'auth_token');
    }

    if (_authToken != null) {
      // Set authorization header for all API requests
      dio.options.headers['Authorization'] = 'Bearer $_authToken';
    }

    // Request notification permissions (mobile only)
    if (!kIsWeb) {
      final messaging = FirebaseMessaging.instance;
      final settings = await messaging.requestPermission(
        alert: true,
        badge: true,
        sound: true,
      );

      if (Env.debug) {
        print('Notification permission: ${settings.authorizationStatus}');
      }

      // Get FCM token and register with backend
      final fcmToken = await messaging.getToken();
      if (fcmToken != null) {
        if (Env.debug) {
          print('FCM Token: $fcmToken');
        }

        // Register device with backend
        try {
          await dio.post('/devices/register', data: {
            'fcm_token': fcmToken,
            'platform': Platform.isIOS ? 'ios' : 'android',
          });
        } catch (e) {
          if (Env.debug) {
            print('Failed to register FCM token: $e');
          }
        }
      }

      // Handle notification that launched the app
      final initialMessage = await messaging.getInitialMessage();
      if (initialMessage != null) {
        _handlePushNotification(initialMessage);
      }
    }
  }

  void _setupPushHandlers() {
    // Skip push handlers on web platform
    if (kIsWeb) return;

    // Handle notification when app is in foreground
    FirebaseMessaging.onMessage.listen((message) {
      if (Env.debug) {
        print('Foreground message: ${message.notification?.title}');
      }

      // Show local notification
      _showLocalNotification(message);
    });

    // Handle notification when app is in background but opened
    FirebaseMessaging.onMessageOpenedApp.listen(_handlePushNotification);
  }

  void _handlePushNotification(RemoteMessage message) {
    final data = message.data;
    final prCode = data['pr_code'] as String?;
    final deepLink = data['deep_link'] as String?;

    if (prCode != null) {
      // Navigate to payment screen
      _navigatorKey.currentState?.push(
        MaterialPageRoute(
          builder: (_) => PayScreen(
            prCode: prCode,
            deepLink: deepLink,
          ),
        ),
      );
    }
  }

  Future<void> _showLocalNotification(RemoteMessage message) async {
    const androidDetails = AndroidNotificationDetails(
      'togetherpay_default',
      'Payment Requests',
      channelDescription: 'Payment request notifications',
      importance: Importance.high,
      priority: Priority.high,
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

    await localNotifications.show(
      message.hashCode,
      message.notification?.title ?? 'Payment Request',
      message.notification?.body ?? 'Tap to view',
      details,
      payload: message.data['pr_code'],
    );
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: _navigatorKey,
      title: Env.appName,
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF14B8A6), // Teal
          brightness: Brightness.light,
        ),
      ),
      darkTheme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF14B8A6),
          brightness: Brightness.dark,
        ),
      ),
      home: const HomeScreen(),
    );
  }
}

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My Groups'),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () {
              // TODO: Show notifications/payment requests
            },
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(
              Icons.groups_outlined,
              size: 64,
              color: Colors.grey,
            ),
            const SizedBox(height: 16),
            Text(
              'Your groups will appear here',
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                    color: Colors.grey,
                  ),
            ),
            const SizedBox(height: 32),
            // Demo button to test payment flow
            ElevatedButton.icon(
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const DemoGroupScreen(),
                  ),
                );
              },
              icon: const Icon(Icons.play_arrow),
              label: const Text('Demo Group'),
            ),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          // TODO: Create new group
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Create group - Coming soon')),
          );
        },
        icon: const Icon(Icons.add),
        label: const Text('New Group'),
      ),
    );
  }
}

class DemoGroupScreen extends StatelessWidget {
  const DemoGroupScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('T-Mobile Family Plan'),
      ),
      body: ListView(
        children: [
          // Group info card
          Card(
            margin: const EdgeInsets.all(16),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Your Share',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '\$50.00',
                    style: Theme.of(context).textTheme.headlineLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Due today',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Colors.orange,
                        ),
                  ),
                ],
              ),
            ),
          ),

          // Payment requests list
          ListTile(
            leading: const CircleAvatar(
              child: Icon(Icons.payment),
            ),
            title: const Text('October 2025 Payment'),
            subtitle: const Text('PR_2025-10_G5_M7_demo'),
            trailing: FilledButton(
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const PayScreen(
                      prCode: 'PR_2025-10_G5_M7_demo',
                      deepLink: null,
                    ),
                  ),
                );
              },
              child: const Text('Pay'),
            ),
          ),

          const Divider(),

          // Past payments
          ListTile(
            leading: CircleAvatar(
              backgroundColor: Colors.green.shade100,
              child: Icon(Icons.check, color: Colors.green.shade700),
            ),
            title: const Text('September 2025'),
            subtitle: const Text('Paid on Sep 15'),
            trailing: const Text(
              '\$50.00',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }
}
