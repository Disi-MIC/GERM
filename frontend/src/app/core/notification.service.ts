import { HttpClient } from '@angular/common/http';
import { Injectable, computed, signal } from '@angular/core';
import { API_BASE } from './api-base';
import { NotificationItem, NotificationsReponse } from './models/notification.model';

/**
 * État partagé des notifications (cloche + badges du menu), rafraîchi par
 * ShellComponent (au démarrage, périodiquement, et après navigation). `nonLues`
 * n'est pas plafonné côté serveur : le compteur de la cloche et les badges par
 * rubrique (comptés par préfixe de route sur `lien`, voir ShellComponent)
 * restent donc exacts même au-delà des dernières notifications affichées dans
 * le menu déroulant.
 */
@Injectable({ providedIn: 'root' })
export class NotificationService {
  private readonly recentesSignal = signal<NotificationItem[]>([]);
  private readonly nonLuesSignal = signal<NotificationItem[]>([]);

  readonly recentes = this.recentesSignal.asReadonly();
  readonly nonLues = this.nonLuesSignal.asReadonly();
  readonly nombreNonLues = computed(() => this.nonLuesSignal().length);

  constructor(private readonly http: HttpClient) {}

  charger(): void {
    this.http.get<NotificationsReponse>(`${API_BASE}/me/notifications`).subscribe({
      next: (reponse) => {
        this.recentesSignal.set(reponse.recentes);
        this.nonLuesSignal.set(reponse.nonLues);
      },
      error: () => {
        // Pas de session active ou erreur réseau : la cloche reste simplement vide.
      },
    });
  }

  marquerLue(id: number): void {
    this.http.post(`${API_BASE}/me/notifications/${id}/lire`, {}).subscribe({ next: () => this.charger() });
  }

  marquerToutesLues(): void {
    this.http.post(`${API_BASE}/me/notifications/tout-lire`, {}).subscribe({ next: () => this.charger() });
  }

  /** Badge d'une rubrique du menu : nombre de notifications non lues dont le lien mène à cette rubrique. */
  compteurPour(prefixeRoute: string): number {
    return this.nonLuesSignal().filter((n) => n.lien?.startsWith(prefixeRoute)).length;
  }
}
