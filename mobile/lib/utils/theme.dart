import 'package:flutter/material.dart';

class AppTheme {
  // Brand palette (refined + professional)
  static const Color primary = Color(0xFF0B7A75);
  static const Color primaryDark = Color(0xFF075B57);
  static const Color primaryLight = Color(0xFF49B5A3);
  static const Color secondary = Color(0xFF1F3C88);
  static const Color neutralBackground = Color(0xFFF4F6F9);
  static const Color neutralSurface = Colors.white;
  static const Color neutralBorder = Color(0xFFD5DCE5);
  static const Color neutralText = Color(0xFF1A2433);

  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFF0B7A75), Color(0xFF0F918B)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient successGradient = LinearGradient(
    colors: [Color(0xFF0F7A5C), Color(0xFF14A06D)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient warningGradient = LinearGradient(
    colors: [Color(0xFFB35C0A), Color(0xFFE17A14)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient errorGradient = LinearGradient(
    colors: [Color(0xFFD64545), Color(0xFFE46D6D)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  // Border radius
  static const double radiusSmall = 8;
  static const double radiusMedium = 12;
  static const double radiusLarge = 16;
  static const double radiusXLarge = 22;

  // Spacing
  static const double spaceXSmall = 4;
  static const double spaceSmall = 8;
  static const double spaceMedium = 16;
  static const double spaceLarge = 24;
  static const double spaceXLarge = 32;

  // Shadows
  static List<BoxShadow> get cardShadow => [
        BoxShadow(
          color: const Color(0xFF102437).withValues(alpha: 0.06),
          blurRadius: 18,
          offset: const Offset(0, 6),
        ),
      ];

  static List<BoxShadow> get elevatedShadow => [
        BoxShadow(
          color: const Color(0xFF102437).withValues(alpha: 0.12),
          blurRadius: 28,
          offset: const Offset(0, 12),
        ),
        BoxShadow(
          color: const Color(0xFF102437).withValues(alpha: 0.07),
          blurRadius: 12,
          offset: const Offset(0, 3),
        ),
      ];

  static ThemeData get lightTheme {
    const colorScheme = ColorScheme(
      brightness: Brightness.light,
      primary: primary,
      onPrimary: Colors.white,
      primaryContainer: Color(0xFFD4EEE8),
      onPrimaryContainer: Color(0xFF053330),
      secondary: secondary,
      onSecondary: Colors.white,
      secondaryContainer: Color(0xFFDEE4F5),
      onSecondaryContainer: Color(0xFF142151),
      error: Color(0xFFD64545),
      onError: Colors.white,
      errorContainer: Color(0xFFF8DAD9),
      onErrorContainer: Color(0xFF601410),
      surface: neutralSurface,
      onSurface: neutralText,
      surfaceContainerHighest: Color(0xFFE6EBF4),
      outline: neutralBorder,
    );

    final base = ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: neutralBackground,
    );

    return base.copyWith(
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.transparent,
        foregroundColor: neutralText,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: false,
        titleTextStyle: TextStyle(
          fontSize: 20,
          fontWeight: FontWeight.w600,
          color: neutralText,
        ),
      ),
      cardTheme: CardThemeData(
        color: neutralSurface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusLarge),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusMedium),
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w600),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusMedium),
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusMedium),
          ),
          side: const BorderSide(color: neutralBorder),
          textStyle: const TextStyle(fontWeight: FontWeight.w600),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: primary,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          textStyle: const TextStyle(fontWeight: FontWeight.w600),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMedium),
          borderSide: const BorderSide(color: neutralBorder),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMedium),
          borderSide: const BorderSide(color: neutralBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMedium),
          borderSide: const BorderSide(color: primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMedium),
          borderSide: const BorderSide(color: Color(0xFFE46D6D)),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        labelStyle: const TextStyle(fontWeight: FontWeight.w500),
      ),
      chipTheme: base.chipTheme.copyWith(
        backgroundColor: const Color(0xFFE9EEF5),
        selectedColor: colorScheme.primaryContainer,
        labelStyle: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w500,
        ),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMedium),
        ),
      ),
      dividerTheme: const DividerThemeData(
        color: Color(0xFFE0E6F0),
        thickness: 1,
        space: 0,
      ),
      navigationBarTheme: NavigationBarThemeData(
        height: 64,
        backgroundColor: neutralSurface,
        indicatorColor: colorScheme.primaryContainer.withValues(alpha: 0.6),
        surfaceTintColor: Colors.transparent,
        iconTheme: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return const IconThemeData(color: primary);
          }
          return const IconThemeData(color: Color(0xFF77808E));
        }),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return const TextStyle(fontSize: 12, fontWeight: FontWeight.w600);
          }
          return const TextStyle(fontSize: 12, fontWeight: FontWeight.w500);
        }),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: neutralSurface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(radiusXLarge)),
        ),
      ),
    );
  }

  static ThemeData get darkTheme {
    const colorScheme = ColorScheme(
      brightness: Brightness.dark,
      primary: primaryLight,
      onPrimary: Color(0xFF042D2A),
      primaryContainer: Color(0xFF0B514C),
      onPrimaryContainer: Color(0xFFD4EEE8),
      secondary: Color(0xFFA9B7E8),
      onSecondary: Color(0xFF101C46),
      secondaryContainer: Color(0xFF273B71),
      onSecondaryContainer: Color(0xFFDEE4F5),
      error: Color(0xFFF1A6A3),
      onError: Color(0xFF5C1512),
      errorContainer: Color(0xFF822122),
      onErrorContainer: Color(0xFFFAD9D7),
      surface: Color(0xFF0F1724),
      onSurface: Color(0xFFE7ECF5),
      surfaceContainerHighest: Color(0xFF1D2633),
      outline: Color(0xFF3F4B5D),
    );

    final base = ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: const Color(0xFF0A1420),
    );

    return base.copyWith(
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: false,
      ),
      cardTheme: CardThemeData(
        color: colorScheme.surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusLarge),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: const Color(0xFF1A2433),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMedium),
          borderSide: const BorderSide(color: Color(0xFF3F4B5D)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMedium),
          borderSide: const BorderSide(color: Color(0xFF3F4B5D)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMedium),
          borderSide: const BorderSide(color: primaryLight, width: 2),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
      navigationBarTheme: NavigationBarThemeData(
        height: 64,
        backgroundColor: const Color(0xFF1C2533),
        indicatorColor: colorScheme.primaryContainer.withValues(alpha: 0.4),
      ),
    );
  }
}
