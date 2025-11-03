import 'package:flutter/foundation.dart';

/// Production API Configuration
class ProductionConfig {
  // API Configuration
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://api.togetherpay.app/api/v1',
  );

  static const String websocketUrl = String.fromEnvironment(
    'WS_URL',
    defaultValue: 'wss://api.togetherpay.app',
  );

  // Feature Flags
  static const bool enableAnalytics = bool.fromEnvironment(
    'ENABLE_ANALYTICS',
    defaultValue: true,
  );

  static const bool enableCrashReporting = bool.fromEnvironment(
    'ENABLE_CRASH_REPORTING',
    defaultValue: true,
  );

  static const bool enablePerformanceMonitoring = bool.fromEnvironment(
    'ENABLE_PERFORMANCE_MONITORING',
    defaultValue: true,
  );

  // App Configuration
  static const String appName = 'TogetherPay';
  static const String packageName = 'com.togetherpay.togetherpay';
  static const String iosBundleId = 'com.togetherpay.togetherpay';

  // Deep Linking
  static const String deepLinkScheme = 'togetherpay';
  static const String universalLinkDomain = 'tpay.app';
  static const List<String> universalLinkPaths = ['/p/', '/pay/'];

  // Cache Configuration
  static const Duration cacheExpiration = Duration(hours: 24);
  static const int maxCacheSize = 50 * 1024 * 1024; // 50 MB

  // API Timeouts
  static const Duration connectionTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);
  static const Duration sendTimeout = Duration(seconds: 30);

  // Retry Configuration
  static const int maxRetries = 3;
  static const Duration retryDelay = Duration(seconds: 2);

  // Logging
  static const bool enableDebugLogging = kDebugMode;
  static const bool enableNetworkLogging = kDebugMode;

  // Security
  static const bool enableCertificatePinning = !kDebugMode;
  static const List<String> pinnedCertificates = [
    // Add SHA-256 hashes of production SSL certificates
    // 'sha256/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
  ];

  // Payment Configuration
  static const String cashAppClientId = String.fromEnvironment('CASHAPP_CLIENT_ID');
  static const String venmoClientId = String.fromEnvironment('VENMO_CLIENT_ID');

  // Notification Configuration
  static const bool enablePushNotifications = true;
  static const String fcmSenderId = String.fromEnvironment('FCM_SENDER_ID');

  // Analytics
  static const String firebaseProjectId = String.fromEnvironment('FIREBASE_PROJECT_ID');

  // Build Information
  static const String environment = kDebugMode ? 'development' : 'production';
  static const String buildNumber = String.fromEnvironment(
    'BUILD_NUMBER',
    defaultValue: '1',
  );
  static const String versionName = String.fromEnvironment(
    'VERSION_NAME',
    defaultValue: '1.0.0',
  );

  // Validation
  static bool get isConfigured {
    if (apiBaseUrl.isEmpty) return false;
    if (kReleaseMode && enableCertificatePinning && pinnedCertificates.isEmpty) {
      return false;
    }
    return true;
  }

  // Debug Information
  static Map<String, dynamic> toJson() => {
        'environment': environment,
        'apiBaseUrl': apiBaseUrl,
        'websocketUrl': websocketUrl,
        'appName': appName,
        'packageName': packageName,
        'enableAnalytics': enableAnalytics,
        'enableCrashReporting': enableCrashReporting,
        'enableDebugLogging': enableDebugLogging,
        'buildNumber': buildNumber,
        'versionName': versionName,
      };
}
