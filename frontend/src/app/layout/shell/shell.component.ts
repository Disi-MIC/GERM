import { SlicePipe } from '@angular/common';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { Subscription, filter, interval } from 'rxjs';
import { AuthService } from '../../core/auth.service';
import { administrationLandingUrl } from '../../core/guards/home.guard';
import { NotificationItem } from '../../core/models/notification.model';
import { NotificationService } from '../../core/notification.service';

const INTERVALLE_RAFRAICHISSEMENT_NOTIFICATIONS_MS = 60_000;

@Component({
  selector: 'app-shell',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, SlicePipe],
  templateUrl: './shell.component.html',
  styleUrl: './shell.component.scss',
})
export class ShellComponent implements OnInit, OnDestroy {
  private readonly subscriptions = new Subscription();

  constructor(
    readonly auth: AuthService,
    readonly notifications: NotificationService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.notifications.charger();

    // Rafraîchit la cloche/les badges après chaque navigation (ex. après avoir
    // traité une demande) et périodiquement, pour repérer les notifications
    // créées par d'autres comptes sans devoir recharger la page.
    this.subscriptions.add(
      this.router.events.pipe(filter((event) => event instanceof NavigationEnd)).subscribe(() => this.notifications.charger()),
    );
    this.subscriptions.add(interval(INTERVALLE_RAFRAICHISSEMENT_NOTIFICATIONS_MS).subscribe(() => this.notifications.charger()));
  }

  ngOnDestroy(): void {
    this.subscriptions.unsubscribe();
  }

  logout(): void {
    this.auth.logout().subscribe(() => this.router.navigateByUrl('/login'));
  }

  ouvrirNotification(notification: NotificationItem): void {
    if (!notification.lu) {
      this.notifications.marquerLue(notification.id);
    }
    if (notification.lien) {
      this.router.navigateByUrl(notification.lien);
    }
  }

  toutMarquerLu(): void {
    this.notifications.marquerToutesLues();
  }

  /** Badge d'une rubrique du menu (voir NotificationService.compteurPour). */
  compteurNotifications(prefixeRoute: string): number {
    return this.notifications.compteurPour(prefixeRoute);
  }

  /** Un agent RH (au moins un rôle métier) a deux espaces à basculer ; un agent simple n'a que "Mon espace". */
  aAccesAdministration(): boolean {
    return (
      this.auth.hasRole('ROLE_RH_PERSONNEL') ||
      this.auth.hasRole('ROLE_RH_CONGE') ||
      this.auth.hasRole('ROLE_RH_CARTE_PRO') ||
      this.auth.hasRole('ROLE_ADMIN_RH') ||
      this.auth.hasRole('ROLE_IT_STOCK') ||
      this.auth.hasRole('ROLE_IT_TICKETS') ||
      this.auth.hasRole('ROLE_IT_RESPONSABLE')
    );
  }

  /**
   * La vue affichée (sidebar + bouton de bascule) suit la route active plutôt
   * qu'un état séparé à resynchroniser à chaque navigation : tout ce qui
   * n'est pas sous "Mon espace" (mon-espace/* ou /profil) est considéré
   * comme de l'administration.
   */
  estEnAdministration(): boolean {
    const url = this.router.url;
    return !url.startsWith('/mon-espace') && !url.startsWith('/profil');
  }

  basculerVue(): void {
    if (this.estEnAdministration()) {
      this.router.navigateByUrl('/mon-espace/tableau-de-bord');
    } else {
      this.router.navigateByUrl(administrationLandingUrl(this.auth));
    }
  }
}
