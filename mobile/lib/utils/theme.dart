import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  /// Brand palette - Ethereal Glassmorphism
  static const Color primary = Color(0xFF006D77); // Deep Green/Teal
  static const Color primaryDark = Color(0xFF004E56);
  static const Color primaryLight = Color(0xFFCCFBF7);

  // Iridescent Accents
  static const Color lavender = Color(0xFFE0C3FC);
  static const Color mint = Color(0xFFB9FBC0);
  static const Color paleGold = Color(0xFFFDE68A);
  static const Color coral = Color(0xFFFF7F50);

  static const Color deepGreen = Color(0xFF004D40);
  static const Color deepGreenDark = Color(0xFF002420);

  static const Color cream = Color(0xFFFDFBF7);
  static const Color background = Color(0xFFFDFBF7); // Alias to cream
  static const Color surface = Colors.white;

  static const Color navy = Color(0xFF0F172A); // restored for compatibility
  static const Color navyLight = Color(0xFF1E293B);

  static const Color gray900 = Color(0xFF18181B);
  static const Color gray700 = Color(0xFF3F3F46);
  static const Color gray600 = Color(0xFF52525B);
  static const Color gray400 = Color(0xFFA1A1AA);
  static const Color gray200 = Color(0xFFE4E4E7);
  static const Color gray100 = Color(0xFFF4F4F5);

  static const Color success = Color(0xFF10B981);
  static const Color warning = Color(0xFFF59E0B);
  static const Color error = Color(0xFFEF4444);

  /// Gradients used throughout the app
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [deepGreen, deepGreenDark],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient meshGradient = LinearGradient(
    colors: [
      Color(0xFF004D40), // Deep Green
      Color(0xFF00695C), // Teal 800
      Color(0xFF004D40), // Deep Green
    ],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient auroraGradient = LinearGradient(
    colors: [
      lavender,
      mint,
      paleGold,
    ],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient glassGradient = LinearGradient(
    colors: [
      Colors.white24,
      Colors.white10,
    ],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient successGradient = LinearGradient(
    colors: [Color(0xFF10B981), Color(0xFF059669)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient warningGradient = LinearGradient(
    colors: [Color(0xFFF59E0B), Color(0xFFD97706)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient errorGradient = LinearGradient(
    colors: [Color(0xFFEF4444), Color(0xFFDC2626)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  /// Radius scale
  static const double radiusXS = 6;
  static const double radiusS = 10;
  static const double radiusM = 16;
  static const double radiusL = 22;
  static const double radiusXL = 28;

  /// Legacy aliases (so existing widgets keep working)
  static const double radiusSmall = radiusS;
  static const double radiusMedium = radiusM;
  static const double radiusLarge = radiusL;
  static const double radiusXLarge = radiusXL;

  /// Spacing scale
  static const double spaceXS = 4;
  static const double spaceS = 8;
  static const double spaceM = 16;
  static const double spaceL = 24;
  static const double spaceXL = 32;
  static const double spaceXXL = 48;

  /// Legacy aliases
  static const double spaceXSmall = spaceXS;
  static const double spaceSmall = spaceS;
  static const double spaceMedium = spaceM;
  static const double spaceLarge = spaceL;
  static const double spaceXLarge = spaceXL;

  static const BorderRadius cardRadius = BorderRadius.all(Radius.circular(radiusL));

  static List<BoxShadow> get softShadow => [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.04),
          blurRadius: 20,
          offset: const Offset(0, 6),
        ),
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.02),
          blurRadius: 40,
          offset: const Offset(0, 18),
        ),
      ];

  /// Enhanced shadow for cards
  static List<BoxShadow> get cardShadow => [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.06),
          blurRadius: 16,
          offset: const Offset(0, 4),
        ),
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.03),
          blurRadius: 8,
          offset: const Offset(0, 2),
        ),
      ];

  /// Elevated shadow for interactive elements
  static List<BoxShadow> get elevatedShadow => [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.1),
          blurRadius: 24,
          offset: const Offset(0, 8),
        ),
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.05),
          blurRadius: 12,
          offset: const Offset(0, 4),
        ),
      ];

  /// Subtle inner shadow effect
  static List<BoxShadow> get innerShadow => [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.02),
          blurRadius: 4,
          offset: const Offset(0, 2),
        ),
      ];


  static TextTheme _textTheme(TextTheme base, Color displayColor) {
    // Use GoogleFonts with fallback to prevent network-dependent errors
    // If Inter font fails to load, it will use the system default
    TextTheme textTheme;
    try {
      textTheme = GoogleFonts.interTextTheme(base).apply(
        bodyColor: displayColor,
        displayColor: displayColor,
      );
    } catch (e) {
      // Fallback to base theme if font loading fails
      textTheme = base.apply(
        bodyColor: displayColor,
        displayColor: displayColor,
        fontFamily: 'Inter', // Will use system default if not available
      );
    }


    return textTheme.copyWith(
      headlineLarge: textTheme.headlineLarge?.copyWith(
        fontWeight: FontWeight.w600,
        letterSpacing: -0.5,
      ),
      headlineMedium: textTheme.headlineMedium?.copyWith(
        fontWeight: FontWeight.w600,
        letterSpacing: -0.3,
      ),
      headlineSmall: textTheme.headlineSmall?.copyWith(
        fontWeight: FontWeight.w600,
      ),
      titleLarge: textTheme.titleLarge?.copyWith(
        fontWeight: FontWeight.w600,
      ),
      titleMedium: textTheme.titleMedium?.copyWith(
        fontWeight: FontWeight.w500,
        letterSpacing: 0.1,
      ),
      titleSmall: textTheme.titleSmall?.copyWith(
        fontWeight: FontWeight.w500,
      ),
      bodyLarge: textTheme.bodyLarge?.copyWith(fontWeight: FontWeight.w400),
      bodyMedium: textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w400),
      labelLarge: textTheme.labelLarge?.copyWith(
        fontWeight: FontWeight.w600,
        letterSpacing: 0.3,
      ),
      labelMedium: textTheme.labelMedium?.copyWith(
        fontWeight: FontWeight.w500,
        letterSpacing: 0.2,
      ),
      labelSmall: textTheme.labelSmall?.copyWith(
        fontWeight: FontWeight.w500,
        letterSpacing: 0.4,
      ),
    );
  }

  static ThemeData get lightTheme {
    final base = ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
    );

    final colorScheme = ColorScheme(
      brightness: Brightness.light,
      primary: primary,
      onPrimary: Colors.white,
      primaryContainer: primaryLight,
      onPrimaryContainer: primaryDark,
      secondary: const Color(0xFF4D67FF),
      onSecondary: Colors.white,
      secondaryContainer: const Color(0xFFE2E7FF),
      onSecondaryContainer: const Color(0xFF0F1B4C),
      tertiary: const Color(0xFFFB6F92),
      onTertiary: Colors.white,
      error: error,
      onError: Colors.white,
      errorContainer: const Color(0xFFFCE8EA),
      onErrorContainer: error,
      surface: surface,
      onSurface: gray900,
      surfaceTint: Colors.transparent,
      outline: gray200,
      outlineVariant: gray100,
      shadow: Colors.black.withValues(alpha: 0.08),
      scrim: Colors.black54,
      inverseSurface: gray900,
      onInverseSurface: Colors.white,
      inversePrimary: primaryDark,
      surfaceContainerHighest: cream,
    );

    final textTheme = _textTheme(base.textTheme, gray900);

    return base.copyWith(
      colorScheme: colorScheme,
      scaffoldBackgroundColor: cream,
      textTheme: textTheme,
      appBarTheme: AppBarTheme(
        elevation: 0,
        scrolledUnderElevation: 0,
        backgroundColor: cream,
        surfaceTintColor: Colors.transparent,
        foregroundColor: gray900,
        titleTextStyle: textTheme.titleLarge,
      ),
      cardTheme: CardThemeData(
        color: surface,
        elevation: 0,
        margin: EdgeInsets.zero,
        surfaceTintColor: Colors.transparent,
        shadowColor: Colors.black.withValues(alpha: 0.08),
        shape: RoundedRectangleBorder(
          borderRadius: cardRadius,
          side: BorderSide(color: gray200.withValues(alpha: 0.6)),
        ),
      ),
      listTileTheme: ListTileThemeData(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusM)),
        tileColor: surface,
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
        iconColor: gray700,
        textColor: gray900,
        dense: false,
      ),
      chipTheme: ChipThemeData(
        backgroundColor: primaryLight,
        labelStyle: textTheme.labelMedium?.copyWith(color: primaryDark),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusM)),
        side: BorderSide.none,
      ),
      dividerTheme: const DividerThemeData(
        color: gray200,
        thickness: 1,
        space: 1,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          elevation: 0,
          minimumSize: const Size.fromHeight(52),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusL)),
          textStyle: textTheme.labelLarge?.copyWith(color: Colors.white),
          backgroundColor: primary,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size.fromHeight(52),
          backgroundColor: primary,
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusL)),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          textStyle: textTheme.labelLarge,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(52),
          side: const BorderSide(color: gray200, width: 1.2),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusL)),
          textStyle: textTheme.labelLarge?.copyWith(color: gray900),
          foregroundColor: gray900,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: primary,
          textStyle: textTheme.labelLarge,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surface,
        hintStyle: textTheme.bodyMedium?.copyWith(color: gray400),
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusL),
          borderSide: const BorderSide(color: gray200),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusL),
          borderSide: const BorderSide(color: gray200),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusL),
          borderSide: const BorderSide(color: primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusL),
          borderSide: const BorderSide(color: error),
        ),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: surface,
        surfaceTintColor: Colors.transparent,
        modalBackgroundColor: surface,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(radiusXL)),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: surface,
        elevation: 0,
        indicatorColor: primaryLight.withValues(alpha: 0.65),
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
        iconTheme: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return const IconThemeData(color: primary);
          }
          return const IconThemeData(color: gray600);
        }),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final style = textTheme.labelMedium;
          if (states.contains(WidgetState.selected)) {
            return style?.copyWith(color: primary);
          }
          return style?.copyWith(color: gray600);
        }),
      ),
      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: primary,
        foregroundColor: Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
        elevation: 6,
      ),
      iconTheme: const IconThemeData(color: gray700),
    );
  }

  static ThemeData get darkTheme {
    final base = ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
    );

    const darkSurface = Color(0xFF101215);
    const darkCard = Color(0xFF181B1F);
    const darkOutline = Color(0xFF2A2E33);

    final colorScheme = ColorScheme(
      brightness: Brightness.dark,
      primary: primaryLight,
      onPrimary: gray900,
      primaryContainer: primaryDark,
      onPrimaryContainer: Colors.white,
      secondary: const Color(0xFFAEC6FF),
      onSecondary: gray900,
      secondaryContainer: const Color(0xFF1C2440),
      onSecondaryContainer: Colors.white,
      tertiary: const Color(0xFFFF9EC0),
      onTertiary: gray900,
      error: const Color(0xFFFFB4AB),
      onError: const Color(0xFF690005),
      errorContainer: const Color(0xFF93000A),
      onErrorContainer: const Color(0xFFFFDAD6),
      surface: darkSurface,
      onSurface: Colors.white,
      outline: darkOutline,
      outlineVariant: const Color(0xFF34383D),
      surfaceTint: Colors.transparent,
      shadow: Colors.black.withValues(alpha: 0.45),
      scrim: Colors.black,
      inverseSurface: Colors.white,
      onInverseSurface: gray900,
      inversePrimary: primary,
      surfaceContainerHighest: darkCard,
    );

    final textTheme = _textTheme(base.textTheme, Colors.white);

    return base.copyWith(
      colorScheme: colorScheme,
      scaffoldBackgroundColor: darkSurface,
      textTheme: textTheme,
      appBarTheme: AppBarTheme(
        elevation: 0,
        scrolledUnderElevation: 0,
        backgroundColor: darkSurface,
        foregroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        titleTextStyle: textTheme.titleLarge,
      ),
      cardTheme: CardThemeData(
        color: darkCard,
        elevation: 0,
        margin: EdgeInsets.zero,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: cardRadius,
          side: BorderSide(color: darkOutline.withValues(alpha: 0.4)),
        ),
      ),
      listTileTheme: ListTileThemeData(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusM)),
        tileColor: darkCard,
        iconColor: primaryLight,
        textColor: Colors.white,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: darkCard,
        hintStyle: textTheme.bodyMedium?.copyWith(color: gray400),
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusL),
          borderSide: const BorderSide(color: darkOutline),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusL),
          borderSide: const BorderSide(color: darkOutline),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusL),
          borderSide: const BorderSide(color: primaryLight, width: 2),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: darkCard,
        indicatorColor: primaryDark.withValues(alpha: 0.3),
        iconTheme: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return const IconThemeData(color: primaryLight);
          }
          return const IconThemeData(color: Colors.white70);
        }),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          final style = textTheme.labelMedium;
          if (states.contains(WidgetState.selected)) {
            return style?.copyWith(color: primaryLight);
          }
          return style?.copyWith(color: Colors.white70);
        }),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: darkCard,
        modalBackgroundColor: darkCard,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(radiusXL)),
        ),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: primaryDark.withValues(alpha: 0.2),
        labelStyle: textTheme.labelMedium?.copyWith(color: Colors.white),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusM)),
        side: BorderSide.none,
      ),
      dividerTheme: const DividerThemeData(
        color: darkOutline,
        thickness: 1,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primaryLight,
          foregroundColor: gray900,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusL)),
          minimumSize: const Size.fromHeight(52),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(52),
          side: BorderSide(color: Colors.white.withValues(alpha: 0.2)),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radiusL)),
          foregroundColor: Colors.white,
        ),
      ),
      iconTheme: const IconThemeData(color: Colors.white),
    );
  }
}
