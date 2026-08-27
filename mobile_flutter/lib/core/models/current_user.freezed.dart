// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'current_user.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

CurrentUser _$CurrentUserFromJson(Map<String, dynamic> json) {
  return _CurrentUser.fromJson(json);
}

/// @nodoc
mixin _$CurrentUser {
  int get id => throw _privateConstructorUsedError;
  String get email => throw _privateConstructorUsedError;
  String get nom => throw _privateConstructorUsedError;
  String get prenom => throw _privateConstructorUsedError;
  List<String> get roles => throw _privateConstructorUsedError;
  int? get serviceResponsableId => throw _privateConstructorUsedError;
  int? get directionDirigeeId => throw _privateConstructorUsedError;

  /// Serializes this CurrentUser to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of CurrentUser
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $CurrentUserCopyWith<CurrentUser> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $CurrentUserCopyWith<$Res> {
  factory $CurrentUserCopyWith(
          CurrentUser value, $Res Function(CurrentUser) then) =
      _$CurrentUserCopyWithImpl<$Res, CurrentUser>;
  @useResult
  $Res call(
      {int id,
      String email,
      String nom,
      String prenom,
      List<String> roles,
      int? serviceResponsableId,
      int? directionDirigeeId});
}

/// @nodoc
class _$CurrentUserCopyWithImpl<$Res, $Val extends CurrentUser>
    implements $CurrentUserCopyWith<$Res> {
  _$CurrentUserCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of CurrentUser
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? email = null,
    Object? nom = null,
    Object? prenom = null,
    Object? roles = null,
    Object? serviceResponsableId = freezed,
    Object? directionDirigeeId = freezed,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      email: null == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String,
      nom: null == nom
          ? _value.nom
          : nom // ignore: cast_nullable_to_non_nullable
              as String,
      prenom: null == prenom
          ? _value.prenom
          : prenom // ignore: cast_nullable_to_non_nullable
              as String,
      roles: null == roles
          ? _value.roles
          : roles // ignore: cast_nullable_to_non_nullable
              as List<String>,
      serviceResponsableId: freezed == serviceResponsableId
          ? _value.serviceResponsableId
          : serviceResponsableId // ignore: cast_nullable_to_non_nullable
              as int?,
      directionDirigeeId: freezed == directionDirigeeId
          ? _value.directionDirigeeId
          : directionDirigeeId // ignore: cast_nullable_to_non_nullable
              as int?,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$CurrentUserImplCopyWith<$Res>
    implements $CurrentUserCopyWith<$Res> {
  factory _$$CurrentUserImplCopyWith(
          _$CurrentUserImpl value, $Res Function(_$CurrentUserImpl) then) =
      __$$CurrentUserImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {int id,
      String email,
      String nom,
      String prenom,
      List<String> roles,
      int? serviceResponsableId,
      int? directionDirigeeId});
}

/// @nodoc
class __$$CurrentUserImplCopyWithImpl<$Res>
    extends _$CurrentUserCopyWithImpl<$Res, _$CurrentUserImpl>
    implements _$$CurrentUserImplCopyWith<$Res> {
  __$$CurrentUserImplCopyWithImpl(
      _$CurrentUserImpl _value, $Res Function(_$CurrentUserImpl) _then)
      : super(_value, _then);

  /// Create a copy of CurrentUser
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? email = null,
    Object? nom = null,
    Object? prenom = null,
    Object? roles = null,
    Object? serviceResponsableId = freezed,
    Object? directionDirigeeId = freezed,
  }) {
    return _then(_$CurrentUserImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      email: null == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String,
      nom: null == nom
          ? _value.nom
          : nom // ignore: cast_nullable_to_non_nullable
              as String,
      prenom: null == prenom
          ? _value.prenom
          : prenom // ignore: cast_nullable_to_non_nullable
              as String,
      roles: null == roles
          ? _value._roles
          : roles // ignore: cast_nullable_to_non_nullable
              as List<String>,
      serviceResponsableId: freezed == serviceResponsableId
          ? _value.serviceResponsableId
          : serviceResponsableId // ignore: cast_nullable_to_non_nullable
              as int?,
      directionDirigeeId: freezed == directionDirigeeId
          ? _value.directionDirigeeId
          : directionDirigeeId // ignore: cast_nullable_to_non_nullable
              as int?,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$CurrentUserImpl implements _CurrentUser {
  const _$CurrentUserImpl(
      {required this.id,
      required this.email,
      required this.nom,
      required this.prenom,
      required final List<String> roles,
      this.serviceResponsableId,
      this.directionDirigeeId})
      : _roles = roles;

  factory _$CurrentUserImpl.fromJson(Map<String, dynamic> json) =>
      _$$CurrentUserImplFromJson(json);

  @override
  final int id;
  @override
  final String email;
  @override
  final String nom;
  @override
  final String prenom;
  final List<String> _roles;
  @override
  List<String> get roles {
    if (_roles is EqualUnmodifiableListView) return _roles;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_roles);
  }

  @override
  final int? serviceResponsableId;
  @override
  final int? directionDirigeeId;

  @override
  String toString() {
    return 'CurrentUser(id: $id, email: $email, nom: $nom, prenom: $prenom, roles: $roles, serviceResponsableId: $serviceResponsableId, directionDirigeeId: $directionDirigeeId)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$CurrentUserImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.email, email) || other.email == email) &&
            (identical(other.nom, nom) || other.nom == nom) &&
            (identical(other.prenom, prenom) || other.prenom == prenom) &&
            const DeepCollectionEquality().equals(other._roles, _roles) &&
            (identical(other.serviceResponsableId, serviceResponsableId) ||
                other.serviceResponsableId == serviceResponsableId) &&
            (identical(other.directionDirigeeId, directionDirigeeId) ||
                other.directionDirigeeId == directionDirigeeId));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(
      runtimeType,
      id,
      email,
      nom,
      prenom,
      const DeepCollectionEquality().hash(_roles),
      serviceResponsableId,
      directionDirigeeId);

  /// Create a copy of CurrentUser
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$CurrentUserImplCopyWith<_$CurrentUserImpl> get copyWith =>
      __$$CurrentUserImplCopyWithImpl<_$CurrentUserImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$CurrentUserImplToJson(
      this,
    );
  }
}

