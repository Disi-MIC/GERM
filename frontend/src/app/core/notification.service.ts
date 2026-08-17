import { HttpClient } from '@angular/common/http';
import { Injectable, computed, signal } from '@angular/core';
import { Badge } from '@capawesome/capacitor-badge';
import { Capacitor } from '@capacitor/core';
import { API_BASE } from './api-base';
import { NotificationItem, NotificationsReponse } from './models/notification.model';

/**
 * État partagé des notifications (cloche + badges du menu), rafraîchi par
 * ShellComponent (au démarrage, périodiquement, et après navigation). `nonLues`
 * n'est pas plafonné côté serveur : le compteur de la cloche et les badges par
 * rubrique (comptés par préfixe de route sur `lien`, voir ShellComponent)
 * restent donc exacts même au-delà des dernières notifications affichées dans
 * le menu déroulant.
 *
 * Sur natif, le compteur synchronise aussi le badge sur l'icône de l'app
 * (voir synchroniserBadge) — pas de vraies notifications push (exigent un
 * compte Apple Developer Program payant, absent ici), donc le badge ne se
 * met à jour qu'aux moments où l'app est ouverte/rafraîchit déjà ses
 * notifications, pas en temps réel app fermée.
 */
@Injectable({ providedIn: 'root' })
export class NotificationService {
  private readonly recentesSignal = signal<NotificationItem[]>([]);
  private readonly nonLuesSignal = signal<NotificationItem[]>([]);
  private permissionBadgeDemandee = false;

  readonly recentes = this.recentesSignal.asReadonly();
  readonly nonLues = this.nonLuesSignal.asReadonly();
  readonly nombreNonLues = computed(() => this.nonLuesSignal().length);

  constructor(private readonly http: HttpClient) {}

  charger(): void {
    this.http.get<NotificationsReponse>(`${API_BASE}/me/notifications`).subscribe({
      next: (reponse) => {
        this.recentesSignal.set(reponse.recentes);
        this.nonLuesSignal.set(reponse.nonLues);
        this.synchroniserBadge(reponse.nonLues.length);
      },
      error: () => {
        // Pas de session active ou erreur réseau : la cloche reste simplement vide.
      },
    });
  }

  /** Retire le badge de l'icône — appelé à la déconnexion pour ne pas laisser un compteur obsolète. */
  async effacerBadge(): Promise<void> {
    if (!Capacitor.isNativePlatform()) {
      return;
    }
    try {
      await Badge.clear();
    } catch {
      // Plateforme sans badge ou permission refusée : rien à faire de plus.
    }
  }

  private async synchroniserBadge(compte: number): Promise<void> {
    if (!Capacitor.isNativePlatform()) {
      return;
    }
    try {
      if (!this.permissionBadgeDemandee) {
        this.permissionBadgeDemandee = true;
        const statut = await Badge.checkPermissions();
        if (statut.display !== 'granted') {
          await Badge.requestPermissions();
        }
      }
      await Badge.set({ count: compte });
    } catch {
      // Permission refusée ou plateforme non supportée : le badge reste simplement absent.
    }
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
