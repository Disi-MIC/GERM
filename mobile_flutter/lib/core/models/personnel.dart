import 'package:freezed_annotation/freezed_annotation.dart';

part 'personnel.freezed.dart';
part 'personnel.g.dart';

/// Référence légère à un agent (listes déroulantes, liens) — voir
/// personnel-ref.model.ts côté Angular.
@freezed
class PersonnelRef with _$PersonnelRef {
  const factory PersonnelRef({
    required int id,
    required String nomComplet,
  }) = _PersonnelRef;

  factory PersonnelRef.fromJson(Map<String, dynamic> json) => _$PersonnelRefFromJson(json);
}

/// API Platform renvoie soit un objet embarqué, soit une IRI ("/api/services/5")
/// selon le endpoint — ce wrapper reflète ça au lieu de forcer un objet
/// toujours présent. Voir ServiceRef | string côté Angular (union type).
@freezed
class ServiceRef with _$ServiceRef {
  const factory ServiceRef({
    required int id,
    required String code,
    required String nom,
    String? responsableNom,
  }) = _ServiceRef;

  factory ServiceRef.fromJson(Map<String, dynamic> json) => _$ServiceRefFromJson(json);
}

/// Valeur d'une liste de paramétrage RH Admin (type de contrat, motif...) —
/// voir ListeValeurRef côté Angular.
@freezed
class ListeValeurRef with _$ListeValeurRef {
  const factory ListeValeurRef({
    required int id,
    required String categorie,
    required String code,
    required String libelle,
  }) = _ListeValeurRef;

  factory ListeValeurRef.fromJson(Map<String, dynamic> json) => _$ListeValeurRefFromJson(json);
}

/// Fiche agent — voir Personnel côté Angular (core/models/personnel.model.ts)
/// et l'entité Personnel côté Symfony.
@freezed
class Personnel with _$Personnel {
  const factory Personnel({
    int? id,
    String? matricule,
    required String nom,
    required String prenom,
    /// 'M' ou 'F' — voir App\Entity\Enum\Sexe côté serveur.
    required String sexe,
    String? dateNaissance,
    required String fonction,
    String? grade,
    dynamic typeContrat, // ListeValeurRef embarqué ou IRI string
    String? dateEmbauche,
    required String statut,
    String? telephone,
    String? email,
    String? adresse,
    dynamic service, // ServiceRef embarqué ou IRI string
    String? observations,
    String? createdAt,
    String? updatedAt,
    String? nomComplet,
    bool? hasPhoto,
  }) = _Personnel;

  factory Personnel.fromJson(Map<String, dynamic> json) => _$PersonnelFromJson(json);
}
