// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'direction.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

DirectionModel _$DirectionModelFromJson(Map<String, dynamic> json) {
  return _DirectionModel.fromJson(json);
}

/// @nodoc
mixin _$DirectionModel {
  int? get id => throw _privateConstructorUsedError;
  String get code => throw _privateConstructorUsedError;
  String get nom => throw _privateConstructorUsedError;
  String? get description => throw _privateConstructorUsedError;
  bool get actif => throw _privateConstructorUsedError;
  dynamic get directeur =>
      throw _privateConstructorUsedError; // PersonnelRef embarqué, IRI string, ou null
  String? get directeurNom => throw _privateConstructorUsedError;
  String? get noteServiceNumero => throw _privateConstructorUsedError;
  String? get noteServiceDate => throw _privateConstructorUsedError;
  bool? get hasNoteServiceFichier => throw _privateConstructorUsedError;
  String? get noteServiceNomOriginal => throw _privateConstructorUsedError;

  /// Serializes this DirectionModel to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of DirectionModel
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $DirectionModelCopyWith<DirectionModel> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $DirectionModelCopyWith<$Res> {
  factory $DirectionModelCopyWith(
          DirectionModel value, $Res Function(DirectionModel) then) =
      _$DirectionModelCopyWithImpl<$Res, DirectionModel>;
  @useResult
  $Res call(
      {int? id,
      String code,
      String nom,
      String? description,
      bool actif,
      dynamic directeur,
      String? directeurNom,
      String? noteServiceNumero,
      String? noteServiceDate,
      bool? hasNoteServiceFichier,
      String? noteServiceNomOriginal});
}

/// @nodoc
class _$DirectionModelCopyWithImpl<$Res, $Val extends DirectionModel>
    implements $DirectionModelCopyWith<$Res> {
  _$DirectionModelCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of DirectionModel
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = freezed,
    Object? code = null,
    Object? nom = null,
    Object? description = freezed,
    Object? actif = null,
    Object? directeur = freezed,
    Object? directeurNom = freezed,
    Object? noteServiceNumero = freezed,
    Object? noteServiceDate = freezed,
    Object? hasNoteServiceFichier = freezed,
    Object? noteServiceNomOriginal = freezed,
  }) {
    return _then(_value.copyWith(
      id: freezed == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int?,
      code: null == code
          ? _value.code
          : code // ignore: cast_nullable_to_non_nullable
              as String,
      nom: null == nom
          ? _value.nom
          : nom // ignore: cast_nullable_to_non_nullable
              as String,
      description: freezed == description
          ? _value.description
          : description // ignore: cast_nullable_to_non_nullable
              as String?,
      actif: null == actif
          ? _value.actif
          : actif // ignore: cast_nullable_to_non_nullable
              as bool,
      directeur: freezed == directeur
          ? _value.directeur
          : directeur // ignore: cast_nullable_to_non_nullable
              as dynamic,
      directeurNom: freezed == directeurNom
          ? _value.directeurNom
          : directeurNom // ignore: cast_nullable_to_non_nullable
              as String?,
      noteServiceNumero: freezed == noteServiceNumero
          ? _value.noteServiceNumero
          : noteServiceNumero // ignore: cast_nullable_to_non_nullable
              as String?,
      noteServiceDate: freezed == noteServiceDate
          ? _value.noteServiceDate
          : noteServiceDate // ignore: cast_nullable_to_non_nullable
              as String?,
      hasNoteServiceFichier: freezed == hasNoteServiceFichier
          ? _value.hasNoteServiceFichier
          : hasNoteServiceFichier // ignore: cast_nullable_to_non_nullable
              as bool?,
      noteServiceNomOriginal: freezed == noteServiceNomOriginal
          ? _value.noteServiceNomOriginal
          : noteServiceNomOriginal // ignore: cast_nullable_to_non_nullable
              as String?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$DirectionModelImplCopyWith<$Res>
    implements $DirectionModelCopyWith<$Res> {
  factory _$$DirectionModelImplCopyWith(_$DirectionModelImpl value,
          $Res Function(_$DirectionModelImpl) then) =
      __$$DirectionModelImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {int? id,
      String code,
      String nom,
      String? description,
      bool actif,
      dynamic directeur,
      String? directeurNom,
      String? noteServiceNumero,
      String? noteServiceDate,
      bool? hasNoteServiceFichier,
      String? noteServiceNomOriginal});
}

/// @nodoc
class __$$DirectionModelImplCopyWithImpl<$Res>
    extends _$DirectionModelCopyWithImpl<$Res, _$DirectionModelImpl>
    implements _$$DirectionModelImplCopyWith<$Res> {
  __$$DirectionModelImplCopyWithImpl(
      _$DirectionModelImpl _value, $Res Function(_$DirectionModelImpl) _then)
      : super(_value, _then);

  /// Create a copy of DirectionModel
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = freezed,
    Object? code = null,
    Object? nom = null,
    Object? description = freezed,
    Object? actif = null,
    Object? directeur = freezed,
    Object? directeurNom = freezed,
    Object? noteServiceNumero = freezed,
    Object? noteServiceDate = freezed,
    Object? hasNoteServiceFichier = freezed,
    Object? noteServiceNomOriginal = freezed,
  }) {
    return _then(_$DirectionModelImpl(
      id: freezed == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int?,
      code: null == code
          ? _value.code
          : code // ignore: cast_nullable_to_non_nullable
              as String,
      nom: null == nom
          ? _value.nom
          : nom // ignore: cast_nullable_to_non_nullable
              as String,
      description: freezed == description
          ? _value.description
          : description // ignore: cast_nullable_to_non_nullable
              as String?,
      actif: null == actif
          ? _value.actif
          : actif // ignore: cast_nullable_to_non_nullable
              as bool,
      directeur: freezed == directeur
          ? _value.directeur
          : directeur // ignore: cast_nullable_to_non_nullable
              as dynamic,
      directeurNom: freezed == directeurNom
          ? _value.directeurNom
          : directeurNom // ignore: cast_nullable_to_non_nullable
              as String?,
      noteServiceNumero: freezed == noteServiceNumero
          ? _value.noteServiceNumero
          : noteServiceNumero // ignore: cast_nullable_to_non_nullable
              as String?,
      noteServiceDate: freezed == noteServiceDate
          ? _value.noteServiceDate
          : noteServiceDate // ignore: cast_nullable_to_non_nullable
              as String?,
      hasNoteServiceFichier: freezed == hasNoteServiceFichier
          ? _value.hasNoteServiceFichier
          : hasNoteServiceFichier // ignore: cast_nullable_to_non_nullable
              as bool?,
      noteServiceNomOriginal: freezed == noteServiceNomOriginal
          ? _value.noteServiceNomOriginal
          : noteServiceNomOriginal // ignore: cast_nullable_to_non_nullable
              as String?,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$DirectionModelImpl implements _DirectionModel {
  const _$DirectionModelImpl(
      {this.id,
      required this.code,
      required this.nom,
      this.description,
      required this.actif,
      this.directeur,
      this.directeurNom,
      this.noteServiceNumero,
      this.noteServiceDate,
      this.hasNoteServiceFichier,
      this.noteServiceNomOriginal});

  factory _$DirectionModelImpl.fromJson(Map<String, dynamic> json) =>
      _$$DirectionModelImplFromJson(json);

  @override
  final int? id;
  @override
  final String code;
  @override
  final String nom;
  @override
  final String? description;
  @override
  final bool actif;
  @override
  final dynamic directeur;
// PersonnelRef embarqué, IRI string, ou null
  @override
  final String? directeurNom;
  @override
  final String? noteServiceNumero;
  @override
  final String? noteServiceDate;
  @override
  final bool? hasNoteServiceFichier;
  @override
  final String? noteServiceNomOriginal;

  @override
  String toString() {
    return 'DirectionModel(id: $id, code: $code, nom: $nom, description: $description, actif: $actif, directeur: $directeur, directeurNom: $directeurNom, noteServiceNumero: $noteServiceNumero, noteServiceDate: $noteServiceDate, hasNoteServiceFichier: $hasNoteServiceFichier, noteServiceNomOriginal: $noteServiceNomOriginal)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$DirectionModelImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.code, code) || other.code == code) &&
            (identical(other.nom, nom) || other.nom == nom) &&
            (identical(other.description, description) ||
                other.description == description) &&
            (identical(other.actif, actif) || other.actif == actif) &&
            const DeepCollectionEquality().equals(other.directeur, directeur) &&
            (identical(other.directeurNom, directeurNom) ||
                other.directeurNom == directeurNom) &&
            (identical(other.noteServiceNumero, noteServiceNumero) ||
                other.noteServiceNumero == noteServiceNumero) &&
            (identical(other.noteServiceDate, noteServiceDate) ||
                other.noteServiceDate == noteServiceDate) &&
            (identical(other.hasNoteServiceFichier, hasNoteServiceFichier) ||
                other.hasNoteServiceFichier == hasNoteServiceFichier) &&
            (identical(other.noteServiceNomOriginal, noteServiceNomOriginal) ||
                other.noteServiceNomOriginal == noteServiceNomOriginal));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(
      runtimeType,
      id,
      code,
      nom,
      description,
      actif,
      const DeepCollectionEquality().hash(directeur),
      directeurNom,
      noteServiceNumero,
      noteServiceDate,
      hasNoteServiceFichier,
      noteServiceNomOriginal);

  /// Create a copy of DirectionModel
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$DirectionModelImplCopyWith<_$DirectionModelImpl> get copyWith =>
      __$$DirectionModelImplCopyWithImpl<_$DirectionModelImpl>(
          this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$DirectionModelImplToJson(
      this,
    );
  }
}

abstract class _DirectionModel implements DirectionModel {
  const factory _DirectionModel(
      {final int? id,
      required final String code,
      required final String nom,
      final String? description,
      required final bool actif,
      final dynamic directeur,
      final String? directeurNom,
      final String? noteServiceNumero,
      final String? noteServiceDate,
      final bool? hasNoteServiceFichier,
      final String? noteServiceNomOriginal}) = _$DirectionModelImpl;

  factory _DirectionModel.fromJson(Map<String, dynamic> json) =
      _$DirectionModelImpl.fromJson;

  @override
  int? get id;
  @override
  String get code;
  @override
  String get nom;
  @override
  String? get description;
  @override
  bool get actif;
  @override
  dynamic get directeur; // PersonnelRef embarqué, IRI string, ou null
  @override
  String? get directeurNom;
  @override
  String? get noteServiceNumero;
  @override
  String? get noteServiceDate;
  @override
  bool? get hasNoteServiceFichier;
  @override
  String? get noteServiceNomOriginal;

  /// Create a copy of DirectionModel
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$DirectionModelImplCopyWith<_$DirectionModelImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
