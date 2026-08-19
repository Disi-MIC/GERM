import { SlicePipe } from '@angular/common';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { Subscription, filter, interval } from 'rxjs';
import { AdminAccessService } from '../../core/admin-access.service';
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
    private readonly adminAccess: AdminAccessService,
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
    this.notifications.effacerBadge();
    this.adminAccess.verrouiller();
    const versLogin = () => this.router.navigateByUrl('/login');
    this.auth.logout().subscribe({ next: versLogin, error: versLogin });
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
      this.auth.hasRole('ROLE_IT_RESPONSABLE') ||
      this.auth.hasRole('ROLE_DIRECTION_MINISTERIELLE')
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

  /**
   * Groupe de sous-menu actuellement actif, pour ouvrir automatiquement le
   * bon dropdown façon Gentelella à la navigation — même logique que les
   * variables `_active` du sidebar Twig (base.html.twig), calculée côté
   * client puisqu'il n'y a pas de re-rendu serveur par page ici. Bootstrap
   * garde ensuite la main sur la classe "show" tant que la route ne change
   * pas (Angular ne réécrit la classe que si la valeur retournée change).
   */
  groupePersonnelActif(): boolean {
    const url = this.router.url;
    return url.startsWith('/personnel') || url.startsWith('/carrieres') || url.startsWith('/documents-administratifs');
  }

  groupeCongesActif(): boolean {
    return this.router.url.startsWith('/conges');
  }

  groupeCartesProActif(): boolean {
    return this.router.url.startsWith('/cartes-professionnelles');
  }

  groupeInformatiqueActif(): boolean {
    const url = this.router.url;
    return (
      url.startsWith('/materiel-informatique') ||
      url.startsWith('/tickets-informatique') ||
      url.startsWith('/maintenance-informatique') ||
      url.startsWith('/licences-logicielles')
    );
  }

  /** Initiales pour l'avatar du menu utilisateur (topbar) — vide si pas de nom disponible. */
  initiales(): string {
    const user = this.auth.currentUser();
    if (!user) {
      return '';
    }
    return `${user.prenom.charAt(0)}${user.nom.charAt(0)}`.toUpperCase();
  }
}
