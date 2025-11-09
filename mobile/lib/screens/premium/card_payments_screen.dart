import 'package:flutter/material.dart';

import '../../widgets/coming_soon_card.dart';

/// Card Payments & Auto-Charge - Premium Feature
/// Displays Coming Soon information for automatic payment processing
class CardPaymentsScreen extends StatelessWidget {
  const CardPaymentsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Card Payments'),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 12),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: Colors.amber.shade100,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.amber.shade300),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.star, size: 16, color: Colors.amber.shade700),
                const SizedBox(width: 4),
                Text(
                  'Premium',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: Colors.amber.shade700,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Hero Section
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    Colors.amber.shade50,
                    Colors.orange.shade50,
                  ],
                ),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.amber.shade200),
              ),
              child: Column(
                children: [
                  Icon(
                    Icons.credit_card,
                    size: 64,
                    color: Colors.amber.shade700,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Premium Auto-Charge',
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Never worry about collecting payments again. Automatic charges ensure you get paid on time, every time.',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Colors.grey.shade600,
                        ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Coming Soon Badge
            Center(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: Colors.orange.shade100,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: Colors.orange.shade300),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.schedule, size: 18, color: Colors.orange.shade700),
                    const SizedBox(width: 8),
                    Text(
                      'Coming Q3 2025',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Colors.orange.shade700,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Feature Cards
            const ComingSoonCard(
              icon: Icons.lock_outline,
              title: 'Secure Card Vaulting',
              description:
                  'Industry-standard encryption protects your payment information. PCI-compliant storage ensures maximum security.',
              estimatedRelease: 'Q3 2025',
              accentColor: Colors.purple,
            ),
            const SizedBox(height: 16),

            const ComingSoonCard(
              icon: Icons.autorenew,
              title: 'Automatic Charging',
              description:
                  'Set it and forget it. Charges happen automatically on your billing cycle so you always get paid on time.',
              estimatedRelease: 'Q3 2025',
              accentColor: Colors.blue,
            ),
            const SizedBox(height: 16),

            const ComingSoonCard(
              icon: Icons.receipt_long,
              title: 'Smart Retry Logic',
              description:
                  'Failed payments are automatically retried with intelligent scheduling to maximize successful charges.',
              estimatedRelease: 'Q3 2025',
              accentColor: Colors.green,
            ),
            const SizedBox(height: 16),

            const ComingSoonCard(
              icon: Icons.notifications_active,
              title: 'Payment Notifications',
              description:
                  'Both you and your members receive instant notifications when charges succeed or fail.',
              estimatedRelease: 'Q3 2025',
              accentColor: Colors.orange,
            ),
            const SizedBox(height: 24),

            // Pricing Teaser
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.blue.shade200),
              ),
              child: Column(
                children: [
                  Icon(Icons.info_outline, color: Colors.blue.shade700, size: 32),
                  const SizedBox(height: 12),
                  Text(
                    'Premium Feature',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.blue.shade900,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Card payments will be available as a premium add-on. Pricing and plans will be announced closer to launch.',
                    style: TextStyle(
                      color: Colors.blue.shade700,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
