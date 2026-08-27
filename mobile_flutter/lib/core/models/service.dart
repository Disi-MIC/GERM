import 'package:freezed_annotation/freezed_annotation.dart';

part 'service.freezed.dart';
part 'service.g.dart';

@freezed
class DirectionRef with _$DirectionRef {
  const factory DirectionRef({
    required int id,
    required String nom,
  }) = _DirectionRef;

  factory DirectionRef.fromJson(Map<String, dynamic> json) => _$DirectionRefFromJson(json);
}

/// Service (unité organisationnelle) — voir Service côté Angular.
@freezed
class ServiceModel with _$ServiceModel {
  const factory ServiceModel({
    int? id,
    required String code,
    required String nom,
    String? description,
    required bool actif,
    dynamic direction, // DirectionRef embarqué, IRI string, ou null
    dynamic responsable, // PersonnelRef embarqué, IRI string, ou null
    String? responsableNom,
    String? noteServiceNumero,
    String? noteServiceDate,
    bool? hasNoteServiceFichier,
    String? noteServiceNomOriginal,
  }) = _ServiceModel;

  factory ServiceModel.fromJson(Map<String, dynamic> json) => _$ServiceModelFromJson(json);
}
