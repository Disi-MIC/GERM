import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../models/current_user.dart';
import '../network/api_client.dart';
import '../network/api_client_provider.dart';

/// Équivalent de auth.service.ts côté Angular : pas de jeton stocké côté
/// client, la session vit dans le cookie géré par ApiClient. `restore()` est
/// l'équivalent de fetchMe() appelé une fois au démarrage pour savoir si un
/// cookie de session valide existe déjà (redémarrage à froid de l'app).
class AuthState {
  const AuthState({
    required this.initialized,
    required this.currentUser,
    required this.loading,
    this.error,
  });

  final bool initialized;
  final CurrentUser? currentUser;
  final bool loading;
  final String? error;

  bool get isLoggedIn => currentUser != null;

  AuthState copyWith({
    bool? initialized,
    CurrentUser? currentUser,
    bool clearCurrentUser = false,
    bool? loading,
    String? error,
    bool clearError = false,
  }) {
    return AuthState(
      initialized: initialized ?? this.initialized,
      currentUser: clearCurrentUser ? null : (currentUser ?? this.currentUser),
      loading: loading ?? this.loading,
      error: clearError ? null : (error ?? this.error),
    );
  }

  static const initial = AuthState(initialized: false, currentUser: null, loading: false);
}

class AuthNotifier extends Notifier<AuthState> {
  late ApiClient _api;

  @override
  AuthState build() {
    _api = ref.read(apiClientProvider);
    return AuthState.initial;
  }

  /// Appelé une fois au démarrage de l'app (voir main.dart) — restaure la
  /// session si le cookie persistant (PersistCookieJar) est encore valide.
  Future<void> restore() async {
    try {
      final response = await _api.dio.get('/me');
      if (response.statusCode == 200) {
        state = state.copyWith(
          initialized: true,
          currentUser: CurrentUser.fromJson(response.data as Map<String, dynamic>),
        );
        return;
      }
    } catch (_) {
      // Pas de session valide (hors ligne, cookie expiré...) — reste
      // déconnecté, comportement identique à un 401 de /api/me.
    }
    state = state.copyWith(initialized: true, clearCurrentUser: true);
  }

  Future<bool> login(String email, String password) async {
    state = state.copyWith(loading: true, clearError: true);
    try {
      final response = await _api.dio.post(
        '/login',
        data: {'email': email, 'password': password},
      );
      if (response.statusCode == 200) {
        state = state.copyWith(
          loading: false,
          currentUser: CurrentUser.fromJson(response.data as Map<String, dynamic>),
        );
        return true;
      }
      // status 0 (jamais atteint le serveur) se traduit par une DioException
      // catchée plus bas ; ici on a bien une réponse HTTP, donc identifiants
      // incorrects — même distinction que login.component.ts côté Angular.
      state = state.copyWith(loading: false, error: 'Email ou mot de passe incorrect.');
      return false;
    } catch (_) {
      state = state.copyWith(
        loading: false,
        error: "Impossible de contacter le serveur. Vérifiez votre connexion et l'adresse du serveur.",
      );
      return false;
    }
  }

  Future<void> logout() async {
    try {
      await _api.dio.post('/logout');
    } catch (_) {
      // Toujours nettoyer l'état local même si l'appel serveur échoue — même
      // logique que logout() côté Angular.
    }
    await _api.clearSession();
    state = state.copyWith(clearCurrentUser: true);
  }
}

final authProvider = NotifierProvider<AuthNotifier, AuthState>(AuthNotifier.new);
