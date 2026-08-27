import 'package:flutter/material.dart';

/// Palette Gentelella (mêmes tokens que frontend/src/styles.scss et
/// shared/chart/chart-colors.ts côté Angular) — cohérence visuelle entre le
/// web et le mobile, un seul jeu de couleurs de référence pour l'app GERM.
class AppColors {
  AppColors._();

  static const primary = Color(0xFF1ABB9C);
  static const secondary = Color(0xFF626D7D);
  static const success = Color(0xFF2FB344);
  static const info = Color(0xFF066FD1);
  static const warning = Color(0xFFF59F00);
  static const danger = Color(0xFFD63939);
  static const purple = Color(0xFFAE3EC9);
  static const orange = Color(0xFFF76707);

  /// Fond de l'écran de démarrage (voir capacitor.config.ts SplashScreen.backgroundColor côté Capacitor).
  static const splashBackground = Color(0xFF122032);

  static const List<Color> chartPalette = [
    primary,
    info,
    warning,
    purple,
    orange,
    success,
    danger,
    secondary,
  ];
}

class AppTheme {
  AppTheme._();

  static ThemeData light() {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: AppColors.primary,
      primary: AppColors.primary,
      brightness: Brightness.light,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: const Color(0xFFF5F6F8),
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.white,
        foregroundColor: Color(0xFF1F2937),
        elevation: 0,
        centerTitle: false,
      ),
      cardTheme: CardTheme(
        elevation: 0,
        color: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: const BorderSide(color: Color(0xFFE6E7EB)),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: const Color(0xFFF3F4F6),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide.none,
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: Colors.white,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.secondary,
        type: BottomNavigationBarType.fixed,
      ),
    );
  }
}
