// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'notification_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_$NotificationItemImpl _$$NotificationItemImplFromJson(
        Map<String, dynamic> json) =>
    _$NotificationItemImpl(
      id: (json['id'] as num).toInt(),
      titre: json['titre'] as String,
      message: json['message'] as String?,
      lien: json['lien'] as String?,
      lu: json['lu'] as bool,
      createdAt: json['createdAt'] as String,
    );

Map<String, dynamic> _$$NotificationItemImplToJson(
        _$NotificationItemImpl instance) =>
    <String, dynamic>{
      'id': instance.id,
      'titre': instance.titre,
      'message': instance.message,
      'lien': instance.lien,
      'lu': instance.lu,
      'createdAt': instance.createdAt,
    };

_$NotificationsReponseImpl _$$NotificationsReponseImplFromJson(
        Map<String, dynamic> json) =>
    _$NotificationsReponseImpl(
      recentes: (json['recentes'] as List<dynamic>)
          .map((e) => NotificationItem.fromJson(e as Map<String, dynamic>))
          .toList(),
      nonLues: (json['nonLues'] as List<dynamic>)
          .map((e) => NotificationItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );

Map<String, dynamic> _$$NotificationsReponseImplToJson(
        _$NotificationsReponseImpl instance) =>
    <String, dynamic>{
      'recentes': instance.recentes,
      'nonLues': instance.nonLues,
    };
