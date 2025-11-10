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
  ];

  Future<void> _completeOnboarding() async {
    setState(() {
      _isCompleting = true;
    });

    try {
      final response = await ApiService().dio.post('/auth/onboarding/complete');

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

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data?['message'] as String? ??
                  'Failed to complete onboarding',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isCompleting = false;
        });
      }
    }
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
                  if (_currentPage < _pages.length - 1) ...[
                    const SizedBox(height: 8),
                    TextButton(
                      onPressed: _isCompleting
                          ? null
                          : () => _completeOnboarding(),
                      child: const Text('Skip'),
                    ),
                  ],
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
