// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'service.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$DirectionRefImpl _$$DirectionRefImplFromJson(Map<String, dynamic> json) =>
    _$DirectionRefImpl(
      id: (json['id'] as num).toInt(),
      nom: json['nom'] as String,
    );

Map<String, dynamic> _$$DirectionRefImplToJson(_$DirectionRefImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'nom': instance.nom,
    };

_$ServiceModelImpl _$$ServiceModelImplFromJson(Map<String, dynamic> json) =>
    _$ServiceModelImpl(
      id: (json['id'] as num?)?.toInt(),
      code: json['code'] as String,
      nom: json['nom'] as String,
      description: json['description'] as String?,
      actif: json['actif'] as bool,
      direction: json['direction'],
      responsable: json['responsable'],
      responsableNom: json['responsableNom'] as String?,
      noteServiceNumero: json['noteServiceNumero'] as String?,
      noteServiceDate: json['noteServiceDate'] as String?,
      hasNoteServiceFichier: json['hasNoteServiceFichier'] as bool?,
      noteServiceNomOriginal: json['noteServiceNomOriginal'] as String?,
    );

Map<String, dynamic> _$$ServiceModelImplToJson(_$ServiceModelImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'code': instance.code,
      'nom': instance.nom,
      'description': instance.description,
      'actif': instance.actif,
      'direction': instance.direction,
      'responsable': instance.responsable,
      'responsableNom': instance.responsableNom,
      'noteServiceNumero': instance.noteServiceNumero,
      'noteServiceDate': instance.noteServiceDate,
      'hasNoteServiceFichier': instance.hasNoteServiceFichier,
      'noteServiceNomOriginal': instance.noteServiceNomOriginal,
    };
