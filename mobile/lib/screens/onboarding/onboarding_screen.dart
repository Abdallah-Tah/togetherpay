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

class _OnboardingScreenState extends State<OnboardingScreen>
    with TickerProviderStateMixin {
  int _currentPage = 0;
  final _pageController = PageController();
  bool _isCompleting = false;
  String? _billingMode;
  String? _modeError;
  late AnimationController _fadeAnimController;
  late Animation<double> _fadeAnimation;

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
      mainAxisSize: MainAxisSize.min,
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
            style: const TextStyle(color: Colors.red, fontSize: 12),
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
  void initState() {
    super.initState();
    _fadeAnimController = AnimationController(
      duration: const Duration(milliseconds: 500),
      vsync: this,
    );
    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _fadeAnimController, curve: Curves.easeInOut),
    );
    _fadeAnimController.forward();
  }

  @override
  void dispose() {
    _pageController.dispose();
    _fadeAnimController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              flex: 3,
              child: PageView.builder(
                controller: _pageController,
                onPageChanged: (index) {
                  setState(() {
                    _currentPage = index;
                  });
                  _fadeAnimController.reset();
                  _fadeAnimController.forward();
                },
                itemCount: _pages.length,
                itemBuilder: (context, index) {
                  final page = _pages[index];
                  return FadeTransition(
                    opacity: _fadeAnimation,
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 32.0),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          ScaleTransition(
                            scale: Tween<double>(begin: 0.5, end: 1.0).animate(
                              CurvedAnimation(
                                parent: _fadeAnimController,
                                curve: Curves.elasticOut,
                              ),
                            ),
                            child: Icon(
                              page.icon,
                              size: 100,
                              color: page.color,
                            ),
                          ),
                          const SizedBox(height: 32),
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
                          const SizedBox(height: 16),
                          Text(
                            page.description,
                            style: Theme.of(context).textTheme.bodyMedium,
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
            Expanded(
              flex: 2,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 16.0),
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Animated page indicators
                      AnimatedBuilder(
                        animation: _fadeAnimation,
                        builder: (context, child) {
                          return Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: List.generate(
                              _pages.length,
                              (index) => AnimatedContainer(
                                duration: const Duration(milliseconds: 300),
                                margin: const EdgeInsets.symmetric(horizontal: 4),
                                width: _currentPage == index ? 28 : 8,
                                height: 8,
                                decoration: BoxDecoration(
                                  color: _currentPage == index
                                      ? Theme.of(context).colorScheme.primary
                                      : Colors.grey.shade300,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                      const SizedBox(height: 20),
                      // Billing mode selector with animation
                      if (_currentPage == _pages.length - 1) ...[
                        AnimatedOpacity(
                          opacity: 1.0,
                          duration: const Duration(milliseconds: 500),
                          child: _buildModeSelector(context),
                        ),
                        const SizedBox(height: 20),
                      ],
                      // Button
                      SizedBox(
                        width: double.infinity,
                        height: 52,
                        child: FilledButton(
                          onPressed: _isCompleting
                              ? null
                              : () {
                                  if (_currentPage < _pages.length - 1) {
                                    _pageController.nextPage(
                                      duration: const Duration(milliseconds: 400),
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
                                    strokeWidth: 2.5,
                                    color: Colors.white,
                                  ),
                                )
                              : Text(
                                  _currentPage < _pages.length - 1
                                      ? 'Next'
                                      : 'Get Started',
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                        ),
                      ),
                    ],
                  ),
                ),
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

class _ModeOptionCard extends StatefulWidget {
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
  State<_ModeOptionCard> createState() => _ModeOptionCardState();
}

class _ModeOptionCardState extends State<_ModeOptionCard>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scaleAnimation;
  bool _isHovered = false;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      duration: const Duration(milliseconds: 300),
      vsync: this,
    );
    _scaleAnimation = Tween<double>(begin: 1.0, end: 1.02).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return GestureDetector(
      onTap: widget.onTap,
      onTapDown: (_) {
        _controller.forward();
      },
      onTapUp: (_) {
        _controller.reverse();
      },
      onTapCancel: () {
        _controller.reverse();
      },
      child: ScaleTransition(
        scale: _scaleAnimation,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: widget.selected ? colorScheme.primary : Colors.grey.shade300,
              width: widget.selected ? 2 : 1.5,
            ),
            color: widget.selected
                ? colorScheme.primaryContainer.withOpacity(0.3)
                : colorScheme.surface,
            boxShadow: widget.selected
                ? [
                    BoxShadow(
                      color: colorScheme.primary.withOpacity(0.15),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ]
                : null,
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: widget.selected
                      ? colorScheme.primary.withOpacity(0.15)
                      : Colors.grey.shade100,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  widget.icon,
                  size: 24,
                  color: widget.selected ? colorScheme.primary : Colors.grey.shade600,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            widget.title,
                            style: Theme.of(context)
                                .textTheme
                                .titleMedium
                                ?.copyWith(fontWeight: FontWeight.w600),
                          ),
                        ),
                        if (widget.badgeLabel != null)
                          AnimatedContainer(
                            duration: const Duration(milliseconds: 300),
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 5,
                            ),
                            decoration: BoxDecoration(
                              color: colorScheme.primary.withOpacity(0.2),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              widget.badgeLabel!,
                              style: TextStyle(
                                color: colorScheme.primary,
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      widget.description,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: Colors.grey.shade700,
                            height: 1.4,
                          ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              AnimatedScale(
                scale: widget.selected ? 1.2 : 1.0,
                duration: const Duration(milliseconds: 300),
                child: Icon(
                  widget.selected
                      ? Icons.radio_button_checked
                      : Icons.radio_button_off_outlined,
                  color: widget.selected ? colorScheme.primary : Colors.grey.shade400,
                  size: 24,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
