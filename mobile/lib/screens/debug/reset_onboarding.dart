import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Helper widget to reset onboarding flag for testing
class ResetOnboardingButton extends StatelessWidget {
  const ResetOnboardingButton({super.key});

  Future<void> _resetOnboarding(BuildContext context) async {
    const storage = FlutterSecureStorage();
    await storage.delete(key: 'onboarding_completed');

    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Onboarding reset! Restart the app to see onboarding flow.',
          ),
          duration: Duration(seconds: 3),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: const Icon(Icons.refresh_outlined),
      title: const Text('Reset Onboarding'),
      subtitle: const Text('Clear onboarding flag to see welcome screens'),
      trailing: const Icon(Icons.chevron_right),
      onTap: () => _resetOnboarding(context),
    );
  }
}
