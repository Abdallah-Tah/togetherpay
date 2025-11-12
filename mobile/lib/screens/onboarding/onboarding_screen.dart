import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../../env.dart';
import '../../services/api_service.dart';

class OnboardingScreen extends StatefulWidget {
  final VoidCallback onComplete;

  const OnboardingScreen({
    super.key,
    required this.onComplete,
  });

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  int _currentPage = 0;
  final _pageController = PageController();
  bool _isCompleting = false;
  String? _billingMode;
  String? _modeError;

  final List<OnboardingPage> _pages = [
    OnboardingPage(
      title: 'Welcome to TogetherPay',
      description:
          'Split bills easily with friends and family. Track payments and never miss a due date.',
      icon: Icons.groups,
      color: Colors.blue,
    ),
    OnboardingPage(
      title: 'Create Groups',
      description:
          'Organize your shared expenses into groups. Add members and set their payment amounts.',
      icon: Icons.group_add,
      color: Colors.green,
    ),
    OnboardingPage(
      title: 'Get Paid via Venmo or Cash App',
      description:
          'Members pay you directly through their preferred payment app with pre-filled amounts.',
      icon: Icons.payment,
      color: Colors.purple,
    ),
    OnboardingPage(
      title: 'Choose how you collect',
      description:
          'Stick with manual payment links or enable automatic Cash App Pay via Square. You can change this later.',
      icon: Icons.swap_horiz,
      color: Colors.orange,
    ),
  ];

  Future<void> _completeOnboarding() async {
    if (_billingMode == null) {
      setState(() {
        _modeError = 'Please choose how you want to collect payments.';
      });
      return;
    }

    setState(() {
      _isCompleting = true;
      _modeError = null;
    });

    try {
      final response = await ApiService().dio.post(
        '/auth/onboarding/complete',
        data: {'billing_mode': _billingMode},
      );

      if (Env.debug) {
        debugPrint('Onboarding completion response: ${response.data}');
      }

      if (mounted) {
        // Show success message
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Onboarding completed successfully!'),
            backgroundColor: Colors.green,
            duration: Duration(seconds: 2),
          ),
        );

        // Call the completion callback
        widget.onComplete();
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Complete onboarding error: ${e.response?.data}');
      }

      final message = e.response?.data?['message'] as String? ??
          'Failed to complete onboarding';

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: Colors.red,
          ),
        );

        setState(() {
          _modeError = message;
        });
      }
    } finally {
      if (mounted) {
        setState(() {
          _isCompleting = false;
        });
      }
    }
  }

  Widget _buildModeSelector(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'How do you want to collect payments?',
          style: textTheme.titleMedium,
        ),
        const SizedBox(height: 12),
        _ModeOptionCard(
          title: 'Manual payouts',
          description:
              'Members tap Cash App or Venmo links and you confirm payments inside the app.',
          icon: Icons.handshake,
          selected: _billingMode == 'manual',
          badgeLabel: 'Default',
          onTap: () => _selectMode('manual'),
        ),
        const SizedBox(height: 12),
        _ModeOptionCard(
          title: 'Auto Cash App Pay (Square)',
          description:
              'Square emails your members and Cash App prompts them to approve. Requires a connected Square account.',
          icon: Icons.bolt,
          selected: _billingMode == 'auto_square',
          badgeLabel: 'Hands-free',
          onTap: () => _selectMode('auto_square'),
        ),
        if (_billingMode == 'auto_square') ...[
          const SizedBox(height: 8),
          Text(
            'Connect your Square account from the web dashboard to finish enabling automatic Cash App Pay.',
            style: textTheme.bodySmall,
          ),
        ],
        if (_modeError != null) ...[
          const SizedBox(height: 8),
          Text(
            _modeError!,
            style: const TextStyle(color: Colors.red),
          ),
        ],
      ],
    );
  }

  void _selectMode(String mode) {
    setState(() {
      _billingMode = mode;
      _modeError = null;
    });
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: PageView.builder(
                controller: _pageController,
                onPageChanged: (index) {
                  setState(() {
                    _currentPage = index;
                  });
                },
                itemCount: _pages.length,
                itemBuilder: (context, index) {
                  final page = _pages[index];
                  return Padding(
                    padding: const EdgeInsets.all(40.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          page.icon,
                          size: 120,
                          color: page.color,
                        ),
                        const SizedBox(height: 48),
                        Text(
                          page.title,
                          style: Theme.of(context)
                              .textTheme
                              .headlineMedium
                              ?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 24),
                        Text(
                          page.description,
                          style: Theme.of(context).textTheme.bodyLarge,
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(
                      _pages.length,
                      (index) => Container(
                        margin: const EdgeInsets.symmetric(horizontal: 4),
                        width: _currentPage == index ? 24 : 8,
                        height: 8,
                        decoration: BoxDecoration(
                          color: _currentPage == index
                              ? Theme.of(context).colorScheme.primary
                              : Colors.grey.shade300,
                          borderRadius: BorderRadius.circular(4),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                  if (_currentPage == _pages.length - 1) ...[
                    _buildModeSelector(context),
                    const SizedBox(height: 24),
                  ],
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: _isCompleting
                          ? null
                          : () {
                              if (_currentPage < _pages.length - 1) {
                                _pageController.nextPage(
                                  duration: const Duration(milliseconds: 300),
                                  curve: Curves.easeInOut,
                                );
                              } else {
                                if (_billingMode == null) {
                                  setState(() {
                                    _modeError =
                                        'Please choose how you want to collect payments.';
                                  });
                                  return;
                                }
                                _completeOnboarding();
                              }
                            },
                      child: _isCompleting
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : Text(
                              _currentPage < _pages.length - 1
                                  ? 'Next'
                                  : 'Get Started',
                            ),
                    ),
                  ),
                  const SizedBox(height: 8),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class OnboardingPage {
  final String title;
  final String description;
  final IconData icon;
  final Color color;

  const OnboardingPage({
    required this.title,
    required this.description,
    required this.icon,
    required this.color,
  });
}

class _ModeOptionCard extends StatelessWidget {
  final String title;
  final String description;
  final IconData icon;
  final bool selected;
  final String? badgeLabel;
  final VoidCallback onTap;

  const _ModeOptionCard({
    required this.title,
    required this.description,
    required this.icon,
    required this.selected,
    required this.onTap,
    this.badgeLabel,
  });

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: selected ? colorScheme.primary : Colors.grey.shade300,
            width: 2,
          ),
          color: selected
              ? colorScheme.primaryContainer.withOpacity(0.4)
              : colorScheme.surface,
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(
              icon,
              size: 28,
              color: selected ? colorScheme.primary : Colors.grey.shade600,
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          title,
                          style: Theme.of(context)
                              .textTheme
                              .titleMedium
                              ?.copyWith(fontWeight: FontWeight.bold),
                        ),
                      ),
                      if (badgeLabel != null)
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: colorScheme.primary.withOpacity(0.15),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            badgeLabel!,
                            style: TextStyle(
                              color: colorScheme.primary,
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    description,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Colors.grey.shade700,
                        ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Icon(
              selected
                  ? Icons.radio_button_checked
                  : Icons.radio_button_off_outlined,
              color: selected ? colorScheme.primary : Colors.grey.shade400,
            ),
          ],
        ),
      ),
    );
  }
}
