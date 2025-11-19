import 'package:flutter/material.dart';

enum SlideDirection { fromTop, fromBottom, fromLeft, fromRight }

class SlideAnimation extends StatefulWidget {
  final int position;
  final int itemCount;
  final Widget child;
  final SlideDirection slideDirection;
  final AnimationController? animationController;

  const SlideAnimation({
    super.key,
    required this.position,
    required this.itemCount,
    required this.child,
    this.slideDirection = SlideDirection.fromBottom,
    this.animationController,
  });

  @override
  State<SlideAnimation> createState() => _SlideAnimationState();
}

class _SlideAnimationState extends State<SlideAnimation>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<Offset> _offsetAnimation;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _controller = widget.animationController ??
        AnimationController(
          vsync: this,
          duration: const Duration(milliseconds: 800),
        );

    final delay = widget.position * 0.05;
    
    _fadeAnimation = CurvedAnimation(
      parent: _controller,
      curve: Interval(
        delay.clamp(0.0, 1.0),
        (delay + 0.4).clamp(0.0, 1.0),
        curve: Curves.easeOut,
      ),
    );

    Offset beginOffset;
    switch (widget.slideDirection) {
      case SlideDirection.fromTop:
        beginOffset = const Offset(0, -0.2);
        break;
      case SlideDirection.fromBottom:
        beginOffset = const Offset(0, 0.2);
        break;
      case SlideDirection.fromLeft:
        beginOffset = const Offset(-0.2, 0);
        break;
      case SlideDirection.fromRight:
        beginOffset = const Offset(0.2, 0);
        break;
    }

    _offsetAnimation = Tween<Offset>(
      begin: beginOffset,
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _controller,
      curve: Interval(
        delay.clamp(0.0, 1.0),
        (delay + 0.4).clamp(0.0, 1.0),
        curve: Curves.easeOut,
      ),
    ));

    if (widget.animationController == null) {
      _controller.forward();
    }
  }

  @override
  void dispose() {
    if (widget.animationController == null) {
      _controller.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _fadeAnimation,
      child: SlideTransition(
        position: _offsetAnimation,
        child: widget.child,
      ),
    );
  }
}
