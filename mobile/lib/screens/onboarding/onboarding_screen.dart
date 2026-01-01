import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../env.dart';
import '../../services/api_service.dart';
import '../../utils/theme.dart';
import '../../utils/animations.dart';

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
  bool _isConnectingSquare = false;
  
  // Animation controllers
  late AnimationController _contentAnimController;

  final List<OnboardingPage> _pages = const [
    OnboardingPage(
      title: 'Welcome to TogetherPay',
      description:
          'Split bills easily with friends and family. Track payments and never miss a due date.',
      icon: Icons.groups_rounded,
      color: Color(0xFF3B82F6), // Blue
    ),
    OnboardingPage(
      title: 'Create Groups',
      description:
          'Organize your shared expenses into groups. Add members and set their payment amounts.',
      icon: Icons.group_add_rounded,
      color: AppTheme.primary, // Brand Teal
    ),
    OnboardingPage(
      title: 'Get Paid with Multiple Options',
      description:
          'Members choose their preferred payment option when they pay (some apps may not pre-fill the amount).',
      icon: Icons.payment_rounded,
      color: Color(0xFFA855F7), // Purple
    ),
    OnboardingPage(
      title: 'Choose how you collect',
      description:
          'Stick with manual payment links or enable automatic Square collection. You can change this later.',
      icon: Icons.swap_horiz_rounded,
      color: Color(0xFFF59E0B), // Amber
    ),
  ];

  @override
  void initState() {
    super.initState();
    _contentAnimController = AnimationController(
      duration: const Duration(milliseconds: 800),
      vsync: this,
    );
    _contentAnimController.forward();
  }

  @override
  void dispose() {
    _pageController.dispose();
    _contentAnimController.dispose();
    super.dispose();
  }

  void _onPageChanged(int index) {
    setState(() {
      _currentPage = index;
    });
    _contentAnimController.reset();
    _contentAnimController.forward();
  }

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
        widget.onComplete();
      }
    } on DioException catch (e) {
      final message = e.response?.data?['message'] as String? ??
          'Failed to complete onboarding';

      if (_billingMode == 'auto_square' &&
          message.toLowerCase().contains('square account')) {
        await _startSquareConnect();
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: AppTheme.error,
            behavior: SnackBarBehavior.floating,
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

  Future<void> _startSquareConnect() async {
    if (_isConnectingSquare) return;

    setState(() {
      _isConnectingSquare = true;
    });

    try {
      final response = await ApiService().dio.get('/square/oauth/url');
      final url = response.data['authorization_url'] as String?;

      if (url == null) throw Exception('Missing Square authorization URL');
      final uri = Uri.tryParse(url);
      if (uri == null) throw Exception('Invalid Square authorization URL');

      await launchUrl(uri, mode: LaunchMode.externalApplication);

      if (mounted) {
        setState(() {
          _modeError =
              'Finish connecting Square in your browser, then return and tap Get Started again.';
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Unable to launch Square connection right now.'),
            backgroundColor: AppTheme.error,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isConnectingSquare = false;
        });
      }
    }
  }

  void _selectMode(String mode) {
    setState(() {
      _billingMode = mode;
      _modeError = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    // Force dark theme for onboarding as per design
    final theme = AppTheme.darkTheme;
    
    return Theme(
      data: theme,
      child: Scaffold(
        backgroundColor: const Color(0xFF0F172A), // Slate 900
        body: SafeArea(
          child: Stack(
            children: [
              Positioned(
                top: -120,
                left: -80,
                child: Container(
                  width: 240,
                  height: 240,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: AppTheme.primary.withOpacity(0.18),
                  ),
                ),
              ),
              Positioned(
                bottom: -140,
                right: -100,
                child: Container(
                  width: 280,
                  height: 280,
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    color: Color(0x3348E6D2),
                  ),
                ),
              ),
              Column(
                children: [
                  Expanded(
                    child: PageView.builder(
                      controller: _pageController,
                      onPageChanged: _onPageChanged,
                      itemCount: _pages.length,
                      itemBuilder: (context, index) {
                        final page = _pages[index];
                        // If it's the last page, show the selection UI instead of standard description
                        final isLastPage = index == _pages.length - 1;

                        return Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 32.0),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              // Animated Icon
                              SlideAnimation(
                                position: 0,
                                itemCount: 3,
                                slideDirection: SlideDirection.fromTop,
                                animationController: _contentAnimController,
                                child: Container(
                                  width: 120,
                                  height: 120,
                                  decoration: BoxDecoration(
                                    shape: BoxShape.circle,
                                    gradient: LinearGradient(
                                      colors: [
                                        page.color.withOpacity(0.35),
                                        Colors.transparent,
                                      ],
                                      begin: Alignment.topLeft,
                                      end: Alignment.bottomRight,
                                    ),
                                    border: Border.all(
                                      color: page.color.withOpacity(0.4),
                                      width: 1.5,
                                    ),
                                    boxShadow: [
                                      BoxShadow(
                                        color: page.color.withOpacity(0.25),
                                        blurRadius: 24,
                                        offset: const Offset(0, 12),
                                      ),
                                    ],
                                  ),
                                  child: Icon(
                                    page.icon,
                                    size: 64,
                                    color: page.color,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 48),
                              
                              // Title
                              SlideAnimation(
                                position: 1,
                                itemCount: 3,
                                slideDirection: SlideDirection.fromBottom,
                                animationController: _contentAnimController,
                                child: Text(
                                  page.title,
                                  style: theme.textTheme.headlineMedium?.copyWith(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                  ),
                                  textAlign: TextAlign.center,
                                ),
                              ),
                              const SizedBox(height: 16),

                              // Description or Mode Selector
                              SlideAnimation(
                                position: 2,
                                itemCount: 3,
                                slideDirection: SlideDirection.fromBottom,
                                animationController: _contentAnimController,
                                child: isLastPage 
                                  ? _buildModeSelector(theme)
                                  : Text(
                                      page.description,
                                      style: theme.textTheme.bodyLarge?.copyWith(
                                        color: Colors.white70,
                                        height: 1.5,
                                      ),
                                      textAlign: TextAlign.center,
                                    ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ),

                  // Bottom Section: Indicators & Button
                  Padding(
                    padding: const EdgeInsets.all(32.0),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        // Page Indicators
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: List.generate(
                            _pages.length,
                            (index) => AnimatedContainer(
                              duration: const Duration(milliseconds: 300),
                              margin: const EdgeInsets.symmetric(horizontal: 4),
                              width: _currentPage == index ? 32 : 8,
                              height: 8,
                              decoration: BoxDecoration(
                                color: _currentPage == index
                                    ? AppTheme.primary
                                    : Colors.white24,
                                borderRadius: BorderRadius.circular(4),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 28),

                        // Action Button
                        SizedBox(
                          width: double.infinity,
                          height: 56,
                          child: FilledButton(
                            onPressed: _isCompleting
                                ? null
                                : () {
                                    if (_currentPage < _pages.length - 1) {
                                      _pageController.nextPage(
                                        duration: const Duration(milliseconds: 500),
                                        curve: Curves.easeInOutCubic,
                                      );
                                    } else {
                                      _completeOnboarding();
                                    }
                                  },
                            style: FilledButton.styleFrom(
                              backgroundColor: AppTheme.primary,
                              foregroundColor: AppTheme.navy,
                              elevation: 0,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(28),
                              ),
                            ),
                            child: _isCompleting
                                ? const SizedBox(
                                    width: 24,
                                    height: 24,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.5,
                                      valueColor: AlwaysStoppedAnimation(AppTheme.navy),
                                    ),
                                  )
                                : Text(
                                    _currentPage < _pages.length - 1
                                        ? 'Next'
                                        : 'Get Started',
                                    style: const TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildModeSelector(ThemeData theme) {
    return Column(
      children: [
        Text(
          'Stick with manual payment links or enable automatic Square collection. You can change this later.',
          style: theme.textTheme.bodyMedium?.copyWith(
            color: Colors.white70,
            height: 1.5,
          ),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 32),
        _ModeOptionCard(
          title: 'Manual payouts',
          description: 'Members tap links to pay you directly.',
          icon: Icons.handshake_rounded,
          selected: _billingMode == 'manual',
          badgeLabel: 'Default',
          onTap: () => _selectMode('manual'),
        ),
        const SizedBox(height: 16),
        _ModeOptionCard(
          title: 'Auto Square Pay',
          description: 'Square handles payments automatically. Requires a connected Square account.',
          icon: Icons.bolt_rounded,
          selected: _billingMode == 'auto_square',
          badgeLabel: 'Hands-free',
          onTap: () => _selectMode('auto_square'),
        ),
        const SizedBox(height: 16),
        Text(
          'You can switch this anytime in Settings.',
          style: theme.textTheme.bodySmall?.copyWith(
            color: Colors.white54,
          ),
        ),
        if (_modeError != null) ...[
          const SizedBox(height: 16),
          Text(
            _modeError!,
            style: TextStyle(color: AppTheme.error, fontSize: 13),
            textAlign: TextAlign.center,
          ),
        ],
      ],
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
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: selected
              ? AppTheme.primary.withOpacity(0.18)
              : Colors.white.withOpacity(0.06),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: selected ? AppTheme.primary : Colors.white12,
            width: 2,
          ),
          boxShadow: [
            BoxShadow(
              color: selected
                  ? AppTheme.primary.withOpacity(0.2)
                  : Colors.black.withOpacity(0.2),
              blurRadius: 16,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: selected ? AppTheme.primary : Colors.white10,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                icon,
                color: selected ? AppTheme.navy : Colors.white70,
                size: 24,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text(
                        title,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                      if (badgeLabel != null) ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: selected 
                                ? AppTheme.primary.withOpacity(0.2)
                                : Colors.white10,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            badgeLabel!,
                            style: TextStyle(
                              color: selected ? AppTheme.primary : Colors.white70,
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    description,
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.6),
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: selected ? AppTheme.primary : Colors.white12,
              ),
              child: Icon(
                selected ? Icons.check : Icons.circle_outlined,
                size: 16,
                color: selected ? AppTheme.navy : Colors.white54,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
