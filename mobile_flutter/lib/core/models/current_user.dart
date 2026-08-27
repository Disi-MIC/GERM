import 'package:freezed_annotation/freezed_annotation.dart';

part 'current_user.freezed.dart';
part 'current_user.g.dart';

/// Identité de session — voir GET /api/me côté Symfony et
/// core/models/user.model.ts (CurrentUser) côté Angular. `roles` est la
/// liste brute renvoyée par le serveur ; la hiérarchie de rôles Symfony
/// n'est PAS recalculée ici, exactement comme côté Angular — chaque route
/// doit lister explicitement tous les rôles qui y donnent accès (voir
/// core/router/app_router.dart).
@freezed
class CurrentUser with _$CurrentUser {
  const factory CurrentUser({
    required int id,
    required String email,
    required String nom,
    required String prenom,
    required List<String> roles,
    int? serviceResponsableId,
    int? directionDirigeeId,
  }) = _CurrentUser;

  factory CurrentUser.fromJson(Map<String, dynamic> json) => _$CurrentUserFromJson(json);
}

extension CurrentUserRoles on CurrentUser {
  bool hasRole(String role) => roles.contains(role);
  bool hasAnyRole(List<String> allowed) => allowed.any(roles.contains);
}

/// Exposition minimale d'un compte (sélecteur "délégataire") — voir UserRef côté Angular.
@freezed
class UserRef with _$UserRef {
  const factory UserRef({
    required int id,
    required String email,
    required bool actif,
    required String nomComplet,
  }) = _UserRef;

  factory UserRef.fromJson(Map<String, dynamic> json) => _$UserRefFromJson(json);
}
