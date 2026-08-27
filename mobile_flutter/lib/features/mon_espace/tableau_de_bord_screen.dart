import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/auth/auth_state.dart';
import '../../shared/widgets/app_page_header.dart';
import '../../shared/widgets/app_panel.dart';

/// Équivalent de profil/dashboard/mon-tableau-de-bord.component.ts — écran
/// d'accueil natif (homeGuard y redirige toujours `/`). Les vraies tuiles
/// (carrière/ressources/demandes en cours, GET /api/me/tableau-de-bord)
/// arrivent avec le reste de "Mon espace" en phase 2 ; ici juste la
/// structure + l'identité de session pour prouver le flux d'auth de bout en
/// bout.
class TableauDeBordScreen extends ConsumerWidget {
  const TableauDeBordScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authProvider).currentUser;

    return ListView(
      padding: const EdgeInsets.only(bottom: 24),
      children: [
        const AppPageHeader(titre: 'Mon tableau de bord', sousTitre: 'Aperçu de votre carrière, vos ressources et vos demandes en cours.'),
        AppPanel(
          title: 'Bienvenue',
          icon: Icons.waving_hand_outlined,
          child: Text(user != null ? '${user.prenom} ${user.nom}' : '—'),
        ),
      ],
    );
  }
}
