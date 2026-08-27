// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'service.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

DirectionRef _$DirectionRefFromJson(Map<String, dynamic> json) {
  return _DirectionRef.fromJson(json);
}

/// @nodoc
mixin _$DirectionRef {
  int get id => throw _privateConstructorUsedError;
  String get nom => throw _privateConstructorUsedError;

  /// Serializes this DirectionRef to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of DirectionRef
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $DirectionRefCopyWith<DirectionRef> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $DirectionRefCopyWith<$Res> {
  factory $DirectionRefCopyWith(
          DirectionRef value, $Res Function(DirectionRef) then) =
      _$DirectionRefCopyWithImpl<$Res, DirectionRef>;
  @useResult
  $Res call({int id, String nom});
}

/// @nodoc
class _$DirectionRefCopyWithImpl<$Res, $Val extends DirectionRef>
    implements $DirectionRefCopyWith<$Res> {
  _$DirectionRefCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of DirectionRef
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? nom = null,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      nom: null == nom
          ? _value.nom
          : nom // ignore: cast_nullable_to_non_nullable
              as String,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$DirectionRefImplCopyWith<$Res>
    implements $DirectionRefCopyWith<$Res> {
  factory _$$DirectionRefImplCopyWith(
          _$DirectionRefImpl value, $Res Function(_$DirectionRefImpl) then) =
      __$$DirectionRefImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({int id, String nom});
}

/// @nodoc
class __$$DirectionRefImplCopyWithImpl<$Res>
    extends _$DirectionRefCopyWithImpl<$Res, _$DirectionRefImpl>
    implements _$$DirectionRefImplCopyWith<$Res> {
  __$$DirectionRefImplCopyWithImpl(
      _$DirectionRefImpl _value, $Res Function(_$DirectionRefImpl) _then)
      : super(_value, _then);

  /// Create a copy of DirectionRef
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? nom = null,
  }) {
    return _then(_$DirectionRefImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      nom: null == nom
          ? _value.nom
          : nom // ignore: cast_nullable_to_non_nullable
              as String,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$DirectionRefImpl implements _DirectionRef {
  const _$DirectionRefImpl({required this.id, required this.nom});

  factory _$DirectionRefImpl.fromJson(Map<String, dynamic> json) =>
      _$$DirectionRefImplFromJson(json);

  @override
  final int id;
  @override
  final String nom;

  @override
  String toString() {
    return 'DirectionRef(id: $id, nom: $nom)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$DirectionRefImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.nom, nom) || other.nom == nom));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(runtimeType, id, nom);

  /// Create a copy of DirectionRef
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$DirectionRefImplCopyWith<_$DirectionRefImpl> get copyWith =>
      __$$DirectionRefImplCopyWithImpl<_$DirectionRefImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$DirectionRefImplToJson(
      this,
    );
  }
}

abstract class _DirectionRef implements DirectionRef {
  const factory _DirectionRef(
      {required final int id, required final String nom}) = _$DirectionRefImpl;

  factory _DirectionRef.fromJson(Map<String, dynamic> json) =
      _$DirectionRefImpl.fromJson;

  @override
  int get id;
  @override
  String get nom;

  /// Create a copy of DirectionRef
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$DirectionRefImplCopyWith<_$DirectionRefImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

ServiceModel _$ServiceModelFromJson(Map<String, dynamic> json) {
  return _ServiceModel.fromJson(json);
}

/// @nodoc
mixin _$ServiceModel {
  int? get id => throw _privateConstructorUsedError;
  String get code => throw _privateConstructorUsedError;
  String get nom => throw _privateConstructorUsedError;
  String? get description => throw _privateConstructorUsedError;
  bool get actif => throw _privateConstructorUsedError;
  dynamic get direction =>
      throw _privateConstructorUsedError; // DirectionRef embarqué, IRI string, ou null
  dynamic get responsable =>
      throw _privateConstructorUsedError; // PersonnelRef embarqué, IRI string, ou null
  String? get responsableNom => throw _privateConstructorUsedError;
  String? get noteServiceNumero => throw _privateConstructorUsedError;
  String? get noteServiceDate => throw _privateConstructorUsedError;
  bool? get hasNoteServiceFichier => throw _privateConstructorUsedError;
  String? get noteServiceNomOriginal => throw _privateConstructorUsedError;

  /// Serializes this ServiceModel to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of ServiceModel
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $ServiceModelCopyWith<ServiceModel> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $ServiceModelCopyWith<$Res> {
  factory $ServiceModelCopyWith(
          ServiceModel value, $Res Function(ServiceModel) then) =
      _$ServiceModelCopyWithImpl<$Res, ServiceModel>;
  @useResult
  $Res call(
      {int? id,
      String code,
      String nom,
      String? description,
      bool actif,
      dynamic direction,
      dynamic responsable,
      String? responsableNom,
      String? noteServiceNumero,
      String? noteServiceDate,
      bool? hasNoteServiceFichier,
      String? noteServiceNomOriginal});
}

/// @nodoc
class _$ServiceModelCopyWithImpl<$Res, $Val extends ServiceModel>
    implements $ServiceModelCopyWith<$Res> {
  _$ServiceModelCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of ServiceModel
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = freezed,
    Object? code = null,
    Object? nom = null,
    Object? description = freezed,
    Object? actif = null,
    Object? direction = freezed,
    Object? responsable = freezed,
    Object? responsableNom = freezed,
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
      direction: freezed == direction
          ? _value.direction
          : direction // ignore: cast_nullable_to_non_nullable
              as dynamic,
      responsable: freezed == responsable
          ? _value.responsable
          : responsable // ignore: cast_nullable_to_non_nullable
              as dynamic,
      responsableNom: freezed == responsableNom
          ? _value.responsableNom
          : responsableNom // ignore: cast_nullable_to_non_nullable
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
abstract class _$$ServiceModelImplCopyWith<$Res>
    implements $ServiceModelCopyWith<$Res> {
  factory _$$ServiceModelImplCopyWith(
          _$ServiceModelImpl value, $Res Function(_$ServiceModelImpl) then) =
      __$$ServiceModelImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {int? id,
      String code,
      String nom,
      String? description,
      bool actif,
      dynamic direction,
      dynamic responsable,
      String? responsableNom,
      String? noteServiceNumero,
      String? noteServiceDate,
      bool? hasNoteServiceFichier,
      String? noteServiceNomOriginal});
}

/// @nodoc
class __$$ServiceModelImplCopyWithImpl<$Res>
    extends _$ServiceModelCopyWithImpl<$Res, _$ServiceModelImpl>
    implements _$$ServiceModelImplCopyWith<$Res> {
  __$$ServiceModelImplCopyWithImpl(
      _$ServiceModelImpl _value, $Res Function(_$ServiceModelImpl) _then)
      : super(_value, _then);

  /// Create a copy of ServiceModel
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = freezed,
    Object? code = null,
    Object? nom = null,
    Object? description = freezed,
    Object? actif = null,
    Object? direction = freezed,
    Object? responsable = freezed,
    Object? responsableNom = freezed,
    Object? noteServiceNumero = freezed,
    Object? noteServiceDate = freezed,
    Object? hasNoteServiceFichier = freezed,
    Object? noteServiceNomOriginal = freezed,
  }) {
    return _then(_$ServiceModelImpl(
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
      direction: freezed == direction
          ? _value.direction
          : direction // ignore: cast_nullable_to_non_nullable
              as dynamic,
      responsable: freezed == responsable
          ? _value.responsable
          : responsable // ignore: cast_nullable_to_non_nullable
              as dynamic,
      responsableNom: freezed == responsableNom
          ? _value.responsableNom
          : responsableNom // ignore: cast_nullable_to_non_nullable
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
class _$ServiceModelImpl implements _ServiceModel {
  const _$ServiceModelImpl(
      {this.id,
      required this.code,
      required this.nom,
      this.description,
      required this.actif,
      this.direction,
      this.responsable,
      this.responsableNom,
      this.noteServiceNumero,
      this.noteServiceDate,
      this.hasNoteServiceFichier,
      this.noteServiceNomOriginal});

  factory _$ServiceModelImpl.fromJson(Map<String, dynamic> json) =>
      _$$ServiceModelImplFromJson(json);

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
  final dynamic direction;
// DirectionRef embarqué, IRI string, ou null
  @override
  final dynamic responsable;
// PersonnelRef embarqué, IRI string, ou null
  @override
  final String? responsableNom;
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
    return 'ServiceModel(id: $id, code: $code, nom: $nom, description: $description, actif: $actif, direction: $direction, responsable: $responsable, responsableNom: $responsableNom, noteServiceNumero: $noteServiceNumero, noteServiceDate: $noteServiceDate, hasNoteServiceFichier: $hasNoteServiceFichier, noteServiceNomOriginal: $noteServiceNomOriginal)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$ServiceModelImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.code, code) || other.code == code) &&
            (identical(other.nom, nom) || other.nom == nom) &&
            (identical(other.description, description) ||
                other.description == description) &&
            (identical(other.actif, actif) || other.actif == actif) &&
            const DeepCollectionEquality().equals(other.direction, direction) &&
            const DeepCollectionEquality()
                .equals(other.responsable, responsable) &&
            (identical(other.responsableNom, responsableNom) ||
                other.responsableNom == responsableNom) &&
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
      const DeepCollectionEquality().hash(direction),
      const DeepCollectionEquality().hash(responsable),
      responsableNom,
      noteServiceNumero,
      noteServiceDate,
      hasNoteServiceFichier,
      noteServiceNomOriginal);

  /// Create a copy of ServiceModel
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$ServiceModelImplCopyWith<_$ServiceModelImpl> get copyWith =>
      __$$ServiceModelImplCopyWithImpl<_$ServiceModelImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$ServiceModelImplToJson(
      this,
    );
  }
}

abstract class _ServiceModel implements ServiceModel {
  const factory _ServiceModel(
      {final int? id,
      required final String code,
      required final String nom,
      final String? description,
      required final bool actif,
      final dynamic direction,
      final dynamic responsable,
      final String? responsableNom,
      final String? noteServiceNumero,
      final String? noteServiceDate,
      final bool? hasNoteServiceFichier,
      final String? noteServiceNomOriginal}) = _$ServiceModelImpl;

  factory _ServiceModel.fromJson(Map<String, dynamic> json) =
      _$ServiceModelImpl.fromJson;

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
  dynamic get direction; // DirectionRef embarqué, IRI string, ou null
  @override
  dynamic get responsable; // PersonnelRef embarqué, IRI string, ou null
  @override
  String? get responsableNom;
  @override
  String? get noteServiceNumero;
  @override
  String? get noteServiceDate;
  @override
  bool? get hasNoteServiceFichier;
  @override
  String? get noteServiceNomOriginal;

  /// Create a copy of ServiceModel
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$ServiceModelImplCopyWith<_$ServiceModelImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
