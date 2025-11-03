/// Environment configuration
///
/// Set API_BASE at compile time:
/// flutter run --dart-define=API_BASE=https://api.togetherpay.app/api/v1
class Env {
  /// Base URL for the Laravel API
  static const apiBase = String.fromEnvironment(
    'API_BASE',
    defaultValue: 'https://georgiann-semiacademic-never.ngrok-free.dev/api/v1',
  );

  /// Enable debug logging
  static const debug = bool.fromEnvironment('DEBUG', defaultValue: true);

  /// App name
  static const appName = 'TogetherPay';
}
