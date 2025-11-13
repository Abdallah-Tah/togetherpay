import 'package:flutter/material.dart';

/// Terms of Service Screen - Displays the application's terms of service
class TermsOfServiceScreen extends StatelessWidget {
  const TermsOfServiceScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Terms of Service'),
        centerTitle: false,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildSection(
            context,
            '1. Acceptance of Terms',
            'By downloading or using TogetherPay ("Service") you agree to these Terms. If you do not agree, do not access or use the Service.',
          ),
          _buildSection(
            context,
            '2. Description of Service',
            'TogetherPay provides tooling for group owners to organize shared expenses, send payment requests, and track payment status. We offer two collection modes:',
            bulletPoints: [
              'Manual mode – generates pre-filled links for peer‑to‑peer apps such as Cash App or Venmo.',
              'Auto Cash App Pay – publishes Cash App Pay invoices through the owner\'s connected Square merchant account.',
            ],
            additionalContent:
                '\nImportant: TogetherPay is not a payment processor and never holds user funds. Payments occur directly between the parties or via the owner\'s Square account.',
          ),
          _buildSection(
            context,
            '3. Accounts & Responsibilities',
            '',
            bulletPoints: [
              'You must be at least 18 years old and provide accurate registration information.',
              'You are responsible for safeguarding your credentials and the content you create.',
              'You may not use the Service for unlawful or prohibited activities.',
              'You agree to comply with all applicable laws and the terms of any linked payment providers.',
            ],
          ),
          _buildSection(
            context,
            '4. Payment Terms (Manual Mode)',
            'When using manual mode you acknowledge that all financial transactions are handled solely through third‑party peer-to-peer services. TogetherPay is not responsible for failed payments, disputes, chargebacks, or fees imposed by those providers.',
          ),
          _buildSection(
            context,
            '5. Square & Cash App Pay Autopay',
            '',
            bulletPoints: [
              'Connecting Square authorizes TogetherPay to create invoices and read invoice metadata on your behalf.',
              'You remain the merchant of record. Square deposits funds into your Square balance or linked bank account. TogetherPay never receives those funds.',
              'Your use of Square is governed by Square\'s Merchant Terms and Card Brand rules. You are solely responsible for compliance, taxes, chargebacks, refunds, and any disputes.',
              'You may disconnect Square at any time from within TogetherPay or the Square dashboard. Disconnecting stops new autopay invoices but does not cancel invoices already published.',
            ],
          ),
          _buildSection(
            context,
            '6. Data & Privacy',
            'We collect and store information needed to provide the Service (e.g., group details, email addresses, Square OAuth tokens). See our Privacy Policy for details on data handling. We never store full card numbers or bank credentials.',
          ),
          _buildSection(
            context,
            '7. Service Availability',
            'We aim for continuous availability but may suspend or modify the Service at any time for maintenance, upgrades, or security reasons. We are not liable for any loss resulting from downtime.',
          ),
          _buildSection(
            context,
            '8. Limitation of Liability',
            'TogetherPay is provided "as is." To the fullest extent permitted by law, TogetherPay and its affiliates are not liable for indirect, incidental, or consequential damages arising from the use of the Service.',
          ),
          _buildSection(
            context,
            '9. Contact',
            'Questions about these Terms? Email support@togetherpay.com.',
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildSection(
    BuildContext context,
    String title,
    String content, {
    List<String>? bulletPoints,
    String? additionalContent,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          if (content.isNotEmpty)
            Text(
              content,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                height: 1.6,
              ),
            ),
          if (bulletPoints != null) ...[
            const SizedBox(height: 12),
            ...bulletPoints.map(
              (point) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Padding(
                      padding: const EdgeInsets.only(right: 12, top: 4),
                      child: Text(
                        '•',
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          fontSize: 16,
                        ),
                      ),
                    ),
                    Expanded(
                      child: Text(
                        point,
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          height: 1.5,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
          if (additionalContent != null) ...[
            const SizedBox(height: 12),
            Text(
              additionalContent,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                height: 1.6,
                fontStyle: FontStyle.italic,
                color: Colors.grey.shade600,
              ),
            ),
          ],
        ],
      ),
    );
  }
}
