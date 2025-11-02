import 'package:dio/dio.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:url_launcher/url_launcher.dart';

import 'env.dart';
import 'pay_screen.dart';
import 'screens/groups/groups_list_screen.dart';
import 'screens/auth/login_screen.dart';
import 'screens/settings/notification_preferences_screen.dart';
import 'screens/settings/payment_methods_screen.dart';
import 'utils/theme.dart';

/// Global Dio instance for API calls
final dio = Dio(BaseOptions(
  baseUrl: Env.apiBase,
  headers: {
    'ngrok-skip-browser-warning': 'true', // Bypass ngrok warning page
  },
));

/// Secure storage for auth tokens
const storage = FlutterSecureStorage();

/// Global navigator key for navigation
final GlobalKey<NavigatorState> globalNavigatorKey = GlobalKey<NavigatorState>();

/// Background message handler for FCM (must be top-level function)
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  if (Env.debug) {
    debugPrint('Background message received: ${message.messageId}');
    debugPrint('Background message data: ${message.data}');
  }
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Firebase (skip on web for now)
  if (!kIsWeb) {
    try {
      await Firebase.initializeApp();

      // Set up background message handler
      FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

      if (Env.debug) {
        debugPrint('Firebase initialized successfully');
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Firebase initialization error: $e');
      }
    }
  }

  runApp(const TogetherPayApp());
}

class TogetherPayApp extends StatefulWidget {
  const TogetherPayApp({super.key});

  @override
  State<TogetherPayApp> createState() => _TogetherPayAppState();
}

class _TogetherPayAppState extends State<TogetherPayApp> {
  String? _authToken;
  bool _isInitializing = true;

  @override
  void initState() {
    super.initState();
    _setupApp();
  }

  Future<void> _setupApp() async {
    // Load auth token from secure storage
    if (!kIsWeb) {
      _authToken = await storage.read(key: 'auth_token');
    }

    if (_authToken != null) {
      // Set authorization header for all API requests
      dio.options.headers['Authorization'] = 'Bearer $_authToken';

      // Setup Firebase Cloud Messaging if authenticated
      if (!kIsWeb) {
        await _setupFirebaseMessaging();
      }
    }

    setState(() {
      _isInitializing = false;
    });
  }

