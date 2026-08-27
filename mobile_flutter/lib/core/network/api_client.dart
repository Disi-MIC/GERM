import 'package:cookie_jar/cookie_jar.dart';
import 'package:dio/dio.dart';
import 'package:dio_cookie_manager/dio_cookie_manager.dart';
import 'package:path_provider/path_provider.dart';

import 'app_env.dart';

/// Client HTTP partagé par tout le reste de l'app — authentification par
/// cookie de session Symfony (pas de JWT, voir core/auth/auth_state.dart),
/// équivalent de credentials.interceptor.ts + withCredentials côté Angular.
///
/// PersistCookieJar fait persister le cookie de session sur disque entre
/// deux lancements de l'app : au démarrage, un simple appel à /api/me avec
/// ce cookie déjà là suffit à restaurer la session sans repasser par le
/// formulaire de connexion (voir AuthState.restoreSession()).
class ApiClient {
  ApiClient._(this.dio, this._cookieJar);

  final Dio dio;
  final PersistCookieJar _cookieJar;

  static Future<ApiClient> create() async {
    final dio = Dio(
      BaseOptions(
        baseUrl: AppEnv.apiBase,
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 20),
        // Les erreurs HTTP (401/403/422/500...) sont traitées explicitement
        // par chaque appelant (voir failure handling dans les repositories) —
        // jamais transformées en exception dio pour un simple code d'erreur
        // métier, seulement pour un vrai échec réseau.
        validateStatus: (status) => status != null && status < 500,
      ),
    );

    final supportDir = await getApplicationSupportDirectory();
    final cookieJar = PersistCookieJar(
      storage: FileStorage('${supportDir.path}/.cookies/'),
    );
    dio.interceptors.add(CookieManager(cookieJar));

    return ApiClient._(dio, cookieJar);
  }

  /// Vide le cookie de session — utilisé à la déconnexion, en plus de
  /// l'appel serveur POST /api/logout (voir AuthState.logout()).
  Future<void> clearSession() => _cookieJar.deleteAll();
}
