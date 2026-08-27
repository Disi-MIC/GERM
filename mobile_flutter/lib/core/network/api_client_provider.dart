import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'api_client.dart';

/// ApiClient est créé une fois dans main() (ApiClient.create() est async —
/// PersistCookieJar a besoin du chemin du dossier de l'app) puis injecté ici
/// via ProviderScope.overrides, plutôt qu'un FutureProvider : tout le reste
/// de l'état (AuthNotifier en premier) a besoin d'un accès synchrone au
/// client dès sa construction.
final apiClientProvider = Provider<ApiClient>((ref) {
  throw UnimplementedError('apiClientProvider doit être surchargé dans main() après ApiClient.create()');
});
