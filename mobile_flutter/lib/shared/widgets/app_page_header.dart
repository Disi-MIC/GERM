import 'package:flutter/material.dart';

/// Équivalent de shared/page-header/page-header.component.ts : affiche un
/// sous-titre et des actions optionnelles dans le corps de la page — utile
/// pour les boutons d'action ("Nouveau...") qui, en Angular, sont projetés
/// dans le slot de PageHeaderComponent plutôt que dans la barre elle-même.
/// Le titre lui-même est affiché par MobileShell, dérivé de la route (voir
/// features/shell/mobile_shell.dart) plutôt que poussé par cet écran.
class AppPageHeader extends StatelessWidget {
  const AppPageHeader({required this.titre, this.sousTitre, this.actions, super.key});

  final String titre;
  final String? sousTitre;
  final Widget? actions;

  @override
  Widget build(BuildContext context) {
    if (sousTitre == null && actions == null) {
      return const SizedBox.shrink();
    }
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (sousTitre != null)
            Expanded(
              child: Text(sousTitre!, style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: Colors.grey.shade600)),
            ),
          if (actions != null) actions!,
        ],
      ),
    );
  }
}
