/// Environment configuration
///
/// Set API_BASE at compile time:
/// flutter run --dart-define=API_BASE=https://api.togetherpay.app/api/v1
class Env {
  /// Base URL for the Laravel API
  static const String apiBase = String.fromEnvironment(
    'API_BASE',
    defaultValue: 'https://www.togetherpay.app/api/v1',
  );

  /// Enable debug logging
  static const bool debug = bool.fromEnvironment('DEBUG', defaultValue: false);

  /// App name
  static const String appName = 'TogetherPay';

  /// Base URL for web app links (SmartPay, invitations)
  static const String appBaseUrl = String.fromEnvironment(
    'APP_BASE',
    defaultValue: 'https://www.togetherpay.app',
  );

  /// Get the actual API base URL (runtime fallback if compile-time is empty)
  static String get effectiveApiBase {
    return apiBase.isEmpty ? 'https://www.togetherpay.app/api/v1' : apiBase;
  }
}
