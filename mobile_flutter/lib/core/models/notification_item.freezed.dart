// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'notification_item.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

T _$identity<T>(T value) => value;

final _privateConstructorUsedError = UnsupportedError(
    'It seems like you constructed your class using `MyClass._()`. This constructor is only meant to be used by freezed and you are not supposed to need it nor use it.\nPlease check the documentation here for more information: https://github.com/rrousselGit/freezed#adding-getters-and-methods-to-our-models');

NotificationItem _$NotificationItemFromJson(Map<String, dynamic> json) {
  return _NotificationItem.fromJson(json);
}

/// @nodoc
mixin _$NotificationItem {
  int get id => throw _privateConstructorUsedError;
  String get titre => throw _privateConstructorUsedError;
  String? get message => throw _privateConstructorUsedError;
  String? get lien => throw _privateConstructorUsedError;
  bool get lu => throw _privateConstructorUsedError;
  String get createdAt => throw _privateConstructorUsedError;

  /// Serializes this NotificationItem to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of NotificationItem
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $NotificationItemCopyWith<NotificationItem> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $NotificationItemCopyWith<$Res> {
  factory $NotificationItemCopyWith(
          NotificationItem value, $Res Function(NotificationItem) then) =
      _$NotificationItemCopyWithImpl<$Res, NotificationItem>;
  @useResult
  $Res call(
      {int id,
      String titre,
      String? message,
      String? lien,
      bool lu,
      String createdAt});
}

/// @nodoc
class _$NotificationItemCopyWithImpl<$Res, $Val extends NotificationItem>
    implements $NotificationItemCopyWith<$Res> {
  _$NotificationItemCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of NotificationItem
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? titre = null,
    Object? message = freezed,
    Object? lien = freezed,
    Object? lu = null,
    Object? createdAt = null,
  }) {
    return _then(_value.copyWith(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      titre: null == titre
          ? _value.titre
          : titre // ignore: cast_nullable_to_non_nullable
              as String,
      message: freezed == message
          ? _value.message
          : message // ignore: cast_nullable_to_non_nullable
              as String?,
      lien: freezed == lien
          ? _value.lien
          : lien // ignore: cast_nullable_to_non_nullable
              as String?,
      lu: null == lu
          ? _value.lu
          : lu // ignore: cast_nullable_to_non_nullable
              as bool,
      createdAt: null == createdAt
          ? _value.createdAt
          : createdAt // ignore: cast_nullable_to_non_nullable
              as String,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$NotificationItemImplCopyWith<$Res>
    implements $NotificationItemCopyWith<$Res> {
  factory _$$NotificationItemImplCopyWith(_$NotificationItemImpl value,
          $Res Function(_$NotificationItemImpl) then) =
      __$$NotificationItemImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call(
      {int id,
      String titre,
      String? message,
      String? lien,
      bool lu,
      String createdAt});
}

/// @nodoc
class __$$NotificationItemImplCopyWithImpl<$Res>
    extends _$NotificationItemCopyWithImpl<$Res, _$NotificationItemImpl>
    implements _$$NotificationItemImplCopyWith<$Res> {
  __$$NotificationItemImplCopyWithImpl(_$NotificationItemImpl _value,
      $Res Function(_$NotificationItemImpl) _then)
      : super(_value, _then);

  /// Create a copy of NotificationItem
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? id = null,
    Object? titre = null,
    Object? message = freezed,
    Object? lien = freezed,
    Object? lu = null,
    Object? createdAt = null,
  }) {
    return _then(_$NotificationItemImpl(
      id: null == id
          ? _value.id
          : id // ignore: cast_nullable_to_non_nullable
              as int,
      titre: null == titre
          ? _value.titre
          : titre // ignore: cast_nullable_to_non_nullable
              as String,
      message: freezed == message
          ? _value.message
          : message // ignore: cast_nullable_to_non_nullable
              as String?,
      lien: freezed == lien
          ? _value.lien
          : lien // ignore: cast_nullable_to_non_nullable
              as String?,
      lu: null == lu
          ? _value.lu
          : lu // ignore: cast_nullable_to_non_nullable
              as bool,
      createdAt: null == createdAt
          ? _value.createdAt
          : createdAt // ignore: cast_nullable_to_non_nullable
              as String,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$NotificationItemImpl implements _NotificationItem {
  const _$NotificationItemImpl(
      {required this.id,
      required this.titre,
      this.message,
      this.lien,
      required this.lu,
      required this.createdAt});

  factory _$NotificationItemImpl.fromJson(Map<String, dynamic> json) =>
      _$$NotificationItemImplFromJson(json);

  @override
  final int id;
  @override
  final String titre;
  @override
  final String? message;
  @override
  final String? lien;
  @override
  final bool lu;
  @override
  final String createdAt;

  @override
  String toString() {
    return 'NotificationItem(id: $id, titre: $titre, message: $message, lien: $lien, lu: $lu, createdAt: $createdAt)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$NotificationItemImpl &&
            (identical(other.id, id) || other.id == id) &&
            (identical(other.titre, titre) || other.titre == titre) &&
            (identical(other.message, message) || other.message == message) &&
            (identical(other.lien, lien) || other.lien == lien) &&
            (identical(other.lu, lu) || other.lu == lu) &&
            (identical(other.createdAt, createdAt) ||
                other.createdAt == createdAt));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode =>
      Object.hash(runtimeType, id, titre, message, lien, lu, createdAt);

  /// Create a copy of NotificationItem
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$NotificationItemImplCopyWith<_$NotificationItemImpl> get copyWith =>
      __$$NotificationItemImplCopyWithImpl<_$NotificationItemImpl>(
          this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$NotificationItemImplToJson(
      this,
    );
  }
}

abstract class _NotificationItem implements NotificationItem {
  const factory _NotificationItem(
      {required final int id,
      required final String titre,
      final String? message,
      final String? lien,
      required final bool lu,
      required final String createdAt}) = _$NotificationItemImpl;

  factory _NotificationItem.fromJson(Map<String, dynamic> json) =
      _$NotificationItemImpl.fromJson;

  @override
  int get id;
  @override
  String get titre;
  @override
  String? get message;
  @override
  String? get lien;
  @override
  bool get lu;
  @override
  String get createdAt;

  /// Create a copy of NotificationItem
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$NotificationItemImplCopyWith<_$NotificationItemImpl> get copyWith =>
      throw _privateConstructorUsedError;
}

NotificationsReponse _$NotificationsReponseFromJson(Map<String, dynamic> json) {
  return _NotificationsReponse.fromJson(json);
}

/// @nodoc
mixin _$NotificationsReponse {
  List<NotificationItem> get recentes => throw _privateConstructorUsedError;
  List<NotificationItem> get nonLues => throw _privateConstructorUsedError;

  /// Serializes this NotificationsReponse to a JSON map.
  Map<String, dynamic> toJson() => throw _privateConstructorUsedError;

  /// Create a copy of NotificationsReponse
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  $NotificationsReponseCopyWith<NotificationsReponse> get copyWith =>
      throw _privateConstructorUsedError;
}

/// @nodoc
abstract class $NotificationsReponseCopyWith<$Res> {
  factory $NotificationsReponseCopyWith(NotificationsReponse value,
          $Res Function(NotificationsReponse) then) =
      _$NotificationsReponseCopyWithImpl<$Res, NotificationsReponse>;
  @useResult
  $Res call({List<NotificationItem> recentes, List<NotificationItem> nonLues});
}

/// @nodoc
class _$NotificationsReponseCopyWithImpl<$Res,
        $Val extends NotificationsReponse>
    implements $NotificationsReponseCopyWith<$Res> {
  _$NotificationsReponseCopyWithImpl(this._value, this._then);

  // ignore: unused_field
  final $Val _value;
  // ignore: unused_field
  final $Res Function($Val) _then;

  /// Create a copy of NotificationsReponse
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? recentes = null,
    Object? nonLues = null,
  }) {
    return _then(_value.copyWith(
      recentes: null == recentes
          ? _value.recentes
          : recentes // ignore: cast_nullable_to_non_nullable
              as List<NotificationItem>,
      nonLues: null == nonLues
          ? _value.nonLues
          : nonLues // ignore: cast_nullable_to_non_nullable
              as List<NotificationItem>,
    ) as $Val);
  }
}

/// @nodoc
abstract class _$$NotificationsReponseImplCopyWith<$Res>
    implements $NotificationsReponseCopyWith<$Res> {
  factory _$$NotificationsReponseImplCopyWith(_$NotificationsReponseImpl value,
          $Res Function(_$NotificationsReponseImpl) then) =
      __$$NotificationsReponseImplCopyWithImpl<$Res>;
  @override
  @useResult
  $Res call({List<NotificationItem> recentes, List<NotificationItem> nonLues});
}

/// @nodoc
class __$$NotificationsReponseImplCopyWithImpl<$Res>
    extends _$NotificationsReponseCopyWithImpl<$Res, _$NotificationsReponseImpl>
    implements _$$NotificationsReponseImplCopyWith<$Res> {
  __$$NotificationsReponseImplCopyWithImpl(_$NotificationsReponseImpl _value,
      $Res Function(_$NotificationsReponseImpl) _then)
      : super(_value, _then);

  /// Create a copy of NotificationsReponse
  /// with the given fields replaced by the non-null parameter values.
  @pragma('vm:prefer-inline')
  @override
  $Res call({
    Object? recentes = null,
    Object? nonLues = null,
  }) {
    return _then(_$NotificationsReponseImpl(
      recentes: null == recentes
          ? _value._recentes
          : recentes // ignore: cast_nullable_to_non_nullable
              as List<NotificationItem>,
      nonLues: null == nonLues
          ? _value._nonLues
          : nonLues // ignore: cast_nullable_to_non_nullable
              as List<NotificationItem>,
    ));
  }
}

/// @nodoc
@JsonSerializable()
class _$NotificationsReponseImpl implements _NotificationsReponse {
  const _$NotificationsReponseImpl(
      {required final List<NotificationItem> recentes,
      required final List<NotificationItem> nonLues})
      : _recentes = recentes,
        _nonLues = nonLues;

  factory _$NotificationsReponseImpl.fromJson(Map<String, dynamic> json) =>
      _$$NotificationsReponseImplFromJson(json);

  final List<NotificationItem> _recentes;
  @override
  List<NotificationItem> get recentes {
    if (_recentes is EqualUnmodifiableListView) return _recentes;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_recentes);
  }

  final List<NotificationItem> _nonLues;
  @override
  List<NotificationItem> get nonLues {
    if (_nonLues is EqualUnmodifiableListView) return _nonLues;
    // ignore: implicit_dynamic_type
    return EqualUnmodifiableListView(_nonLues);
  }

  @override
  String toString() {
    return 'NotificationsReponse(recentes: $recentes, nonLues: $nonLues)';
  }

  @override
  bool operator ==(Object other) {
    return identical(this, other) ||
        (other.runtimeType == runtimeType &&
            other is _$NotificationsReponseImpl &&
            const DeepCollectionEquality().equals(other._recentes, _recentes) &&
            const DeepCollectionEquality().equals(other._nonLues, _nonLues));
  }

  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  int get hashCode => Object.hash(
      runtimeType,
      const DeepCollectionEquality().hash(_recentes),
      const DeepCollectionEquality().hash(_nonLues));

  /// Create a copy of NotificationsReponse
  /// with the given fields replaced by the non-null parameter values.
  @JsonKey(includeFromJson: false, includeToJson: false)
  @override
  @pragma('vm:prefer-inline')
  _$$NotificationsReponseImplCopyWith<_$NotificationsReponseImpl>
      get copyWith =>
          __$$NotificationsReponseImplCopyWithImpl<_$NotificationsReponseImpl>(
              this, _$identity);

  @override
  Map<String, dynamic> toJson() {
    return _$$NotificationsReponseImplToJson(
      this,
    );
  }
}

abstract class _NotificationsReponse implements NotificationsReponse {
  const factory _NotificationsReponse(
          {required final List<NotificationItem> recentes,
          required final List<NotificationItem> nonLues}) =
      _$NotificationsReponseImpl;

  factory _NotificationsReponse.fromJson(Map<String, dynamic> json) =
      _$NotificationsReponseImpl.fromJson;

  @override
  List<NotificationItem> get recentes;
  @override
  List<NotificationItem> get nonLues;

  /// Create a copy of NotificationsReponse
  /// with the given fields replaced by the non-null parameter values.
  @override
  @JsonKey(includeFromJson: false, includeToJson: false)
  _$$NotificationsReponseImplCopyWith<_$NotificationsReponseImpl>
      get copyWith => throw _privateConstructorUsedError;
}
