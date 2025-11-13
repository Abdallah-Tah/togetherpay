import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  /// Brand palette
  static const Color primary = Color(0xFF00CDBE);
  static const Color primaryDark = Color(0xFF00A79D);
  static const Color primaryLight = Color(0xFFC8F8F3);

  static const Color background = Color(0xFFF7FAFB);
  static const Color surface = Colors.white;

  static const Color gray900 = Color(0xFF1B1B1D);
  static const Color gray700 = Color(0xFF3F3F42);
  static const Color gray600 = Color(0xFF6B6B6E);
  static const Color gray400 = Color(0xFF99999C);
  static const Color gray200 = Color(0xFFECECEC);
  static const Color gray100 = Color(0xFFF3F3F4);

  static const Color success = Color(0xFF00C46C);
  static const Color warning = Color(0xFFF5A524);
  static const Color error = Color(0xFFE5484D);

  /// Gradients used throughout the app (keeps compatibility with legacy usage).
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [primary, primaryDark],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient successGradient = LinearGradient(
    colors: [Color(0xFF12D68E), Color(0xFF08A36A)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient warningGradient = LinearGradient(
    colors: [Color(0xFFFFC05B), Color(0xFFF58A1F)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient errorGradient = LinearGradient(
    colors: [Color(0xFFFA5C5C), Color(0xFFDB1F3F)],
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

  static TextTheme _textTheme(TextTheme base, Color displayColor) {
    final textTheme = GoogleFonts.interTextTheme(base).apply(
      bodyColor: displayColor,
      displayColor: displayColor,
    );

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
      surfaceContainerHighest: background,
    );

    final textTheme = _textTheme(base.textTheme, gray900);

    return base.copyWith(
      colorScheme: colorScheme,
      scaffoldBackgroundColor: background,
      textTheme: textTheme,
      appBarTheme: AppBarTheme(
        elevation: 0,
        scrolledUnderElevation: 0,
        backgroundColor: background,
        surfaceTintColor: Colors.transparent,
        foregroundColor: gray900,
        titleTextStyle: textTheme.titleLarge,
      ),
      cardTheme: const CardThemeData(
        color: surface,
        elevation: 0,
        margin: EdgeInsets.zero,
        surfaceTintColor: Colors.transparent,
        shadowColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: cardRadius,
          side: BorderSide(color: gray200),
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
