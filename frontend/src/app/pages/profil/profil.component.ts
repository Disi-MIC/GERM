import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Capacitor } from '@capacitor/core';
import { AuthService } from '../../core/auth.service';
import { DashboardMe } from '../../core/models/dashboard-me.model';
import { HistoriqueAffectation, TypeMouvementCarriere } from '../../core/models/historique-affectation.model';
import { Personnel } from '../../core/models/personnel.model';
import { PageHeaderComponent } from '../../shared/page-header/page-header.component';
import { PanelComponent } from '../../shared/panel/panel.component';
import { StatTileComponent } from '../../shared/stat-tile/stat-tile.component';
import { ProfilApiService } from './profil-api.service';

const LABELS_SEXE: Record<string, string> = {
  M: 'Homme',
  F: 'Femme',
};

const LABELS_STATUT: Record<string, string> = {
  actif: 'Actif',
  en_conge: 'En congé',
  suspendu: 'Suspendu',
  retraite: 'Retraité',
  demissionnaire: 'Démissionnaire',
};

const BADGES_STATUT: Record<string, string> = {
  actif: 'success',
  en_conge: 'info',
  suspendu: 'warning',
  retraite: 'secondary',
  demissionnaire: 'danger',
};

const LABELS_MOUVEMENT: Record<TypeMouvementCarriere, string> = {
  nomination: 'Nomination',
  mutation: 'Mutation',
  promotion: 'Promotion',
  autre: 'Mouvement',
};

const ICONES_MOUVEMENT: Record<TypeMouvementCarriere, string> = {
  nomination: 'bi-award',
  mutation: 'bi-arrow-left-right',
  promotion: 'bi-graph-up-arrow',
  autre: 'bi-dot',
};

/** Nombre de mouvements récents affichés dans la frise ; le lien "Voir tout" mène à l'historique complet. */
const NB_MOUVEMENTS_RECENTS = 5;

@Component({
  selector: 'app-profil',
  standalone: true,
  imports: [SlicePipe, RouterLink, PageHeaderComponent, PanelComponent, StatTileComponent],
  templateUrl: './profil.component.html',
  styleUrl: './profil.component.scss',
})
export class ProfilComponent implements OnInit {
  /** Sections en accordéon sur l'app mobile (écran étroit, une rubrique à la fois) ; panneaux dépliés côte à côte sur le web. */
  readonly estNatif = Capacitor.isNativePlatform();
  personnel: Personnel | null = null;
  tableauDeBord: DashboardMe | null = null;
  carriere: HistoriqueAffectation[] = [];
  loading = true;
  error: string | null = null;
  photoEnCours = false;
  erreurPhoto: string | null = null;
  /** Casse le cache navigateur de <img [src]="photoUrl()"> : l'URL de la photo est
   *  toujours la même après un changement, sans ce paramètre l'ancienne image resterait affichée. */
  private photoVersion = 0;
  readonly labelsSexe = LABELS_SEXE;
  readonly labelsStatut = LABELS_STATUT;
  readonly labelsMouvement = LABELS_MOUVEMENT;

  constructor(
    private readonly api: ProfilApiService,
    readonly auth: AuthService,
  ) {}

  ngOnInit(): void {
    this.api.getMonPersonnel().subscribe({
      next: (personnel) => {
        this.personnel = personnel;
        this.loading = false;
      },
      error: (err) => {
        this.error = err?.error?.errors?.personnel ?? 'Impossible de charger votre fiche personnel.';
        this.loading = false;
      },
    });
    this.api.getMonTableauDeBord().subscribe((tableauDeBord) => (this.tableauDeBord = tableauDeBord));
    this.api.getMaCarriere().subscribe((carriere) => (this.carriere = carriere));
  }

  get mouvementsRecents(): HistoriqueAffectation[] {
    return this.carriere.slice(0, NB_MOUVEMENTS_RECENTS);
  }

  photoUrl(): string {
    return `${this.api.photoUrl()}?v=${this.photoVersion}`;
  }

  changerPhoto(input: HTMLInputElement): void {
    const fichier = input.files?.[0];
    input.value = '';
    if (!fichier || !this.personnel) {
      return;
    }
    this.photoEnCours = true;
    this.erreurPhoto = null;
    this.api.uploaderMaPhoto(fichier).subscribe({
      next: (personnel) => {
        this.personnel = personnel;
        this.photoVersion++;
        this.photoEnCours = false;
      },
      error: (err) => {
        this.erreurPhoto = err?.error?.errors?.photoFichier ?? "Impossible de mettre à jour la photo.";
        this.photoEnCours = false;
      },
    });
  }

  initiales(): string {
    if (!this.personnel) {
      return '';
    }
    return `${this.personnel.prenom.charAt(0)}${this.personnel.nom.charAt(0)}`.toUpperCase();
  }

  serviceLabel(): string {
    const service = this.personnel?.service;
    return service && typeof service !== 'string' ? service.nom : '';
  }

  typeContratLabel(): string {
    const type = this.personnel?.typeContrat;
    return type && typeof type !== 'string' ? type.libelle : '';
  }

  badgeClasseStatut(statut: string): string {
    return BADGES_STATUT[statut] ?? 'secondary';
  }

  iconeMouvement(type: TypeMouvementCarriere): string {
    return ICONES_MOUVEMENT[type] ?? 'bi-dot';
  }

  /** Ancienneté en années pleines depuis la date d'embauche, "—" si non renseignée. */
  anciennete(): string {
    if (!this.personnel?.dateEmbauche) {
      return '—';
    }
    const debut = new Date(this.personnel.dateEmbauche);
    const maintenant = new Date();
    let annees = maintenant.getFullYear() - debut.getFullYear();
    const anniversairePasse =
      maintenant.getMonth() > debut.getMonth() || (maintenant.getMonth() === debut.getMonth() && maintenant.getDate() >= debut.getDate());
    if (!anniversairePasse) {
      annees -= 1;
    }
    return annees <= 0 ? '< 1 an' : `${annees} an${annees > 1 ? 's' : ''}`;
  }

  mouvementLabel(entree: HistoriqueAffectation): string {
    const service = entree.service && typeof entree.service !== 'string' ? entree.service.nom : '';
    return `${entree.fonction} — ${service}`;
  }
}
