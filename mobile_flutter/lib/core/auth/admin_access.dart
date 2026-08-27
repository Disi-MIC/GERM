import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'auth_state.dart';

/// Équivalent d'AdminAccessService côté Angular : un déverrouillage par
/// session (pas persisté), remis à zéro à la déconnexion. `demanderAcces()`
/// ouvre AdminAccessModal si pas encore déverrouillé, et attend sa
/// résolution — donc jamais appelé directement par les écrans, toujours via
/// AdminAccessGate dans core/router/app_router.dart.
class AdminAccessNotifier extends Notifier<bool> {
  @override
  bool build() => false;

  void deverrouiller() => state = true;

  void verrouiller() => state = false;
}

final adminAccessProvider = NotifierProvider<AdminAccessNotifier, bool>(AdminAccessNotifier.new);

/// Ré-authentifie par mot de passe (réutilise POST /api/login avec l'email
/// déjà connu — pas d'endpoint dédié, même choix que AdminAccessModalComponent
/// côté Angular) puis déverrouille l'accès admin pour le reste de la session.
///
/// Retourne true si déverrouillé (soit déjà avant l'appel, soit à l'instant).
Future<bool> demanderAccesAdmin(BuildContext context, WidgetRef ref) async {
  if (ref.read(adminAccessProvider)) {
    return true;
  }

  if (!context.mounted) {
    return false;
  }

  final deverrouille = await showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    isDismissible: false,
    enableDrag: false,
    builder: (context) => const _AdminAccessSheet(),
  );

  if (deverrouille == true) {
    ref.read(adminAccessProvider.notifier).deverrouiller();
    return true;
  }
  return false;
}

class _AdminAccessSheet extends ConsumerStatefulWidget {
  const _AdminAccessSheet();

  @override
  ConsumerState<_AdminAccessSheet> createState() => _AdminAccessSheetState();
}

class _AdminAccessSheetState extends ConsumerState<_AdminAccessSheet> {
  final _password = TextEditingController();
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _password.dispose();
    super.dispose();
  }

  Future<void> _confirmer() async {
    final email = ref.read(authProvider).currentUser?.email;
    if (email == null || _password.text.isEmpty) {
      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });
    final ok = await ref.read(authProvider.notifier).login(email, _password.text);
    if (!mounted) {
      return;
    }
    if (ok) {
      Navigator.of(context).pop(true);
    } else {
      setState(() {
        _submitting = false;
        _error = 'Mot de passe incorrect.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        top: 24,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Icon(Icons.shield_outlined, size: 40),
          const SizedBox(height: 12),
          Text('Accès Administration', style: Theme.of(context).textTheme.titleLarge, textAlign: TextAlign.center),
          const SizedBox(height: 8),
          const Text(
            'Merci de confirmer votre mot de passe pour continuer.',
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _password,
            obscureText: true,
            autofocus: true,
            decoration: InputDecoration(
              hintText: 'Mot de passe',
              errorText: _error,
            ),
            onSubmitted: (_) => _confirmer(),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: _submitting ? null : () => Navigator.of(context).pop(false),
                  child: const Text('Annuler'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton(
                  onPressed: _submitting ? null : _confirmer,
                  child: _submitting
                      ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2))
                      : const Text('Confirmer'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
