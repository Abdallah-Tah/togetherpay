import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import 'env.dart';
import 'services/api_service.dart';
import 'utils/theme.dart';
import 'widgets/app_background.dart';

class PayScreen extends StatefulWidget {
  final String prCode;
  final String? deepLink;

  const PayScreen({
    super.key,
    required this.prCode,
    this.deepLink,
  });

  @override
  State<PayScreen> createState() => _PayScreenState();
}

class _PayScreenState extends State<PayScreen> with WidgetsBindingObserver {
  final Dio _dio = ApiService().dio;

  bool _loading = true;
  bool _launching = false;
  bool _hasLaunchedWallet = false;
  bool _confirmationShown = false;
  String? _error;

  Map<String, dynamic>? _paymentData;
  String? _deepLinkUri;
  String? _webFallback;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _loadPaymentRequest();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);

    if (state == AppLifecycleState.resumed &&
        _hasLaunchedWallet &&
        !_confirmationShown &&
        mounted) {
      _confirmationShown = true;
      Future.delayed(const Duration(milliseconds: 400), () {
        if (mounted) {
          _showPaymentConfirmationDialog();
        }
      });
    }
  }

  Future<void> _loadPaymentRequest() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await _dio.get('/requests/by-code/${widget.prCode}');
      final data = response.data as Map<String, dynamic>;

      if (!mounted) return;

      setState(() {
        _paymentData = data;
        _deepLinkUri = data['deep_link'] as String?;
        _webFallback = data['web_fallback_link'] as String?;
        _loading = false;
      });

      await Future.delayed(const Duration(milliseconds: 400));
      if (mounted) {
        _launchWallet();
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Error loading payment request: ${e.response?.data}');
      }

      if (!mounted) return;

      setState(() {
        _error = e.response?.data?['message'] as String? ??
            'Unable to load payment request';
        _loading = false;
      });
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected error loading payment request: $e');
      }

      if (!mounted) return;

      setState(() {
        _error = 'Unable to load payment request';
        _loading = false;
      });
    }
  }

  Future<void> _launchWallet() async {
    if (_launching) return;

    setState(() {
      _launching = true;
      _error = null;
    });

    try {
      Uri? targetUri;

      if (_deepLinkUri != null) {
        final uri = Uri.tryParse(_deepLinkUri!);
        if (uri != null && await canLaunchUrl(uri)) {
          targetUri = uri;
        }
      }

      if (targetUri == null && _webFallback != null) {
        final uri = Uri.tryParse(_webFallback!);
        if (uri != null && await canLaunchUrl(uri)) {
          targetUri = uri;
        }
      }

      if (targetUri == null && widget.deepLink != null) {
        final uri = Uri.tryParse(widget.deepLink!);
        if (uri != null && await canLaunchUrl(uri)) {
          targetUri = uri;
        }
      }

      if (targetUri != null) {
        final launched = await launchUrl(
          targetUri,
          mode: LaunchMode.externalApplication,
        );

        if (launched) {
          setState(() {
            _hasLaunchedWallet = true;
          });
        } else {
          setState(() {
            _error = 'Unable to open payment app';
          });
        }
      } else {
        setState(() {
          _error =
              'No compatible wallet app found. Install Cash App or Venmo and try again.';
        });
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Error launching wallet: $e');
      }

      if (mounted) {
        setState(() {
          _error = 'Failed to launch wallet app';
        });
      }
    } finally {
      if (mounted) {
        setState(() {
          _launching = false;
        });
      }
    }
  }

  Future<void> _showPaymentConfirmationDialog() async {
    final result = await showDialog<String>(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Text('Did you complete the payment?'),
        content: const Text(
          'Confirm once you have sent the payment in your wallet app.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, 'later'),
            child: const Text('Not yet'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, 'yes'),
            child: const Text('Yes, I paid'),
          ),
        ],
      ),
    );

    if (!mounted) return;

    if (result == 'yes') {
      await _confirmPayment();
    } else if (result == 'later') {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No worries—confirm when you are ready.'),
        ),
      );
    }
  }

  Future<void> _confirmPayment() async {
    try {
      await _dio.post(
        '/requests/by-code/${widget.prCode}/mark',
        data: {
          'evidence_url': null,
          'evidence_type': null,
        },
      );

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Payment marked as paid'),
            backgroundColor: Colors.green,
            duration: Duration(seconds: 2),
          ),
        );

        Future.delayed(const Duration(milliseconds: 400), () {
          if (mounted) {
            Navigator.pop(context);
          }
        });
      }
    } on DioException catch (e) {
      if (Env.debug) {
        debugPrint('Error marking as paid: ${e.response?.data}');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              e.response?.data?['message'] as String? ??
                  'Failed to mark payment as paid',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (Env.debug) {
        debugPrint('Unexpected error marking as paid: $e');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('An unexpected error occurred'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _markAsPaid() async {
    await _confirmPayment();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      extendBodyBehindAppBar: true,
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        title: Text('Pay • ${widget.prCode}'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _loading ? null : _loadPaymentRequest,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: AppBackground(
        child: SafeArea(
          child: AnimatedSwitcher(
            duration: const Duration(milliseconds: 450),
            switchInCurve: Curves.easeOutCubic,
            switchOutCurve: Curves.easeInBack,
            child: _loading
                ? _buildLoadingState(theme)
                : _buildLoadedContent(theme),
          ),
        ),
      ),
    );
  }

  Widget _buildLoadingState(ThemeData theme) {
    return Center(
      key: const ValueKey('loading'),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 120,
            height: 120,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: AppTheme.primaryGradient,
              boxShadow: AppTheme.elevatedShadow,
            ),
            padding: const EdgeInsets.all(32),
            child: const CircularProgressIndicator(
              strokeWidth: 4,
              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
            ),
          ),
          const SizedBox(height: 32),
          Text(
            'Preparing your payment link…',
            style: theme.textTheme.titleMedium,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Text(
            'We will open your wallet as soon as everything is ready.',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.onSurface.withValues(alpha: 0.6),
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildLoadedContent(ThemeData theme) {
    return SingleChildScrollView(
      key: const ValueKey('content'),
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(24, 16, 24, 40),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (_paymentData != null) ...[
            _buildHeroCard(theme),
            const SizedBox(height: 24),
          ],
          _buildInfoBanner(theme),
          const SizedBox(height: 24),
          _buildActionButtons(theme),
          const SizedBox(height: 32),
          Text(
            'How it works',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 16),
          _buildStep(1, 'Tap "Open Wallet"'),
          _buildStep(2, 'Review the pre-filled amount'),
          _buildStep(3, 'Send the payment'),
          _buildStep(4, 'Confirm here once sent'),
          const SizedBox(height: 32),
          _buildReferenceCard(theme),
        ],
      ),
    );
  }

  Widget _buildHeroCard(ThemeData theme) {
    final amount = _paymentData!['amount_display'] as String? ?? '\$0.00';
    final groupName = _paymentData!['group']?['name'] as String? ?? 'Payment';
    final note = _paymentData!['note'] as String?;

    return TweenAnimationBuilder<double>(
      duration: const Duration(milliseconds: 600),
      curve: Curves.easeOutBack,
      tween: Tween(begin: 0.92, end: 1),
      builder: (context, value, child) => Transform.scale(
        scale: value,
        child: child,
      ),
      child: Container(
        decoration: BoxDecoration(
          gradient: AppTheme.primaryGradient,
          borderRadius: BorderRadius.circular(AppTheme.radiusXLarge),
          boxShadow: AppTheme.elevatedShadow,
        ),
        padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Text(
              groupName,
              style: theme.textTheme.titleMedium?.copyWith(
                color: Colors.white.withValues(alpha: 0.9),
                fontWeight: FontWeight.w600,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            AnimatedDefaultTextStyle(
              duration: const Duration(milliseconds: 300),
              style: theme.textTheme.displayMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                  ) ??
                  const TextStyle(
                    fontSize: 48,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
              child: Text(amount),
            ),
            const SizedBox(height: 8),
            Text(
              'Amount Due',
              style: theme.textTheme.titleSmall?.copyWith(
                color: Colors.white.withValues(alpha: 0.8),
                letterSpacing: 0.2,
              ),
            ),
            if (note != null && note.isNotEmpty) ...[
              const SizedBox(height: 16),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
                ),
                child: Text(
                  note,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: Colors.white,
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildInfoBanner(ThemeData theme) {
    final hasError = _error != null;
    final icon = hasError ? Icons.warning_amber_rounded : Icons.wallet_outlined;
    final title =
        hasError ? 'Having trouble opening your wallet?' : 'Ready when you are';
    final message = hasError
        ? _error!
        : 'Your wallet should open automatically. If not, tap “Open Wallet” below.';

    return AnimatedContainer(
      duration: const Duration(milliseconds: 350),
      curve: Curves.easeOutCubic,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
        color: hasError
            ? const Color(0xFFFEE2E2)
            : theme.colorScheme.primaryContainer.withValues(alpha: 0.5),
        border: Border.all(
          color: hasError
              ? const Color(0xFFFCA5A5)
              : theme.colorScheme.primary.withValues(alpha: 0.35),
        ),
        boxShadow: AppTheme.cardShadow,
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient:
                  hasError ? AppTheme.errorGradient : AppTheme.primaryGradient,
            ),
            child: Icon(
              icon,
              color: Colors.white,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  message,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: hasError
                        ? const Color(0xFF7F1D1D)
                        : theme.colorScheme.onSurface.withValues(alpha: 0.7),
                  ),
                ),
                if (hasError) ...[
                  const SizedBox(height: 12),
                  TextButton(
                    onPressed: _loadPaymentRequest,
                    child: const Text('Try again'),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionButtons(ThemeData theme) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        FilledButton(
          onPressed: _launching ? null : _launchWallet,
          style: FilledButton.styleFrom(
            padding: const EdgeInsets.symmetric(vertical: 16),
            textStyle: theme.textTheme.titleMedium
                ?.copyWith(fontWeight: FontWeight.w600),
          ),
          child: AnimatedSwitcher(
            duration: const Duration(milliseconds: 200),
            switchInCurve: Curves.easeOut,
            switchOutCurve: Curves.easeIn,
            child: _launching
                ? const Row(
                    key: ValueKey('opening'),
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          valueColor:
                              AlwaysStoppedAnimation<Color>(Colors.white),
                        ),
                      ),
                      SizedBox(width: 12),
                      Text('Opening wallet…'),
                    ],
                  )
                : const Row(
                    key: ValueKey('label'),
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.open_in_new_rounded),
                      SizedBox(width: 12),
                      Text('Open Wallet'),
                    ],
                  ),
          ),
        ),
        const SizedBox(height: 12),
        OutlinedButton(
          onPressed: _launching ? null : _markAsPaid,
          style: OutlinedButton.styleFrom(
            padding: const EdgeInsets.symmetric(vertical: 16),
            textStyle: theme.textTheme.titleMedium,
          ),
          child: const Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.check_circle_outline_rounded),
              SizedBox(width: 12),
              Text('Mark as Paid'),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildReferenceCard(ThemeData theme) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
        border: Border.all(color: theme.dividerColor.withValues(alpha: 0.6)),
        boxShadow: AppTheme.cardShadow,
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
              color: theme.colorScheme.primaryContainer,
            ),
            child: Icon(
              Icons.qr_code_rounded,
              color: theme.colorScheme.primary,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Payment Request Code',
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 4),
                SelectableText(
                  widget.prCode,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontFamily: 'monospace',
                    letterSpacing: 0.4,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStep(int number, String text) {
    final theme = Theme.of(context);

    return TweenAnimationBuilder<double>(
      duration: Duration(milliseconds: 250 + (number * 90)),
      curve: Curves.easeOut,
      tween: Tween(begin: 0.94, end: 1),
      builder: (context, value, child) => Transform.scale(
        scale: value,
        child: child,
      ),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: theme.colorScheme.surface,
          borderRadius: BorderRadius.circular(AppTheme.radiusLarge),
          boxShadow: AppTheme.cardShadow,
        ),
        child: Row(
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
                gradient: AppTheme.primaryGradient,
              ),
              alignment: Alignment.center,
              child: Text(
                '$number',
                style: theme.textTheme.titleSmall?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Text(
                text,
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
