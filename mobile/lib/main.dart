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
import 'screens/auth/login_screen.dart';
import 'screens/groups/groups_list_screen.dart';
import 'screens/payments/payment_requests_screen.dart';

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
  bool _isInitializing = true;

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

    setState(() {
      _isInitializing = false;
    });

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
          if (Env.debug) {
            print('Device registered successfully');
          }
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

  /// Handle logout and clear auth token
  Future<void> _handleLogout() async {
    try {
      // Call logout API
      await dio.post('/auth/logout');
    } catch (e) {
      if (Env.debug) {
        print('Logout error: $e');
      }
    }

    // Clear stored token
    await storage.delete(key: 'auth_token');

    // Clear auth header
    dio.options.headers.remove('Authorization');

    setState(() {
      _authToken = null;
    });
  }

  /// Handle successful login/registration
  Future<void> _handleAuthSuccess() async {
    final token = await storage.read(key: 'auth_token');
    setState(() {
      _authToken = token;
    });
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
      home: _isInitializing
          ? const SplashScreen()
          : _authToken != null
              ? MainScaffold(onLogout: _handleLogout)
              : LoginScreenWrapper(onAuthSuccess: _handleAuthSuccess),
    );
  }
}

/// Splash screen shown during initialization
class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.account_balance_wallet_rounded,
              size: 80,
              color: Theme.of(context).colorScheme.primary,
            ),
            const SizedBox(height: 16),
            Text(
              'TogetherPay',
              style: Theme.of(context).textTheme.headlineLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.primary,
                  ),
            ),
            const SizedBox(height: 32),
            const CircularProgressIndicator(),
          ],
        ),
      ),
    );
  }
}

/// Login screen wrapper that handles auth success callback
class LoginScreenWrapper extends StatelessWidget {
  final VoidCallback onAuthSuccess;

  const LoginScreenWrapper({
    super.key,
    required this.onAuthSuccess,
  });

  @override
  Widget build(BuildContext context) {
    return const LoginScreen();
  }
}

/// Main scaffold with bottom navigation
class MainScaffold extends StatefulWidget {
  final VoidCallback onLogout;

  const MainScaffold({
    super.key,
    required this.onLogout,
  });

  @override
  State<MainScaffold> createState() => _MainScaffoldState();
}

class _MainScaffoldState extends State<MainScaffold> {
  int _currentIndex = 0;

  final List<Widget> _screens = const [
    GroupsListScreen(),
    PaymentRequestsScreen(),
    ProfileScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.groups_outlined),
            selectedIcon: Icon(Icons.groups),
            label: 'Groups',
          ),
          NavigationDestination(
            icon: Icon(Icons.payment_outlined),
            selectedIcon: Icon(Icons.payment),
            label: 'Payments',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }
}

/// Profile screen with settings and logout
class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Profile'),
      ),
      body: ListView(
        children: [
          const SizedBox(height: 16),

          // User info section
          Padding(
            padding: const EdgeInsets.all(16),
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    CircleAvatar(
                      radius: 40,
                      backgroundColor:
                          Theme.of(context).colorScheme.primaryContainer,
                      child: Icon(
                        Icons.person,
                        size: 40,
                        color: Theme.of(context).colorScheme.onPrimaryContainer,
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'User Name',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'user@example.com',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: Colors.grey,
                          ),
                    ),
                  ],
                ),
              ),
            ),
          ),

          // Settings section
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Text(
              'Settings',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
          ),

          ListTile(
            leading: const Icon(Icons.notifications_outlined),
            title: const Text('Notifications'),
            subtitle: const Text('Manage notification preferences'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Coming soon')),
              );
            },
          ),

          ListTile(
            leading: const Icon(Icons.payment_outlined),
            title: const Text('Payment Methods'),
            subtitle: const Text('Manage Cash App and Venmo'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Coming soon')),
              );
            },
          ),

          ListTile(
            leading: const Icon(Icons.security_outlined),
            title: const Text('Security'),
            subtitle: const Text('Password and authentication'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Coming soon')),
              );
            },
          ),

          const Divider(),

          // About section
          ListTile(
            leading: const Icon(Icons.info_outline),
            title: const Text('About'),
            subtitle: const Text('Version 1.0.0'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              showAboutDialog(
                context: context,
                applicationName: 'TogetherPay',
                applicationVersion: '1.0.0',
                applicationIcon: Icon(
                  Icons.account_balance_wallet_rounded,
                  size: 48,
                  color: Theme.of(context).colorScheme.primary,
                ),
                children: const [
                  Text('Split bills together with ease'),
                ],
              );
            },
          ),

          ListTile(
            leading: const Icon(Icons.privacy_tip_outlined),
            title: const Text('Privacy Policy'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Coming soon')),
              );
            },
          ),

          ListTile(
            leading: const Icon(Icons.description_outlined),
            title: const Text('Terms of Service'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Coming soon')),
              );
            },
          ),

          const SizedBox(height: 16),

          // Logout button
          Padding(
            padding: const EdgeInsets.all(16),
            child: OutlinedButton.icon(
              onPressed: () async {
                final confirmed = await showDialog<bool>(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: const Text('Logout'),
                    content: const Text(
                      'Are you sure you want to logout?',
                    ),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.pop(context, false),
                        child: const Text('Cancel'),
                      ),
                      FilledButton(
                        onPressed: () => Navigator.pop(context, true),
                        child: const Text('Logout'),
                      ),
                    ],
                  ),
                );

                if (confirmed == true && context.mounted) {
                  // Find the MainScaffold ancestor and call logout
                  final mainScaffold =
                      context.findAncestorStateOfType<_TogetherPayAppState>();
                  if (mainScaffold != null) {
                    await mainScaffold._handleLogout();
                  }
                }
              },
              style: OutlinedButton.styleFrom(
                foregroundColor: Colors.red,
                side: const BorderSide(color: Colors.red),
              ),
              icon: const Icon(Icons.logout),
              label: const Text('Logout'),
            ),
          ),

          const SizedBox(height: 32),
        ],
      ),
    );
  }
}
