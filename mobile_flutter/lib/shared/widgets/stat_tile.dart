import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

/// Équivalent de shared/stat-tile/stat-tile.component.ts — tuile KPI (icône
/// dans un badge coloré, grande valeur, libellé), optionnellement cliquable
/// via `path`.
class StatTile extends StatelessWidget {
  const StatTile({
    required this.icon,
    required this.value,
    required this.label,
    this.color = Colors.teal,
    this.path,
    super.key,
  });

  final IconData icon;
  final Object value;
  final String label;
  final Color color;
  final String? path;

  @override
  Widget build(BuildContext context) {
    final content = Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: color.withOpacity(0.12), borderRadius: BorderRadius.circular(10)),
              child: Icon(icon, color: color),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('$value', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
                  Text(label, style: Theme.of(context).textTheme.bodySmall, overflow: TextOverflow.ellipsis),
                ],
              ),
            ),
          ],
        ),
      ),
    );

    if (path == null) {
      return content;
    }
    return InkWell(borderRadius: BorderRadius.circular(12), onTap: () => context.push(path!), child: content);
  }
}
