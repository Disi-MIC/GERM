import 'package:freezed_annotation/freezed_annotation.dart';

part 'direction.freezed.dart';
part 'direction.g.dart';

/// Direction ministérielle — voir Direction côté Angular.
@freezed
class DirectionModel with _$DirectionModel {
  const factory DirectionModel({
    int? id,
    required String code,
    required String nom,
    String? description,
    required bool actif,
    dynamic directeur, // PersonnelRef embarqué, IRI string, ou null
    String? directeurNom,
    String? noteServiceNumero,
    String? noteServiceDate,
    bool? hasNoteServiceFichier,
    String? noteServiceNomOriginal,
  }) = _DirectionModel;

  factory DirectionModel.fromJson(Map<String, dynamic> json) => _$DirectionModelFromJson(json);
}
