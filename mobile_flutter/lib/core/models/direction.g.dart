// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'direction.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$DirectionModelImpl _$$DirectionModelImplFromJson(Map<String, dynamic> json) =>
    _$DirectionModelImpl(
      id: (json['id'] as num?)?.toInt(),
      code: json['code'] as String,
      nom: json['nom'] as String,
      description: json['description'] as String?,
      actif: json['actif'] as bool,
      directeur: json['directeur'],
      directeurNom: json['directeurNom'] as String?,
      noteServiceNumero: json['noteServiceNumero'] as String?,
      noteServiceDate: json['noteServiceDate'] as String?,
      hasNoteServiceFichier: json['hasNoteServiceFichier'] as bool?,
      noteServiceNomOriginal: json['noteServiceNomOriginal'] as String?,
    );

Map<String, dynamic> _$$DirectionModelImplToJson(
        _$DirectionModelImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'code': instance.code,
      'nom': instance.nom,
      'description': instance.description,
      'actif': instance.actif,
      'directeur': instance.directeur,
      'directeurNom': instance.directeurNom,
      'noteServiceNumero': instance.noteServiceNumero,
      'noteServiceDate': instance.noteServiceDate,
      'hasNoteServiceFichier': instance.hasNoteServiceFichier,
      'noteServiceNomOriginal': instance.noteServiceNomOriginal,
    };
