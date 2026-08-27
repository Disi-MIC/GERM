// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'personnel.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

PersonnelRef _$PersonnelRefFromJson(Map<String, dynamic> json) {
  return _PersonnelRef.fromJson(json);
}

/// @nodoc
mixin _$PersonnelRef {
  int get id => throw _privateConstructorUsedError;
  String get nomComplet => throw _privateConstructorUsedError;

  /// Serializes this PersonnelRef to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of PersonnelRef
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $PersonnelRefCopyWith<PersonnelRef> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $PersonnelRefCopyWith<$Res> {
  factory $PersonnelRefCopyWith(
          PersonnelRef value, $Res Function(PersonnelRef) then) =
      _$PersonnelRefCopyWithImpl<$Res, PersonnelRef>;
  @useResult
  $Res call({int id, String nomComplet});
}

/// @nodoc
class _$PersonnelRefCopyWithImpl<$Res, $Val extends PersonnelRef>
    implements $PersonnelRefCopyWith<$Res> {
  _$PersonnelRefCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of PersonnelRef
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? nomComplet = null,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      nomComplet: null == nomComplet
          ? _value.nomComplet
          : nomComplet // ignore: cast_nullable_to_non_nullable
              as String,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$PersonnelRefImplCopyWith<$Res>
    implements $PersonnelRefCopyWith<$Res> {
  factory _$$PersonnelRefImplCopyWith(
          _$PersonnelRefImpl value, $Res Function(_$PersonnelRefImpl) then) =
      __$$PersonnelRefImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({int id, String nomComplet});
}

/// @nodoc
class __$$PersonnelRefImplCopyWithImpl<$Res>
    extends _$PersonnelRefCopyWithImpl<$Res, _$PersonnelRefImpl>
    implements _$$PersonnelRefImplCopyWith<$Res> {
  __$$PersonnelRefImplCopyWithImpl(
      _$PersonnelRefImpl _value, $Res Function(_$PersonnelRefImpl) _then)
      : super(_value, _then);

  /// Create a copy of PersonnelRef
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? nomComplet = null,
  }) {
    return _then(_$PersonnelRefImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      nomComplet: null == nomComplet
          ? _value.nomComplet
          : nomComplet // ignore: cast_nullable_to_non_nullable
              as String,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$PersonnelRefImpl implements _PersonnelRef {
  const _$PersonnelRefImpl({required this.id, required this.nomComplet});

  factory _$PersonnelRefImpl.fromJson(Map<String, dynamic> json) =>
      _$$PersonnelRefImplFromJson(json);

  @override
  final int id;
  @override
  final String nomComplet;

  @override
  String toString() {
    return 'PersonnelRef(id: $id, nomComplet: $nomComplet)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$PersonnelRefImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.nomComplet, nomComplet) ||
                other.nomComplet == nomComplet));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(runtimeType, id, nomComplet);

  /// Create a copy of PersonnelRef
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$PersonnelRefImplCopyWith<_$PersonnelRefImpl> get copyWith =>
      __$$PersonnelRefImplCopyWithImpl<_$PersonnelRefImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$PersonnelRefImplToJson(
      this,
    );
  }
}

abstract class _PersonnelRef implements PersonnelRef {
  const factory _PersonnelRef(
      {required final int id,
      required final String nomComplet}) = _$PersonnelRefImpl;

  factory _PersonnelRef.fromJson(Map<String, dynamic> json) =
      _$PersonnelRefImpl.fromJson;

  @override
  int get id;
  @override
  String get nomComplet;

  /// Create a copy of PersonnelRef
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$PersonnelRefImplCopyWith<_$PersonnelRefImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

ServiceRef _$ServiceRefFromJson(Map<String, dynamic> json) {
  return _ServiceRef.fromJson(json);
}

/// @nodoc
mixin _$ServiceRef {
  int get id => throw _privateConstructorUsedError;
  String get code => throw _privateConstructorUsedError;
  String get nom => throw _privateConstructorUsedError;
  String? get responsableNom => throw _privateConstructorUsedError;

  /// Serializes this ServiceRef to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of ServiceRef
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $ServiceRefCopyWith<ServiceRef> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $ServiceRefCopyWith<$Res> {
  factory $ServiceRefCopyWith(
          ServiceRef value, $Res Function(ServiceRef) then) =
      _$ServiceRefCopyWithImpl<$Res, ServiceRef>;
  @useResult
  $Res call({int id, String code, String nom, String? responsableNom});
}

/// @nodoc
class _$ServiceRefCopyWithImpl<$Res, $Val extends ServiceRef>
    implements $ServiceRefCopyWith<$Res> {
  _$ServiceRefCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of ServiceRef
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? code = null,
    Object? nom = null,
    Object? responsableNom = freezed,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      code: null == code
          ? _value.code
          : code // ignore: cast_nullable_to_non_nullable
              as String,
      nom: null == nom
          ? _value.nom
          : nom // ignore: cast_nullable_to_non_nullable
              as String,
      responsableNom: freezed == responsableNom
          ? _value.responsableNom
          : responsableNom // ignore: cast_nullable_to_non_nullable
              as String?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$ServiceRefImplCopyWith<$Res>
    implements $ServiceRefCopyWith<$Res> {
  factory _$$ServiceRefImplCopyWith(
          _$ServiceRefImpl value, $Res Function(_$ServiceRefImpl) then) =
      __$$ServiceRefImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({int id, String code, String nom, String? responsableNom});
}

/// @nodoc
class __$$ServiceRefImplCopyWithImpl<$Res>
    extends _$ServiceRefCopyWithImpl<$Res, _$ServiceRefImpl>
    implements _$$ServiceRefImplCopyWith<$Res> {
  __$$ServiceRefImplCopyWithImpl(
      _$ServiceRefImpl _value, $Res Function(_$ServiceRefImpl) _then)
      : super(_value, _then);

  /// Create a copy of ServiceRef
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? code = null,
    Object? nom = null,
    Object? responsableNom = freezed,
  }) {
    return _then(_$ServiceRefImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      code: null == code
          ? _value.code
          : code // ignore: cast_nullable_to_non_nullable
              as String,
      nom: null == nom
          ? _value.nom
          : nom // ignore: cast_nullable_to_non_nullable
              as String,
      responsableNom: freezed == responsableNom
          ? _value.responsableNom
          : responsableNom // ignore: cast_nullable_to_non_nullable
              as String?,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$ServiceRefImpl implements _ServiceRef {
  const _$ServiceRefImpl(
      {required this.id,
      required this.code,
      required this.nom,
      this.responsableNom});

  factory _$ServiceRefImpl.fromJson(Map<String, dynamic> json) =>
      _$$ServiceRefImplFromJson(json);

  @override
  final int id;
  @override
  final String code;
  @override
  final String nom;
  @override
  final String? responsableNom;

  @override
  String toString() {
    return 'ServiceRef(id: $id, code: $code, nom: $nom, responsableNom: $responsableNom)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$ServiceRefImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.code, code) || other.code == code) &&
            (identical(other.nom, nom) || other.nom == nom) &&
            (identical(other.responsableNom, responsableNom) ||
                other.responsableNom == responsableNom));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(runtimeType, id, code, nom, responsableNom);

  /// Create a copy of ServiceRef
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$ServiceRefImplCopyWith<_$ServiceRefImpl> get copyWith =>
      __$$ServiceRefImplCopyWithImpl<_$ServiceRefImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$ServiceRefImplToJson(
      this,
    );
  }
}

abstract class _ServiceRef implements ServiceRef {
  const factory _ServiceRef(
      {required final int id,
      required final String code,
      required final String nom,
      final String? responsableNom}) = _$ServiceRefImpl;

  factory _ServiceRef.fromJson(Map<String, dynamic> json) =
      _$ServiceRefImpl.fromJson;

  @override
  int get id;
  @override
  String get code;
  @override
  String get nom;
  @override
  String? get responsableNom;

  /// Create a copy of ServiceRef
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$ServiceRefImplCopyWith<_$ServiceRefImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

ListeValeurRef _$ListeValeurRefFromJson(Map<String, dynamic> json) {
  return _ListeValeurRef.fromJson(json);
}

/// @nodoc
mixin _$ListeValeurRef {
  int get id => throw _privateConstructorUsedError;
  String get categorie => throw _privateConstructorUsedError;
  String get code => throw _privateConstructorUsedError;
  String get libelle => throw _privateConstructorUsedError;

  /// Serializes this ListeValeurRef to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of ListeValeurRef
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $ListeValeurRefCopyWith<ListeValeurRef> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $ListeValeurRefCopyWith<$Res> {
  factory $ListeValeurRefCopyWith(
          ListeValeurRef value, $Res Function(ListeValeurRef) then) =
      _$ListeValeurRefCopyWithImpl<$Res, ListeValeurRef>;
  @useResult
  $Res call({int id, String categorie, String code, String libelle});
}

/// @nodoc
class _$ListeValeurRefCopyWithImpl<$Res, $Val extends ListeValeurRef>
    implements $ListeValeurRefCopyWith<$Res> {
  _$ListeValeurRefCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of ListeValeurRef
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? categorie = null,
    Object? code = null,
    Object? libelle = null,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      categorie: null == categorie
          ? _value.categorie
          : categorie // ignore: cast_nullable_to_non_nullable
              as String,
      code: null == code
          ? _value.code
          : code // ignore: cast_nullable_to_non_nullable
              as String,
      libelle: null == libelle
          ? _value.libelle
          : libelle // ignore: cast_nullable_to_non_nullable
              as String,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$ListeValeurRefImplCopyWith<$Res>
    implements $ListeValeurRefCopyWith<$Res> {
  factory _$$ListeValeurRefImplCopyWith(_$ListeValeurRefImpl value,
          $Res Function(_$ListeValeurRefImpl) then) =
      __$$ListeValeurRefImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({int id, String categorie, String code, String libelle});
}

/// @nodoc
class __$$ListeValeurRefImplCopyWithImpl<$Res>
    extends _$ListeValeurRefCopyWithImpl<$Res, _$ListeValeurRefImpl>
    implements _$$ListeValeurRefImplCopyWith<$Res> {
  __$$ListeValeurRefImplCopyWithImpl(
      _$ListeValeurRefImpl _value, $Res Function(_$ListeValeurRefImpl) _then)
      : super(_value, _then);

  /// Create a copy of ListeValeurRef
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? categorie = null,
    Object? code = null,
    Object? libelle = null,
  }) {
    return _then(_$ListeValeurRefImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      categorie: null == categorie
          ? _value.categorie
          : categorie // ignore: cast_nullable_to_non_nullable
              as String,
      code: null == code
          ? _value.code
          : code // ignore: cast_nullable_to_non_nullable
              as String,
      libelle: null == libelle
          ? _value.libelle
          : libelle // ignore: cast_nullable_to_non_nullable
              as String,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$ListeValeurRefImpl implements _ListeValeurRef {
  const _$ListeValeurRefImpl(
      {required this.id,
      required this.categorie,
      required this.code,
      required this.libelle});

  factory _$ListeValeurRefImpl.fromJson(Map<String, dynamic> json) =>
      _$$ListeValeurRefImplFromJson(json);

  @override
  final int id;
  @override
  final String categorie;
  @override
  final String code;
  @override
  final String libelle;

  @override
  String toString() {
    return 'ListeValeurRef(id: $id, categorie: $categorie, code: $code, libelle: $libelle)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$ListeValeurRefImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.categorie, categorie) ||
                other.categorie == categorie) &&
            (identical(other.code, code) || other.code == code) &&
            (identical(other.libelle, libelle) || other.libelle == libelle));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(runtimeType, id, categorie, code, libelle);

  /// Create a copy of ListeValeurRef
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$ListeValeurRefImplCopyWith<_$ListeValeurRefImpl> get copyWith =>
      __$$ListeValeurRefImplCopyWithImpl<_$ListeValeurRefImpl>(
          this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$ListeValeurRefImplToJson(
      this,
    );
  }
}

abstract class _ListeValeurRef implements ListeValeurRef {
  const factory _ListeValeurRef(
      {required final int id,
      required final String categorie,
      required final String code,
      required final String libelle}) = _$ListeValeurRefImpl;

  factory _ListeValeurRef.fromJson(Map<String, dynamic> json) =
      _$ListeValeurRefImpl.fromJson;

  @override
  int get id;
  @override
  String get categorie;
  @override
  String get code;
  @override
  String get libelle;

  /// Create a copy of ListeValeurRef
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$ListeValeurRefImplCopyWith<_$ListeValeurRefImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

Personnel _$PersonnelFromJson(Map<String, dynamic> json) {
  return _Personnel.fromJson(json);
}

/// @nodoc
mixin _$Personnel {
  int? get id => throw _privateConstructorUsedError;
  String? get matricule => throw _privateConstructorUsedError;
  String get nom => throw _privateConstructorUsedError;
  String get prenom => throw _privateConstructorUsedError;

  /// 'M' ou 'F' — voir App\Entity\Enum\Sexe côté serveur.
  String get sexe => throw _privateConstructorUsedError;
  String? get dateNaissance => throw _privateConstructorUsedError;
  String get fonction => throw _privateConstructorUsedError;
  String? get grade => throw _privateConstructorUsedError;
  dynamic get typeContrat =>
      throw _privateConstructorUsedError; // ListeValeurRef embarqué ou IRI string
  String? get dateEmbauche => throw _privateConstructorUsedError;
  String get statut => throw _privateConstructorUsedError;
  String? get telephone => throw _privateConstructorUsedError;
  String? get email => throw _privateConstructorUsedError;
  String? get adresse => throw _privateConstructorUsedError;
  dynamic get service =>
      throw _privateConstructorUsedError; // ServiceRef embarqué ou IRI string
  String? get observations => throw _privateConstructorUsedError;
  String? get createdAt => throw _privateConstructorUsedError;
  String? get updatedAt => throw _privateConstructorUsedError;
  String? get nomComplet => throw _privateConstructorUsedError;
  bool? get hasPhoto => throw _privateConstructorUsedError;

  /// Serializes this Personnel to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of Personnel
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $PersonnelCopyWith<Personnel> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $PersonnelCopyWith<$Res> {
  factory $PersonnelCopyWith(Personnel value, $Res Function(Personnel) then) =
      _$PersonnelCopyWithImpl<$Res, Personnel>;
  @useResult
  $Res call(
      {int? id,
      String? matricule,
      String nom,
      String prenom,
      String sexe,
      String? dateNaissance,
      String fonction,
      String? grade,
      dynamic typeContrat,
      String? dateEmbauche,
      String statut,
      String? telephone,
      String? email,
      String? adresse,
      dynamic service,
      String? observations,
      String? createdAt,
      String? updatedAt,
      String? nomComplet,
      bool? hasPhoto});
}

/// @nodoc
class _$PersonnelCopyWithImpl<$Res, $Val extends Personnel>
    implements $PersonnelCopyWith<$Res> {
  _$PersonnelCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of Personnel
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = freezed,
    Object? matricule = freezed,
    Object? nom = null,
    Object? prenom = null,
    Object? sexe = null,
    Object? dateNaissance = freezed,
    Object? fonction = null,
    Object? grade = freezed,
    Object? typeContrat = freezed,
    Object? dateEmbauche = freezed,
    Object? statut = null,
    Object? telephone = freezed,
    Object? email = freezed,
    Object? adresse = freezed,
    Object? service = freezed,
    Object? observations = freezed,
    Object? createdAt = freezed,
    Object? updatedAt = freezed,
    Object? nomComplet = freezed,
    Object? hasPhoto = freezed,
  }) {
    return _then(_value.copyWith(
      id: freezed == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int?,
      matricule: freezed == matricule
          ? _value.matricule
          : matricule // ignore: cast_nullable_to_non_nullable
              as String?,
      nom: null == nom
          ? _value.nom
          : nom // ignore: cast_nullable_to_non_nullable
              as String,
      prenom: null == prenom
          ? _value.prenom
          : prenom // ignore: cast_nullable_to_non_nullable
              as String,
      sexe: null == sexe
          ? _value.sexe
          : sexe // ignore: cast_nullable_to_non_nullable
              as String,
      dateNaissance: freezed == dateNaissance
          ? _value.dateNaissance
          : dateNaissance // ignore: cast_nullable_to_non_nullable
              as String?,
      fonction: null == fonction
          ? _value.fonction
          : fonction // ignore: cast_nullable_to_non_nullable
              as String,
      grade: freezed == grade
          ? _value.grade
          : grade // ignore: cast_nullable_to_non_nullable
              as String?,
      typeContrat: freezed == typeContrat
          ? _value.typeContrat
          : typeContrat // ignore: cast_nullable_to_non_nullable
              as dynamic,
      dateEmbauche: freezed == dateEmbauche
          ? _value.dateEmbauche
          : dateEmbauche // ignore: cast_nullable_to_non_nullable
              as String?,
      statut: null == statut
          ? _value.statut
          : statut // ignore: cast_nullable_to_non_nullable
              as String,
      telephone: freezed == telephone
          ? _value.telephone
          : telephone // ignore: cast_nullable_to_non_nullable
              as String?,
      email: freezed == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String?,
      adresse: freezed == adresse
          ? _value.adresse
          : adresse // ignore: cast_nullable_to_non_nullable
              as String?,
      service: freezed == service
          ? _value.service
          : service // ignore: cast_nullable_to_non_nullable
              as dynamic,
      observations: freezed == observations
          ? _value.observations
          : observations // ignore: cast_nullable_to_non_nullable
              as String?,
      createdAt: freezed == createdAt
          ? _value.createdAt
          : createdAt // ignore: cast_nullable_to_non_nullable
              as String?,
      updatedAt: freezed == updatedAt
          ? _value.updatedAt
          : updatedAt // ignore: cast_nullable_to_non_nullable
              as String?,
      nomComplet: freezed == nomComplet
          ? _value.nomComplet
          : nomComplet // ignore: cast_nullable_to_non_nullable
              as String?,
      hasPhoto: freezed == hasPhoto
          ? _value.hasPhoto
          : hasPhoto // ignore: cast_nullable_to_non_nullable
              as bool?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$PersonnelImplCopyWith<$Res>
    implements $PersonnelCopyWith<$Res> {
  factory _$$PersonnelImplCopyWith(
          _$PersonnelImpl value, $Res Function(_$PersonnelImpl) then) =
      __$$PersonnelImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {int? id,
      String? matricule,
      String nom,
      String prenom,
      String sexe,
      String? dateNaissance,
      String fonction,
      String? grade,
      dynamic typeContrat,
      String? dateEmbauche,
      String statut,
      String? telephone,
      String? email,
      String? adresse,
      dynamic service,
      String? observations,
      String? createdAt,
      String? updatedAt,
      String? nomComplet,
      bool? hasPhoto});
}

/// @nodoc
class __$$PersonnelImplCopyWithImpl<$Res>
    extends _$PersonnelCopyWithImpl<$Res, _$PersonnelImpl>
    implements _$$PersonnelImplCopyWith<$Res> {
  __$$PersonnelImplCopyWithImpl(
      _$PersonnelImpl _value, $Res Function(_$PersonnelImpl) _then)
      : super(_value, _then);

  /// Create a copy of Personnel
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = freezed,
    Object? matricule = freezed,
    Object? nom = null,
    Object? prenom = null,
    Object? sexe = null,
    Object? dateNaissance = freezed,
    Object? fonction = null,
    Object? grade = freezed,
    Object? typeContrat = freezed,
    Object? dateEmbauche = freezed,
    Object? statut = null,
    Object? telephone = freezed,
    Object? email = freezed,
    Object? adresse = freezed,
    Object? service = freezed,
    Object? observations = freezed,
    Object? createdAt = freezed,
    Object? updatedAt = freezed,
    Object? nomComplet = freezed,
    Object? hasPhoto = freezed,
  }) {
    return _then(_$PersonnelImpl(
      id: freezed == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int?,
      matricule: freezed == matricule
          ? _value.matricule
          : matricule // ignore: cast_nullable_to_non_nullable
              as String?,
      nom: null == nom
          ? _value.nom
          : nom // ignore: cast_nullable_to_non_nullable
              as String,
      prenom: null == prenom
          ? _value.prenom
          : prenom // ignore: cast_nullable_to_non_nullable
              as String,
      sexe: null == sexe
          ? _value.sexe
          : sexe // ignore: cast_nullable_to_non_nullable
              as String,
      dateNaissance: freezed == dateNaissance
          ? _value.dateNaissance
          : dateNaissance // ignore: cast_nullable_to_non_nullable
              as String?,
      fonction: null == fonction
          ? _value.fonction
          : fonction // ignore: cast_nullable_to_non_nullable
              as String,
      grade: freezed == grade
          ? _value.grade
          : grade // ignore: cast_nullable_to_non_nullable
              as String?,
      typeContrat: freezed == typeContrat
          ? _value.typeContrat
          : typeContrat // ignore: cast_nullable_to_non_nullable
              as dynamic,
      dateEmbauche: freezed == dateEmbauche
          ? _value.dateEmbauche
          : dateEmbauche // ignore: cast_nullable_to_non_nullable
              as String?,
      statut: null == statut
          ? _value.statut
          : statut // ignore: cast_nullable_to_non_nullable
              as String,
      telephone: freezed == telephone
          ? _value.telephone
          : telephone // ignore: cast_nullable_to_non_nullable
              as String?,
      email: freezed == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String?,
      adresse: freezed == adresse
          ? _value.adresse
          : adresse // ignore: cast_nullable_to_non_nullable
              as String?,
      service: freezed == service
          ? _value.service
          : service // ignore: cast_nullable_to_non_nullable
              as dynamic,
      observations: freezed == observations
          ? _value.observations
          : observations // ignore: cast_nullable_to_non_nullable
              as String?,
      createdAt: freezed == createdAt
          ? _value.createdAt
          : createdAt // ignore: cast_nullable_to_non_nullable
              as String?,
      updatedAt: freezed == updatedAt
          ? _value.updatedAt
          : updatedAt // ignore: cast_nullable_to_non_nullable
              as String?,
      nomComplet: freezed == nomComplet
          ? _value.nomComplet
          : nomComplet // ignore: cast_nullable_to_non_nullable
              as String?,
      hasPhoto: freezed == hasPhoto
          ? _value.hasPhoto
          : hasPhoto // ignore: cast_nullable_to_non_nullable
              as bool?,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$PersonnelImpl implements _Personnel {
  const _$PersonnelImpl(
      {this.id,
      this.matricule,
      required this.nom,
      required this.prenom,
      required this.sexe,
      this.dateNaissance,
      required this.fonction,
      this.grade,
      this.typeContrat,
      this.dateEmbauche,
      required this.statut,
      this.telephone,
      this.email,
      this.adresse,
      this.service,
      this.observations,
      this.createdAt,
      this.updatedAt,
      this.nomComplet,
      this.hasPhoto});

  factory _$PersonnelImpl.fromJson(Map<String, dynamic> json) =>
      _$$PersonnelImplFromJson(json);

  @override
  final int? id;
  @override
  final String? matricule;
  @override
  final String nom;
  @override
  final String prenom;

  /// 'M' ou 'F' — voir App\Entity\Enum\Sexe côté serveur.
  @override
  final String sexe;
  @override
  final String? dateNaissance;
  @override
  final String fonction;
  @override
  final String? grade;
  @override
  final dynamic typeContrat;
// ListeValeurRef embarqué ou IRI string
  @override
  final String? dateEmbauche;
  @override
  final String statut;
  @override
  final String? telephone;
  @override
  final String? email;
  @override
  final String? adresse;
  @override
  final dynamic service;
// ServiceRef embarqué ou IRI string
  @override
  final String? observations;
  @override
  final String? createdAt;
  @override
  final String? updatedAt;
  @override
  final String? nomComplet;
  @override
  final bool? hasPhoto;

  @override
  String toString() {
    return 'Personnel(id: $id, matricule: $matricule, nom: $nom, prenom: $prenom, sexe: $sexe, dateNaissance: $dateNaissance, fonction: $fonction, grade: $grade, typeContrat: $typeContrat, dateEmbauche: $dateEmbauche, statut: $statut, telephone: $telephone, email: $email, adresse: $adresse, service: $service, observations: $observations, createdAt: $createdAt, updatedAt: $updatedAt, nomComplet: $nomComplet, hasPhoto: $hasPhoto)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$PersonnelImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.matricule, matricule) ||
                other.matricule == matricule) &&
            (identical(other.nom, nom) || other.nom == nom) &&
            (identical(other.prenom, prenom) || other.prenom == prenom) &&
            (identical(other.sexe, sexe) || other.sexe == sexe) &&
            (identical(other.dateNaissance, dateNaissance) ||
                other.dateNaissance == dateNaissance) &&
            (identical(other.fonction, fonction) ||
                other.fonction == fonction) &&
            (identical(other.grade, grade) || other.grade == grade) &&
            const DeepCollectionEquality()
                .equals(other.typeContrat, typeContrat) &&
            (identical(other.dateEmbauche, dateEmbauche) ||
                other.dateEmbauche == dateEmbauche) &&
            (identical(other.statut, statut) || other.statut == statut) &&
            (identical(other.telephone, telephone) ||
                other.telephone == telephone) &&
            (identical(other.email, email) || other.email == email) &&
            (identical(other.adresse, adresse) || other.adresse == adresse) &&
            const DeepCollectionEquality().equals(other.service, service) &&
            (identical(other.observations, observations) ||
                other.observations == observations) &&
            (identical(other.createdAt, createdAt) ||
                other.createdAt == createdAt) &&
            (identical(other.updatedAt, updatedAt) ||
                other.updatedAt == updatedAt) &&
            (identical(other.nomComplet, nomComplet) ||
                other.nomComplet == nomComplet) &&
            (identical(other.hasPhoto, hasPhoto) ||
                other.hasPhoto == hasPhoto));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hashAll([
        runtimeType,
        id,
        matricule,
        nom,
        prenom,
        sexe,
        dateNaissance,
        fonction,
        grade,
        const DeepCollectionEquality().hash(typeContrat),
        dateEmbauche,
        statut,
        telephone,
        email,
        adresse,
        const DeepCollectionEquality().hash(service),
        observations,
        createdAt,
        updatedAt,
        nomComplet,
        hasPhoto
      ]);

  /// Create a copy of Personnel
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$PersonnelImplCopyWith<_$PersonnelImpl> get copyWith =>
      __$$PersonnelImplCopyWithImpl<_$PersonnelImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$PersonnelImplToJson(
      this,
    );
  }
}

abstract class _Personnel implements Personnel {
  const factory _Personnel(
      {final int? id,
      final String? matricule,
      required final String nom,
      required final String prenom,
      required final String sexe,
      final String? dateNaissance,
      required final String fonction,
      final String? grade,
      final dynamic typeContrat,
      final String? dateEmbauche,
      required final String statut,
      final String? telephone,
      final String? email,
      final String? adresse,
      final dynamic service,
      final String? observations,
      final String? createdAt,
      final String? updatedAt,
      final String? nomComplet,
      final bool? hasPhoto}) = _$PersonnelImpl;

  factory _Personnel.fromJson(Map<String, dynamic> json) =
      _$PersonnelImpl.fromJson;

  @override
  int? get id;
  @override
  String? get matricule;
  @override
  String get nom;
  @override
  String get prenom;

  /// 'M' ou 'F' — voir App\Entity\Enum\Sexe côté serveur.
  @override
  String get sexe;
  @override
  String? get dateNaissance;
  @override
  String get fonction;
  @override
  String? get grade;
  @override
  dynamic get typeContrat; // ListeValeurRef embarqué ou IRI string
  @override
  String? get dateEmbauche;
  @override
  String get statut;
  @override
  String? get telephone;
  @override
  String? get email;
  @override
  String? get adresse;
  @override
  dynamic get service; // ServiceRef embarqué ou IRI string
  @override
  String? get observations;
  @override
  String? get createdAt;
  @override
  String? get updatedAt;
  @override
  String? get nomComplet;
  @override
  bool? get hasPhoto;

  /// Create a copy of Personnel
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$PersonnelImplCopyWith<_$PersonnelImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
