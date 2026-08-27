import 'package:flutter/material.dart';

import '../../core/theme/app_theme.dart';

/// Affiché le temps qu'AuthNotifier.restore() (équivalent fetchMe() initial)
/// détermine si un cookie de session valide existe déjà — voir
/// core/router/app_router.dart. Le splash natif (flutter_native_splash) est
/// masqué avant que Flutter n'affiche son premier frame ; celui-ci prend le
/// relais le temps de l'appel réseau, sans flash de l'écran de connexion.
class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  static const path = '/splash';

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: AppColors.splashBackground,
      body: Center(
        child: CircularProgressIndicator(color: Colors.white),
      ),
    );
  }
}
