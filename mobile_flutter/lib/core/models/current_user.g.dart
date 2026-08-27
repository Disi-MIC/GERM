// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'current_user.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$CurrentUserImpl _$$CurrentUserImplFromJson(Map<String, dynamic> json) =>
    _$CurrentUserImpl(
      id: (json['id'] as num).toInt(),
      email: json['email'] as String,
      nom: json['nom'] as String,
      prenom: json['prenom'] as String,
      roles: (json['roles'] as List<dynamic>).map((e) => e as String).toList(),
      serviceResponsableId: (json['serviceResponsableId'] as num?)?.toInt(),
      directionDirigeeId: (json['directionDirigeeId'] as num?)?.toInt(),
    );

Map<String, dynamic> _$$CurrentUserImplToJson(_$CurrentUserImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'email': instance.email,
      'nom': instance.nom,
      'prenom': instance.prenom,
      'roles': instance.roles,
      'serviceResponsableId': instance.serviceResponsableId,
      'directionDirigeeId': instance.directionDirigeeId,
    };

_$UserRefImpl _$$UserRefImplFromJson(Map<String, dynamic> json) =>
    _$UserRefImpl(
      id: (json['id'] as num).toInt(),
      email: json['email'] as String,
      actif: json['actif'] as bool,
      nomComplet: json['nomComplet'] as String,
    );

Map<String, dynamic> _$$UserRefImplToJson(_$UserRefImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'email': instance.email,
      'actif': instance.actif,
      'nomComplet': instance.nomComplet,
    };
