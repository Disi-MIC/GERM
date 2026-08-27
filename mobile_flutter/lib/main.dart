import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/auth/auth_state.dart';
import 'core/network/api_client.dart';
import 'core/network/api_client_provider.dart';
import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  final apiClient = await ApiClient.create();

  runApp(
    ProviderScope(
      overrides: [apiClientProvider.overrideWithValue(apiClient)],
      child: const GermApp(),
    ),
  );
}

class GermApp extends ConsumerStatefulWidget {
  const GermApp({super.key});

  @override
  ConsumerState<GermApp> createState() => _GermAppState();
}

class _GermAppState extends ConsumerState<GermApp> {
  @override
  void initState() {
    super.initState();
    // Équivalent de fetchMe() au démarrage côté auth.service.ts : détermine
    // si le cookie de session persistant est encore valide avant de choisir
    // entre écran de connexion et "Mon espace".
    Future.microtask(() => ref.read(authProvider.notifier).restore());
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: 'GERM',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      routerConfig: ref.watch(routerProvider),
    );
  }
}
