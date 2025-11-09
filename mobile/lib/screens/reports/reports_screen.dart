import 'package:flutter/material.dart';

import '../../widgets/coming_soon_card.dart';

/// Reports Screen - Shows analytics and export options
/// Currently displays Coming Soon placeholders for premium features
class ReportsScreen extends StatelessWidget {
  const ReportsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Reports & Analytics'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Text(
              'Payment Insights',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 8),
            Text(
              'Track your group payment performance and export detailed reports.',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Colors.grey.shade600,
                  ),
            ),
            const SizedBox(height: 24),

            // Coming Soon Cards
            const ComingSoonCard(
              icon: Icons.analytics_outlined,
              title: 'Payment Analytics',
              description:
                  'View detailed analytics on collection rates, payment patterns, and member performance over time.',
              estimatedRelease: 'Q2 2025',
              accentColor: Colors.indigo,
            ),
            const SizedBox(height: 16),

            const ComingSoonCard(
              icon: Icons.picture_as_pdf_outlined,
              title: 'PDF Reports',
              description:
                  'Generate professional PDF reports with charts, graphs, and detailed payment summaries for your records.',
              estimatedRelease: 'Q2 2025',
              accentColor: Colors.red,
            ),
            const SizedBox(height: 16),

            const ComingSoonCard(
              icon: Icons.file_download_outlined,
              title: 'CSV Export',
              description:
                  'Export raw payment data to CSV format for custom analysis in Excel or Google Sheets.',
              estimatedRelease: 'Q1 2025',
              accentColor: Colors.green,
            ),
            const SizedBox(height: 16),

            const ComingSoonCard(
              icon: Icons.email_outlined,
              title: 'Scheduled Reports',
              description:
                  'Automatically receive weekly or monthly payment summaries via email.',
              estimatedRelease: 'Q3 2025',
              accentColor: Colors.blue,
            ),
          ],
        ),
      ),
    );
  }
}
