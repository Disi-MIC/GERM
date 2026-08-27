import 'package:flutter/material.dart';

import '../../shared/widgets/app_page_header.dart';

/// Écran temporaire pour toute destination pas encore construite — garde la
/// navigation (onglets, feuille "Plus") entièrement fonctionnelle dès la
/// phase 1, à remplacer un par un par le vrai écran au fil des phases
/// suivantes sans jamais retoucher le router ni le shell.
class MonEspacePlaceholderScreen extends StatelessWidget {
  const MonEspacePlaceholderScreen({required this.titre, super.key});

  final String titre;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        AppPageHeader(titre: titre),
        Expanded(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.construction_outlined, size: 40, color: Colors.grey.shade400),
                  const SizedBox(height: 12),
                  Text('« $titre » arrive dans une prochaine phase.', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600)),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}
