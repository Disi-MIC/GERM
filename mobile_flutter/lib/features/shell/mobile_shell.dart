import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/auth/auth_state.dart';
import '../../core/models/current_user.dart';
import '../../core/theme/app_theme.dart';

/// Miroir de layout/mobile-shell/mobile-shell.component.ts — barre du bas à
/// 4 onglets + un bouton "Plus" ouvrant une feuille de liens secondaires,
/// plutôt qu'une sidebar façon desktop (voir shell.component.ts, qui reste
/// hors-scope mobile). Le mode "Administration" (deuxième état de la barre,
/// menu groupé par section) arrive avec les premières sections admin
/// (phase 3+) — pour l'instant un seul point d'entrée placeholder.
class MobileShell extends ConsumerWidget {
  const MobileShell({required this.location, required this.child, super.key});

  final String location;
  final Widget child;

  static const _tabs = [
    _TabDef('/mon-espace/tableau-de-bord', Icons.speed_outlined, 'Accueil'),
    _TabDef('/mon-espace/conges', Icons.event_outlined, 'Congés'),
    _TabDef('/mon-espace/tickets', Icons.support_agent_outlined, 'Tickets'),
    _TabDef('/profil', Icons.account_circle_outlined, 'Profil'),
  ];

  // Titres par route (miroir des `titre:` déjà déclarés sur chaque écran via
  // AppPageHeader) — dérivés directement de `location` plutôt que poussés
  // par l'écran lui-même : un `pop()` (retour arrière) rend la page
  // précédente visible sans rejouer son cycle de vie (initState/build), donc
  // un titre "poussé" resterait bloqué sur celui de la page quittée.
  static const _titres = {
    '/mon-espace/tableau-de-bord': 'Mon tableau de bord',
    '/mon-espace/conges': 'Congés',
    '/mon-espace/tickets': 'Tickets',
    '/profil': 'Profil',
    '/mon-espace/carriere': 'Ma carrière',
    '/mon-espace/parc-informatique': 'Mon parc informatique',
    '/mon-espace/parc-automobile': 'Mon parc automobile',
    '/mon-espace/carte-professionnelle': 'Ma carte professionnelle',
    '/mon-espace/documents': 'Mes documents',
    '/mon-espace/apercu-service': 'Aperçu de mon service',
    '/mon-espace/apercu-direction': 'Aperçu de ma direction',
    '/administration': 'Administration',
  };

  int _indexActif(String location) {
    final index = _tabs.indexWhere((t) => location.startsWith(t.path));
    return index == -1 ? 0 : index;
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final titre = _titres[location] ?? 'GERM';
    final estRacine = _tabs.any((t) => t.path == location);

    return Scaffold(
      appBar: AppBar(
        leading: estRacine
            ? null
            : IconButton(
                icon: const Icon(Icons.chevron_left),
                onPressed: () => context.canPop() ? context.pop() : context.go('/mon-espace/tableau-de-bord'),
              ),
        automaticallyImplyLeading: false,
        title: Text(titre),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_none),
            onPressed: () => _ouvrirNotifications(context),
          ),
        ],
      ),
      body: SafeArea(top: false, child: child),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _indexActif(location),
        onTap: (index) {
          if (index < _tabs.length) {
            context.go(_tabs[index].path);
          }
        },
        items: [
          ..._tabs.map((t) => BottomNavigationBarItem(icon: Icon(t.icon), label: t.label)),
        ],
      ),
      floatingActionButton: FloatingActionButton.small(
        heroTag: 'plus',
        backgroundColor: AppColors.secondary,
        onPressed: () => _ouvrirPlus(context, ref),
        child: const Icon(Icons.grid_view_rounded, color: Colors.white),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.endDocked,
    );
  }

  void _ouvrirNotifications(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => const _SheetScaffold(
        titre: 'Notifications',
        // Le vrai flux de notifications (GET /api/me/notifications, marquer
        // lu...) arrive avec le reste de "Mon espace" (phase 2) — squelette
        // de la feuille posé ici pour ne pas refaire la structure ensuite.
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Text('Aucune notification.', textAlign: TextAlign.center),
        ),
      ),
    );
  }

  void _ouvrirPlus(BuildContext context, WidgetRef ref) {
    final user = ref.read(authProvider).currentUser;
    final accesAdmin = user != null &&
        user.hasAnyRole(const [
          'ROLE_RH_PERSONNEL', 'ROLE_RH_CONGE', 'ROLE_RH_CARTE_PRO',
          'ROLE_IT_STOCK', 'ROLE_IT_TICKETS', 'ROLE_IT_RESPONSABLE',
          'ROLE_RH_RESPONSABLE', 'ROLE_AUTORITE',
        ]);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) => _SheetScaffold(
        titre: 'Plus',
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const _PlusLink(icon: Icons.trending_up, label: 'Ma carrière', path: '/mon-espace/carriere'),
            const _PlusLink(icon: Icons.laptop_mac_outlined, label: 'Mon parc informatique', path: '/mon-espace/parc-informatique'),
            const _PlusLink(icon: Icons.local_shipping_outlined, label: 'Mon parc automobile', path: '/mon-espace/parc-automobile'),
            const _PlusLink(icon: Icons.badge_outlined, label: 'Ma carte professionnelle', path: '/mon-espace/carte-professionnelle'),
            const _PlusLink(icon: Icons.folder_open_outlined, label: 'Mes documents', path: '/mon-espace/documents'),
            if (user?.serviceResponsableId != null)
              const _PlusLink(icon: Icons.groups_outlined, label: 'Aperçu de mon service', path: '/mon-espace/apercu-service'),
            if (user?.directionDirigeeId != null)
              const _PlusLink(icon: Icons.account_tree_outlined, label: 'Aperçu de ma direction', path: '/mon-espace/apercu-direction'),
            if (accesAdmin) ...[
              const Divider(height: 24),
              const _PlusLink(icon: Icons.admin_panel_settings_outlined, label: 'Administration', path: '/administration'),
            ],
            const Divider(height: 24),
            ListTile(
              leading: const Icon(Icons.logout, color: AppColors.danger),
              title: const Text('Déconnexion', style: TextStyle(color: AppColors.danger)),
              onTap: () {
                Navigator.of(sheetContext).pop();
                ref.read(authProvider.notifier).logout();
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _TabDef {
  const _TabDef(this.path, this.icon, this.label);
  final String path;
  final IconData icon;
  final String label;
}

class _PlusLink extends StatelessWidget {
  const _PlusLink({required this.icon, required this.label, required this.path});

  final IconData icon;
  final String label;
  final String path;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(icon),
      title: Text(label),
      onTap: () {
        Navigator.of(context).pop();
        context.push(path);
      },
    );
  }
}

class _SheetScaffold extends StatelessWidget {
  const _SheetScaffold({required this.titre, required this.child});

  final String titre;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.only(top: 8),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(titre, style: Theme.of(context).textTheme.titleMedium),
                  IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.of(context).pop()),
                ],
              ),
            ),
            Flexible(child: SingleChildScrollView(child: child)),
          ],
        ),
      ),
    );
  }
}