abstract class _CurrentUser implements CurrentUser {
  const factory _CurrentUser(
      {required final int id,
      required final String email,
      required final String nom,
      required final String prenom,
      required final List<String> roles,
      final int? serviceResponsableId,
      final int? directionDirigeeId}) = _$CurrentUserImpl;

  factory _CurrentUser.fromJson(Map<String, dynamic> json) =
      _$CurrentUserImpl.fromJson;

  @override
  int get id;
  @override
  String get email;
  @override
  String get nom;
  @override
  String get prenom;
  @override
  List<String> get roles;
  @override
  int? get serviceResponsableId;
  @override
  int? get directionDirigeeId;

  /// Create a copy of CurrentUser
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$CurrentUserImplCopyWith<_$CurrentUserImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

UserRef _$UserRefFromJson(Map<String, dynamic> json) {
  return _UserRef.fromJson(json);
}

/// @nodoc
mixin _$UserRef {
  int get id => throw _privateConstructorUsedError;
  String get email => throw _privateConstructorUsedError;
  bool get actif => throw _privateConstructorUsedError;
  String get nomComplet => throw _privateConstructorUsedError;

  /// Serializes this UserRef to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of UserRef
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $UserRefCopyWith<UserRef> get copyWith => throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $UserRefCopyWith<$Res> {
  factory $UserRefCopyWith(UserRef value, $Res Function(UserRef) then) =
      _$UserRefCopyWithImpl<$Res, UserRef>;
  @useResult
  $Res call({int id, String email, bool actif, String nomComplet});
}

/// @nodoc
class _$UserRefCopyWithImpl<$Res, $Val extends UserRef>
    implements $UserRefCopyWith<$Res> {
  _$UserRefCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of UserRef
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? email = null,
    Object? actif = null,
    Object? nomComplet = null,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      email: null == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String,
      actif: null == actif
          ? _value.actif
          : actif // ignore: cast_nullable_to_non_nullable
              as bool,
      nomComplet: null == nomComplet
          ? _value.nomComplet
          : nomComplet // ignore: cast_nullable_to_non_nullable
              as String,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$UserRefImplCopyWith<$Res> implements $UserRefCopyWith<$Res> {
  factory _$$UserRefImplCopyWith(
          _$UserRefImpl value, $Res Function(_$UserRefImpl) then) =
      __$$UserRefImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({int id, String email, bool actif, String nomComplet});
}

/// @nodoc
class __$$UserRefImplCopyWithImpl<$Res>
    extends _$UserRefCopyWithImpl<$Res, _$UserRefImpl>
    implements _$$UserRefImplCopyWith<$Res> {
  __$$UserRefImplCopyWithImpl(
      _$UserRefImpl _value, $Res Function(_$UserRefImpl) _then)
      : super(_value, _then);

  /// Create a copy of UserRef
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? email = null,
    Object? actif = null,
    Object? nomComplet = null,
  }) {
    return _then(_$UserRefImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      email: null == email
          ? _value.email
          : email // ignore: cast_nullable_to_non_nullable
              as String,
      actif: null == actif
          ? _value.actif
          : actif // ignore: cast_nullable_to_non_nullable
              as bool,
      nomComplet: null == nomComplet
          ? _value.nomComplet
          : nomComplet // ignore: cast_nullable_to_non_nullable
              as String,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$UserRefImpl implements _UserRef {
  const _$UserRefImpl(
      {required this.id,
      required this.email,
      required this.actif,
      required this.nomComplet});

  factory _$UserRefImpl.fromJson(Map<String, dynamic> json) =>
      _$$UserRefImplFromJson(json);

  @override
  final int id;
  @override
  final String email;
  @override
  final bool actif;
  @override
  final String nomComplet;

  @override
  String toString() {
    return 'UserRef(id: $id, email: $email, actif: $actif, nomComplet: $nomComplet)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$UserRefImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.email, email) || other.email == email) &&
            (identical(other.actif, actif) || other.actif == actif) &&
            (identical(other.nomComplet, nomComplet) ||
                other.nomComplet == nomComplet));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(runtimeType, id, email, actif, nomComplet);

  /// Create a copy of UserRef
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$UserRefImplCopyWith<_$UserRefImpl> get copyWith =>
      __$$UserRefImplCopyWithImpl<_$UserRefImpl>(this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$UserRefImplToJson(
      this,
    );
  }
}

abstract class _UserRef implements UserRef {
  const factory _UserRef(
      {required final int id,
      required final String email,
      required final bool actif,
      required final String nomComplet}) = _$UserRefImpl;

  factory _UserRef.fromJson(Map<String, dynamic> json) = _$UserRefImpl.fromJson;

  @override
  int get id;
  @override
  String get email;
  @override
  bool get actif;
  @override
  String get nomComplet;

  /// Create a copy of UserRef
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$UserRefImplCopyWith<_$UserRefImpl> get copyWith =>
      throw _privateConstructorUsedError;
}