  /// Setup Firebase Cloud Messaging with sound and tap handlers
  Future<void> _setupFirebaseMessaging() async {
    try {
      final messaging = FirebaseMessaging.instance;
      final devicePlatform = _devicePlatformParam();

      // Request permission for iOS
      final settings = await messaging.requestPermission(
        alert: true,
        badge: true,
        sound: true,
        provisional: false,
      );

      if (settings.authorizationStatus == AuthorizationStatus.authorized) {
        if (Env.debug) {
          debugPrint('User granted notification permission');
        }

        // Get FCM token
        final token = await messaging.getToken();
        if (token != null) {
          if (Env.debug) {
            debugPrint('FCM Token: $token');
          }

          // Register token with backend
          try {
            await dio.post('/devices/register', data: {
              'fcm_token': token,
              'platform': devicePlatform,
            });
            if (Env.debug) {
              debugPrint('FCM token registered with backend');
            }
          } catch (e) {
            if (Env.debug) {
              debugPrint('Failed to register FCM token: $e');
            }
          }
        }

        // Listen for token refresh
        messaging.onTokenRefresh.listen((newToken) async {
          if (Env.debug) {
            debugPrint('FCM Token refreshed: $newToken');
          }
          try {
            await dio.post('/devices/register', data: {
              'fcm_token': newToken,
              'platform': devicePlatform,
            });
          } catch (e) {
            if (Env.debug) {
              debugPrint('Failed to register refreshed FCM token: $e');
            }
          }
        });

        // Handle foreground messages (app is open)
        FirebaseMessaging.onMessage.listen((RemoteMessage message) {
          if (Env.debug) {
            debugPrint('Foreground message received: ${message.messageId}');
            debugPrint('Message data: ${message.data}');
            debugPrint('Notification: ${message.notification?.toMap()}');
          }

          // Show in-app notification or handle silently
          if (message.notification != null) {
            _showInAppNotification(message);
          }
        });

        // Handle notification tap when app is in background or terminated
        FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
          if (Env.debug) {
            debugPrint('Notification tapped (app in background): ${message.messageId}');
          }
          _handleNotificationTap(message);
        });

        // Check if app was opened from a terminated state via notification
        final initialMessage = await messaging.getInitialMessage();
        if (initialMessage != null) {
          if (Env.debug) {
            debugPrint('App opened from notification (terminated state)');
          }
          _handleNotificationTap(initialMessage);
        }
      } else {
        if (Env.debug) {
          debugPrint('User declined notification permission');
        }
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Firebase Messaging setup error: $e');
      }
    }
  }

  /// Show in-app notification when app is in foreground
  void _showInAppNotification(RemoteMessage message) {
    final notification = message.notification;
    if (notification == null) return;

    final context = globalNavigatorKey.currentContext;
    if (context == null || !context.mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (notification.title != null)
              Text(
                notification.title!,
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
            if (notification.body != null)
              Text(notification.body!),
          ],
        ),
        action: SnackBarAction(
          label: 'View',
          onPressed: () => _handleNotificationTap(message),
        ),
        duration: const Duration(seconds: 5),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  /// Handle notification tap - open Cash App or payment screen
  Future<void> _handleNotificationTap(RemoteMessage message) async {
    final data = message.data;

    // Extract payment request data
    final prCode = data['pr_code'] as String?;
    final deepLink = data['deep_link'] as String?;

    if (Env.debug) {
      debugPrint('Handling notification tap - PR Code: $prCode, Deep Link: $deepLink');
    }

    if (deepLink != null && deepLink.isNotEmpty) {
      // Try to open Cash App or Venmo directly
      try {
        final uri = Uri.parse(deepLink);
        if (await canLaunchUrl(uri)) {
          await launchUrl(
            uri,
            mode: LaunchMode.externalApplication,
          );
          if (Env.debug) {
            debugPrint('Opened Cash App/Venmo with deep link');
          }
          return;
        }
      } catch (e) {
        if (Env.debug) {
          debugPrint('Failed to open deep link: $e');
        }
      }
    }

    // Fallback: Navigate to payment screen if we have a PR code
    if (prCode != null && prCode.isNotEmpty) {
      final context = globalNavigatorKey.currentContext;
      if (context != null && context.mounted) {
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => PayScreen(
              prCode: prCode,
              deepLink: deepLink,
            ),
          ),
        );
      }
    }
  }

  /// Handle logout and clear auth token
  Future<void> _handleLogout() async {
    try {
      // Call logout API
      await dio.post('/auth/logout');
    } catch (e) {
      if (Env.debug) {
        debugPrint('Logout error: $e');
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

  String _devicePlatformParam() {
    switch (defaultTargetPlatform) {
      case TargetPlatform.iOS:
        return 'ios';
      case TargetPlatform.android:
        return 'android';
      default:
        return 'ios';
    }
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: globalNavigatorKey,
      title: Env.appName,
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      themeMode: ThemeMode.system,
      home: _isInitializing
          ? const SplashScreen()
          : _authToken != null
              ? MainScaffold(onLogout: _handleLogout)
              : LoginScreenWrapper(onAuthSuccess: _handleAuthSuccess),
    );
  }
}

/// Splash screen shown during initialization with animations
class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _fadeAnimation;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    );

    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.0, 0.6, curve: Curves.easeOut),
      ),
    );

    _scaleAnimation = Tween<double>(begin: 0.8, end: 1.0).animate(
      CurvedAnimation(
        parent: _controller,
        curve: const Interval(0.0, 0.6, curve: Curves.elasticOut),
      ),
    );

    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              Theme.of(context).colorScheme.primary,
              Theme.of(context).colorScheme.primary.withValues(alpha: 0.8),
            ],
          ),
        ),
        child: Center(
          child: FadeTransition(
            opacity: _fadeAnimation,
            child: ScaleTransition(
              scale: _scaleAnimation,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 120,
                    height: 120,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(AppTheme.radiusXLarge),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.2),
                          blurRadius: 20,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: Icon(
                      Icons.account_balance_wallet_rounded,
                      size: 60,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                  ),
                  const SizedBox(height: AppTheme.spaceXLarge),
                  Text(
                    'TogetherPay',
                    style: Theme.of(context).textTheme.headlineLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                  ),
                  const SizedBox(height: AppTheme.spaceSmall),
                  Text(
                    'Split bills together',
                    style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                          color: Colors.white.withValues(alpha: 0.9),
                        ),
                  ),
                  const SizedBox(height: AppTheme.spaceXLarge * 2),
                  const SizedBox(
                    width: 32,
                    height: 32,
                    child: CircularProgressIndicator(
                      strokeWidth: 3,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  ),
                ],
              ),
            ),
          ),
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
    return LoginScreen(onAuthSuccess: onAuthSuccess);
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
    PlaceholderScreen(
      title: 'Payments',
      message:
          'Payment requests screen temporarily disabled while converting from Firebase to Laravel API',
    ),
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
class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  bool _loading = true;
  String? _error;
  Map<String, dynamic>? _user;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await dio.get('/auth/me');
      final data = response.data as Map<String, dynamic>;
      final userData = data['user'] ?? data;

      setState(() {
        _user = Map<String, dynamic>.from(userData as Map);
        _loading = false;
      });
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Profile load error: ${e.response?.data}');
      }

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Failed to load profile';
        _loading = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected profile error: $e');
      }

      setState(() {
        _error = 'An unexpected error occurred';
        _loading = false;
      });
    }
  }

  String _initials(String name) {
    final trimmed = name.trim();
    if (trimmed.isEmpty) return '?';

    final parts = trimmed.split(RegExp(r'\s+'));
    final buffer = StringBuffer();

    if (parts.isNotEmpty && parts.first.isNotEmpty) {
      buffer.write(parts.first[0].toUpperCase());
    }

    if (parts.length > 1 && parts.last.isNotEmpty) {
      buffer.write(parts.last[0].toUpperCase());
    }

    return buffer.isEmpty ? '?' : buffer.toString();
  }

  @override
  Widget build(BuildContext context) {
    final rawName = (_user?['name'] as String?)?.trim();
    final name = (rawName != null && rawName.isNotEmpty)
        ? rawName
        : (_loading ? 'Loading...' : 'Unknown user');

    final rawEmail = (_user?['email'] as String?)?.trim();
    final email = (rawEmail != null && rawEmail.isNotEmpty)
        ? rawEmail
        : (_loading ? '' : 'No email on file');

    return Scaffold(
      appBar: AppBar(
        title: const Text('Profile'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _loading ? null : _loadProfile,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadProfile,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            if (_loading)
              const LinearProgressIndicator(minHeight: 2),
            const SizedBox(height: 16),

            if (_error != null)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.orange.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.orange.shade200),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.error_outline, color: Colors.orange.shade700),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          _error!,
                          style: TextStyle(color: Colors.orange.shade900),
                        ),
                      ),
                      TextButton(
                        onPressed: _loadProfile,
                        child: const Text('Retry'),
                      ),
                    ],
                  ),
                ),
              ),

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
                        child: Text(
                          _initials(name),
                          style: TextStyle(
                            fontSize: 28,
                            fontWeight: FontWeight.bold,
                            color: Theme.of(context)
                                .colorScheme
                                .onPrimaryContainer,
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        name,
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      if (email.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          email,
                          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                color: Colors.grey,
                              ),
                        ),
                      ],
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
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const NotificationPreferencesScreen(),
                  ),
                );
              },
            ),

            ListTile(
              leading: const Icon(Icons.payment_outlined),
              title: const Text('Payment Methods'),
              subtitle: const Text('Manage Cash App and Venmo'),
              trailing: const Icon(Icons.chevron_right),
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const PaymentMethodsScreen(),
                  ),
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
      ),
    );
  }
}

/// Placeholder screen for disabled features
class PlaceholderScreen extends StatelessWidget {
  final String title;
  final String message;

  const PlaceholderScreen({
    super.key,
    required this.title,
    required this.message,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(title),
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.construction_rounded,
                size: 80,
                color: Colors.orange.shade300,
              ),
              const SizedBox(height: 24),
              Text(
                'Coming Soon',
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
            ],
          ),
        ),
      ),
    );
  }
}
