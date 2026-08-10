export interface NotificationItem {
  id: number;
  titre: string;
  message: string | null;
  lien: string | null;
  lu: boolean;
  createdAt: string;
}

export interface NotificationsReponse {
  recentes: NotificationItem[];
  nonLues: NotificationItem[];
}
