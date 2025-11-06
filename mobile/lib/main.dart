import 'package:dio/dio.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'env.dart';
import 'screens/groups/groups_list_screen.dart';
import 'screens/auth/login_screen.dart';
import 'screens/onboarding/onboarding_screen.dart';
import 'screens/profile/profile_screen.dart';
import 'services/fcm_service.dart';
import 'utils/theme.dart';
import 'widgets/app_background.dart';

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

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize Firebase (skip on web)
  if (!kIsWeb) {
    try {
      await Firebase.initializeApp();

      // Set up background message handler
      FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

      if (Env.debug) {
        debugPrint('Firebase initialized successfully');
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Firebase initialization failed: $e');
        debugPrint('Push notifications will be disabled. Add google-services.json to enable.');
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
  bool _hasCompletedOnboarding = false;

  @override
  void initState() {
    super.initState();
    _setupApp();
  }

  Future<void> _setupApp() async {
    // Check if onboarding has been completed
    if (!kIsWeb) {
      final onboardingCompleted = await storage.read(key: 'onboarding_completed');
      _hasCompletedOnboarding = onboardingCompleted == 'true';
    } else {
      // Skip onboarding on web
      _hasCompletedOnboarding = true;
    }

    // Load auth token from secure storage
    if (!kIsWeb) {
      _authToken = await storage.read(key: 'auth_token');
    }

    if (_authToken != null) {
      // Set authorization header for all API requests
      dio.options.headers['Authorization'] = 'Bearer $_authToken';

      // Initialize FCM for push notifications (only when authenticated)
      if (!kIsWeb) {
        await FCMService.initialize();
      }
    }

    setState(() {
      _isInitializing = false;
    });
  }

  /// Handle onboarding completion
  void _handleOnboardingComplete() {
    setState(() {
      _hasCompletedOnboarding = true;
    });
  }

  /// Handle logout and clear auth token
  Future<void> _handleLogout() async {
    try {
      // Delete FCM token
      if (!kIsWeb) {
        await FCMService.deleteToken();
      }

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

    // Initialize FCM for push notifications after successful login
    if (token != null && !kIsWeb) {
      await FCMService.initialize();
    }

    setState(() {
      _authToken = token;
    });
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
          : !_hasCompletedOnboarding
              ? OnboardingScreen(onComplete: _handleOnboardingComplete)
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
  final Future<void> Function() onLogout;

  const MainScaffold({
    super.key,
    required this.onLogout,
  });

  @override
  State<MainScaffold> createState() => _MainScaffoldState();
}

class _MainScaffoldState extends State<MainScaffold> {
  int _currentIndex = 0;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final screens = [
      const GroupsListScreen(),
      const PlaceholderScreen(
        title: 'Payments',
        message:
            'Payment requests screen temporarily disabled while converting from Firebase to Laravel API',
      ),
      ProfileScreen(onLogout: widget.onLogout),
    ];

    return Scaffold(
      extendBody: true,
      body: IndexedStack(
        index: _currentIndex,
        children: screens,
      ),
      bottomNavigationBar: Padding(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: theme.colorScheme.surface,
            borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
            boxShadow: AppTheme.cardShadow,
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
            child: NavigationBar(
              height: 68,
              backgroundColor: theme.colorScheme.surface,
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
              selectedIndex: _currentIndex,
              onDestinationSelected: (index) {
                setState(() {
                  _currentIndex = index;
                });
              },
            ),
          ),
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
