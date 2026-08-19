import { Location, SlicePipe } from '@angular/common';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { Subscription, filter, interval } from 'rxjs';
import { AdminAccessService } from '../../core/admin-access.service';
import { AuthService } from '../../core/auth.service';
import { administrationLandingUrl } from '../../core/guards/home.guard';
import { NotificationItem } from '../../core/models/notification.model';
import { NotificationService } from '../../core/notification.service';
import { PageTitleService } from '../../core/page-title.service';

const INTERVALLE_RAFRAICHISSEMENT_NOTIFICATIONS_MS = 60_000;

/**
 * Coquille de l'app native (Capacitor) — barre d'onglets en bas façon appli
 * mobile plutôt que la sidebar desktop. Deux vues, comme ShellComponent :
 * « Mon espace » (barre d'onglets Accueil/Congés/Tickets/Profil/Plus) et
 * « Administration » (barre d'onglets Tableau de bord/Menu/Profil/Mon espace,
 * le "Menu" ouvrant une feuille listant toutes les rubriques admin — trop
 * nombreuses pour tenir dans une barre d'onglets). Basculée par route
 * courante (estEnAdministration), jamais par un état séparé à resynchroniser.
 */
@Component({
  selector: 'app-mobile-shell',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, SlicePipe],
  templateUrl: './mobile-shell.component.html',
  styleUrl: './mobile-shell.component.scss',
})
export class MobileShellComponent implements OnInit, OnDestroy {
  menuPlusOuvert = false;
  menuAdminOuvert = false;
  notifOuvert = false;
  private readonly subscriptions = new Subscription();

  /**
   * Racines de navigation : destinations directement accessibles depuis la
   * barre d'onglets (voir template), donc jamais atteintes "en profondeur" —
   * pas de bouton retour dessus, contrairement à tout ce qui s'y ouvre
   * ensuite (détail, formulaire, traitement...). administrationLandingUrl()
   * dépend du rôle, donc recalculée à chaque vérification plutôt que figée ici.
   */
  private readonly racinesFixes = new Set(['/mon-espace/tableau-de-bord', '/mon-espace/conges', '/mon-espace/tickets', '/profil']);

  constructor(
    readonly auth: AuthService,
    private readonly router: Router,
    private readonly location: Location,
    readonly notifications: NotificationService,
    private readonly adminAccess: AdminAccessService,
    readonly pageTitle: PageTitleService,
  ) {}

  ngOnInit(): void {
    this.notifications.charger();

    this.subscriptions.add(
      this.router.events.pipe(filter((event) => event instanceof NavigationEnd)).subscribe(() => {
        this.menuPlusOuvert = false;
        this.menuAdminOuvert = false;
        this.notifOuvert = false;
        this.notifications.charger();
      }),
    );
    this.subscriptions.add(interval(INTERVALLE_RAFRAICHISSEMENT_NOTIFICATIONS_MS).subscribe(() => this.notifications.charger()));
  }

  ngOnDestroy(): void {
    this.subscriptions.unsubscribe();
  }

  compteurNotifications(prefixeRoute: string): number {
    return this.notifications.compteurPour(prefixeRoute);
  }

  basculerPlus(): void {
    this.menuPlusOuvert = !this.menuPlusOuvert;
    this.menuAdminOuvert = false;
    this.notifOuvert = false;
  }

  fermerPlus(): void {
    this.menuPlusOuvert = false;
  }

  basculerMenuAdmin(): void {
    this.menuAdminOuvert = !this.menuAdminOuvert;
    this.menuPlusOuvert = false;
    this.notifOuvert = false;
  }

  fermerMenuAdmin(): void {
    this.menuAdminOuvert = false;
  }

  basculerNotif(): void {
    this.notifOuvert = !this.notifOuvert;
    this.menuPlusOuvert = false;
    this.menuAdminOuvert = false;
  }

  fermerNotif(): void {
    this.notifOuvert = false;
  }

  /** Même logique que ShellComponent.ouvrirNotification(). */
  ouvrirNotification(notification: NotificationItem): void {
    if (!notification.lu) {
      this.notifications.marquerLue(notification.id);
    }
    this.fermerNotif();
    if (notification.lien) {
      this.router.navigateByUrl(notification.lien);
    }
  }

  toutMarquerLu(): void {
    this.notifications.marquerToutesLues();
  }

  /** Même logique que ShellComponent.aAccesAdministration() : au moins un rôle métier RH/IT. */
  aAccesAdministration(): boolean {
    return this.auth.hasAnyRole([
      'ROLE_RH_PERSONNEL',
      'ROLE_RH_CONGE',
      'ROLE_RH_CARTE_PRO',
      'ROLE_ADMIN_RH',
      'ROLE_IT_STOCK',
      'ROLE_IT_TICKETS',
      'ROLE_IT_RESPONSABLE',
      'ROLE_DIRECTION_MINISTERIELLE',
    ]);
  }

  /** Même logique que ShellComponent.estEnAdministration() : dérivée de la route, pas d'état séparé. */
  estEnAdministration(): boolean {
    const url = this.router.url;
    return !url.startsWith('/mon-espace') && !url.startsWith('/profil');
  }

  administrationLandingUrl(): string {
    return administrationLandingUrl(this.auth);
  }

  /** Détermine si l'en-tête affiche le logo (racine) ou une flèche retour (sous-page). */
  estPageRacine(): boolean {
    const chemin = this.router.url.split('?')[0];
    return this.racinesFixes.has(chemin) || chemin === this.administrationLandingUrl();
  }

  retour(): void {
    this.location.back();
  }

  allerVersAdministration(): void {
    this.fermerPlus();
    this.router.navigateByUrl(administrationLandingUrl(this.auth));
  }

  allerVersMonEspace(): void {
    this.fermerMenuAdmin();
    this.router.navigateByUrl('/mon-espace/tableau-de-bord');
  }

  logout(): void {
    this.notifications.effacerBadge();
    this.adminAccess.verrouiller();
    const versLogin = () => this.router.navigateByUrl('/login');
    this.auth.logout().subscribe({ next: versLogin, error: versLogin });
  }
}
