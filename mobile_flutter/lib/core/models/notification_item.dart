import 'package:freezed_annotation/freezed_annotation.dart';

part 'notification_item.freezed.dart';
part 'notification_item.g.dart';

@freezed
class NotificationItem with _$NotificationItem {
  const factory NotificationItem({
    required int id,
    required String titre,
    String? message,
    String? lien,
    required bool lu,
    required String createdAt,
  }) = _NotificationItem;

  factory NotificationItem.fromJson(Map<String, dynamic> json) => _$NotificationItemFromJson(json);
}

@freezed
class NotificationsReponse with _$NotificationsReponse {
  const factory NotificationsReponse({
    required List<NotificationItem> recentes,
    required List<NotificationItem> nonLues,
  }) = _NotificationsReponse;

  factory NotificationsReponse.fromJson(Map<String, dynamic> json) => _$NotificationsReponseFromJson(json);
}
