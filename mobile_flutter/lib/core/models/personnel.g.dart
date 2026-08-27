// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'personnel.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$PersonnelRefImpl _$$PersonnelRefImplFromJson(Map<String, dynamic> json) =>
    _$PersonnelRefImpl(
      id: (json['id'] as num).toInt(),
      nomComplet: json['nomComplet'] as String,
    );

Map<String, dynamic> _$$PersonnelRefImplToJson(_$PersonnelRefImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'nomComplet': instance.nomComplet,
    };

_$ServiceRefImpl _$$ServiceRefImplFromJson(Map<String, dynamic> json) =>
    _$ServiceRefImpl(
      id: (json['id'] as num).toInt(),
      code: json['code'] as String,
      nom: json['nom'] as String,
      responsableNom: json['responsableNom'] as String?,
    );

Map<String, dynamic> _$$ServiceRefImplToJson(_$ServiceRefImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'code': instance.code,
      'nom': instance.nom,
      'responsableNom': instance.responsableNom,
    };

_$ListeValeurRefImpl _$$ListeValeurRefImplFromJson(Map<String, dynamic> json) =>
    _$ListeValeurRefImpl(
      id: (json['id'] as num).toInt(),
      categorie: json['categorie'] as String,
      code: json['code'] as String,
      libelle: json['libelle'] as String,
    );

Map<String, dynamic> _$$ListeValeurRefImplToJson(
        _$ListeValeurRefImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'categorie': instance.categorie,
      'code': instance.code,
      'libelle': instance.libelle,
    };

_$PersonnelImpl _$$PersonnelImplFromJson(Map<String, dynamic> json) =>
    _$PersonnelImpl(
      id: (json['id'] as num?)?.toInt(),
      matricule: json['matricule'] as String?,
      nom: json['nom'] as String,
      prenom: json['prenom'] as String,
      sexe: json['sexe'] as String,
      dateNaissance: json['dateNaissance'] as String?,
      fonction: json['fonction'] as String,
      grade: json['grade'] as String?,
      typeContrat: json['typeContrat'],
      dateEmbauche: json['dateEmbauche'] as String?,
      statut: json['statut'] as String,
      telephone: json['telephone'] as String?,
      email: json['email'] as String?,
      adresse: json['adresse'] as String?,
      service: json['service'],
      observations: json['observations'] as String?,
      createdAt: json['createdAt'] as String?,
      updatedAt: json['updatedAt'] as String?,
      nomComplet: json['nomComplet'] as String?,
      hasPhoto: json['hasPhoto'] as bool?,
    );

Map<String, dynamic> _$$PersonnelImplToJson(_$PersonnelImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'matricule': instance.matricule,
      'nom': instance.nom,
      'prenom': instance.prenom,
      'sexe': instance.sexe,
      'dateNaissance': instance.dateNaissance,
      'fonction': instance.fonction,
      'grade': instance.grade,
      'typeContrat': instance.typeContrat,
      'dateEmbauche': instance.dateEmbauche,
      'statut': instance.statut,
      'telephone': instance.telephone,
      'email': instance.email,
      'adresse': instance.adresse,
      'service': instance.service,
      'observations': instance.observations,
      'createdAt': instance.createdAt,
      'updatedAt': instance.updatedAt,
      'nomComplet': instance.nomComplet,
      'hasPhoto': instance.hasPhoto,
    };
