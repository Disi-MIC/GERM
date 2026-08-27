import 'package:flutter/material.dart';

/// Équivalent de shared/panel/panel.component.ts — carte avec en-tête
/// (icône + titre + actions optionnelles) et corps ; utilisé partout comme
/// conteneur de section (tableaux de bord, formulaires, listes).
class AppPanel extends StatelessWidget {
  const AppPanel({
    required this.child,
    this.title,
    this.icon,
    this.actions,
    this.padding = const EdgeInsets.all(16),
    super.key,
  });

  final Widget child;
  final String? title;
  final IconData? icon;
  final Widget? actions;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Padding(
        padding: padding,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (title != null) ...[
              Row(
                children: [
                  if (icon != null) ...[
                    Icon(icon, size: 18, color: Theme.of(context).colorScheme.primary),
                    const SizedBox(width: 8),
                  ],
                  Expanded(
                    child: Text(title!, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600)),
                  ),
                  if (actions != null) actions!,
                ],
              ),
              const SizedBox(height: 12),
            ],
            child,
          ],
        ),
      ),
    );
  }
}
