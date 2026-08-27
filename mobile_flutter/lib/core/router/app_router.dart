import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../auth/auth_state.dart';
import '../../features/auth/login_screen.dart';
import '../../features/auth/splash_screen.dart';
import '../../features/shell/mobile_shell.dart';
import '../../features/mon_espace/tableau_de_bord_screen.dart';
import '../../features/mon_espace/mon_espace_placeholder_screen.dart';

/// Miroir de app.routes.ts : les guards Angular (authGuard/roleGuard/
/// homeGuard) se traduisent en une seule fonction `redirect` — go_router
/// centralise ça plutôt que le split par-route de CanActivate. La
/// répartition par rôle explicite (pas de hiérarchie Symfony recalculée
/// côté client) et l'accès admin protégé par mot de passe (adminAccessGuard,
/// voir core/auth/admin_access.dart) seront branchés route par route à
/// mesure que les sections Administration sont construites (phases
/// suivantes) — pour l'instant seule "Mon espace" (ROLE_AGENT, accessible à
/// tout compte connecté) existe.
final routerProvider = Provider<GoRouter>((ref) => AppRouter(ref).router);

class AppRouter {
  AppRouter(this.ref);

  final Ref ref;

  static const loginPath = '/login';
  static const homePath = '/mon-espace/tableau-de-bord';

  late final router = GoRouter(
    initialLocation: SplashScreen.path,
    refreshListenable: _AuthListenable(ref),
    redirect: _redirect,
    routes: [
      GoRoute(path: '/', redirect: (context, state) => homePath),
      GoRoute(path: SplashScreen.path, builder: (context, state) => const SplashScreen()),
      GoRoute(path: loginPath, builder: (context, state) => const LoginScreen()),
      ShellRoute(
        builder: (context, state, child) => MobileShell(location: state.uri.toString(), child: child),
        routes: [
          GoRoute(
            path: homePath,
            builder: (context, state) => const TableauDeBordScreen(),
          ),
          GoRoute(
            path: '/mon-espace/conges',
            builder: (context, state) => const MonEspacePlaceholderScreen(titre: 'Congés'),
          ),
          GoRoute(
            path: '/mon-espace/tickets',
            builder: (context, state) => const MonEspacePlaceholderScreen(titre: 'Tickets'),
          ),
          GoRoute(
            path: '/profil',
            builder: (context, state) => const MonEspacePlaceholderScreen(titre: 'Profil'),
          ),
          // Destinations de la feuille "Plus" (voir features/shell/mobile_shell.dart)
          // pas encore construites — même squelette que ci-dessus, à remplacer
          // écran par écran en phase 2 sans retoucher la nav.
          GoRoute(
            path: '/mon-espace/carriere',
            builder: (context, state) => const MonEspacePlaceholderScreen(titre: 'Ma carrière'),
          ),
          GoRoute(
            path: '/mon-espace/parc-informatique',
            builder: (context, state) => const MonEspacePlaceholderScreen(titre: 'Mon parc informatique'),
          ),
          GoRoute(
            path: '/mon-espace/parc-automobile',
            builder: (context, state) => const MonEspacePlaceholderScreen(titre: 'Mon parc automobile'),
          ),
          GoRoute(
            path: '/mon-espace/carte-professionnelle',
            builder: (context, state) => const MonEspacePlaceholderScreen(titre: 'Ma carte professionnelle'),
          ),
          GoRoute(
            path: '/mon-espace/documents',
            builder: (context, state) => const MonEspacePlaceholderScreen(titre: 'Mes documents'),
          ),
          GoRoute(
            path: '/mon-espace/apercu-service',
            builder: (context, state) => const MonEspacePlaceholderScreen(titre: 'Aperçu de mon service'),
          ),
          GoRoute(
            path: '/mon-espace/apercu-direction',
            builder: (context, state) => const MonEspacePlaceholderScreen(titre: 'Aperçu de ma direction'),
          ),
          GoRoute(
            path: '/administration',
            builder: (context, state) => const MonEspacePlaceholderScreen(titre: 'Administration'),
          ),
        ],
      ),
    ],
  );

  String? _redirect(BuildContext context, GoRouterState state) {
    final auth = ref.read(authProvider);

    // Équivalent du splash pendant que restore() (fetchMe() initial) n'a pas
    // encore répondu — ni login ni home tant qu'on ne sait pas si un cookie
    // de session valide existe déjà.
    if (!auth.initialized) {
      return state.matchedLocation == SplashScreen.path ? null : SplashScreen.path;
    }

    final onLogin = state.matchedLocation == loginPath;
    final onSplash = state.matchedLocation == SplashScreen.path;

    if (!auth.isLoggedIn) {
      return onLogin ? null : loginPath;
    }

    // homeGuard : connecté et sur /login ou le splash -> toujours "Mon
    // espace", jamais l'admin par défaut, identique côté web et mobile.
    if (onLogin || onSplash) {
      return homePath;
    }

    return null;
  }
}

/// go_router n'écoute pas Riverpod nativement — ce pont republie tout
/// changement d'AuthState comme notification pour que `redirect` soit
/// ré-évalué (ex. juste après login()/logout()).
class _AuthListenable extends ChangeNotifier {
  _AuthListenable(this.ref) {
    ref.listen(authProvider, (_, __) => notifyListeners());
  }

  final Ref ref;
}
