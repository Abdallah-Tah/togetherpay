import 'package:flutter/material.dart';

/// Privacy Policy Screen - Displays the application's privacy policy
class PrivacyPolicyScreen extends StatelessWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Privacy Policy'),
        centerTitle: false,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildSection(
            context,
            'Introduction',
            'TogetherPay ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our service.',
          ),
          _buildSection(
            context,
            'Information We Collect',
            '',
            subsections: [
              (
                'Personal Information',
                'We collect information that you provide directly to us, including:\n'
                '• Name and email address\n'
                '• Phone number (optional)\n'
                '• Payment channel handles (Venmo/Cash App usernames)\n'
                '• Billing group information and member details\n'
                '• Payment request history and status'
              ),
              (
                'Automatically Collected Information',
                '• Device information (browser type, operating system)\n'
                '• IP address and location data\n'
                '• Usage data and analytics\n'
                '• Cookies and similar tracking technologies'
              ),
            ],
          ),
          _buildSection(
            context,
            'How We Use Your Information',
            'We use the information we collect to:',
            bulletPoints: [
              'Provide, maintain, and improve our services',
              'Send payment requests and reminders to group members',
              'Process group invitations and member management',
              'Generate reports and analytics',
              'Send service-related communications and notifications',
              'Detect and prevent fraud or security issues',
              'Comply with legal obligations',
            ],
          ),
          _buildSection(
            context,
            'Information Sharing and Disclosure',
            'We do not sell your personal information. We may share your information in the following circumstances:',
            bulletPoints: [
              'With Group Members: Your name and payment information is shared with members of groups you join',
              'Service Providers: Third-party vendors who perform services on our behalf (hosting, analytics, email delivery)',
              'Legal Requirements: When required by law or to protect our rights and safety',
              'Business Transfers: In connection with a merger, sale, or acquisition',
            ],
          ),
          _buildSection(
            context,
            'Payment Information',
            'Important: TogetherPay does not process, store, or have access to credit card or bank account information. All payments are made directly between members via Venmo or Cash App. We only facilitate the request process by generating payment links - we never handle actual money transfers.',
            isHighlight: true,
          ),
          _buildSection(
            context,
            'Data Security',
            'We implement appropriate technical and organizational measures to protect your personal information, including:',
            bulletPoints: [
              'Encryption of data in transit and at rest',
              'Regular security audits and updates',
              'Access controls and authentication requirements',
            ],
          ),
          _buildSection(
            context,
            'Your Rights and Choices',
            'You have the following rights regarding your personal information:',
            bulletPoints: [
              'Access: Request a copy of your personal data',
              'Correction: Update or correct inaccurate information',
              'Deletion: Request deletion of your account and data',
              'Opt-out: Unsubscribe from marketing communications',
              'Data Portability: Export your data in a structured format',
            ],
          ),
          Padding(
            padding: const EdgeInsets.only(top: 8, bottom: 16),
            child: Text(
              'To exercise these rights, please contact us at privacy@togetherpay.com',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    fontStyle: FontStyle.italic,
                    color: Colors.grey.shade600,
                  ),
            ),
          ),
          _buildSection(
            context,
            'Data Retention',
            'We retain your personal information for as long as necessary to provide our services and comply with legal obligations. When you delete your account, we will delete or anonymize your personal information within 30 days, except where we are required to retain it for legal, tax, or regulatory purposes.',
          ),
          _buildSection(
            context,
            'Children\'s Privacy',
            'Our service is not intended for children under 18 years of age. We do not knowingly collect personal information from children. If you become aware that a child has provided us with personal information, please contact us.',
          ),
          _buildSection(
            context,
            'International Data Transfers',
            'Your information may be transferred to and processed in countries other than your country of residence. We ensure appropriate safeguards are in place to protect your information in accordance with this Privacy Policy.',
          ),
          _buildSection(
            context,
            'Changes to This Privacy Policy',
            'We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date. Continued use of our service after changes constitutes acceptance of the updated policy.',
          ),
          _buildSection(
            context,
            'Contact Us',
            'If you have questions or concerns about this Privacy Policy, please contact us:',
            bulletPoints: [
              'Email: privacy@togetherpay.com',
              'Contact Form: togetherpay.com/contact',
            ],
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
    List<(String, String)>? subsections,
    bool isHighlight = false,
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
            Container(
              padding: isHighlight ? const EdgeInsets.all(12) : null,
              decoration: isHighlight
                  ? BoxDecoration(
                      color: Colors.blue.shade50,
                      border: Border(
                        left: BorderSide(
                          color: Colors.blue.shade400,
                          width: 4,
                        ),
                      ),
                      borderRadius: BorderRadius.circular(4),
                    )
                  : null,
              child: Text(
                content,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      height: 1.6,
                      color: isHighlight
                          ? Colors.blue.shade900
                          : Colors.grey.shade700,
                    ),
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
          if (subsections != null) ...[
            const SizedBox(height: 12),
            ...subsections.map(
              (sub) => Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      sub.$1,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      sub.$2,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        height: 1.5,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
