import { KeyValuePipe, SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { DashboardMe, DemandeEnAttenteResume, DomaineDemande } from '../../../core/models/dashboard-me.model';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { StatTileComponent } from '../../../shared/stat-tile/stat-tile.component';
import { ProfilApiService } from '../profil-api.service';

const LABELS_STATUT: Record<string, string> = {
  en_attente: 'En attente',
  transmise: 'Transmise au RH Admin',
  approuvee: 'Approuvée',
  refusee: 'Refusée',
};

const ROUTES_DOMAINE: Record<DomaineDemande, string> = {
  carte_pro: '/mon-espace/carte-professionnelle',
  decision: '/mon-espace/conges',
  jouissance: '/mon-espace/conges',
};

const COULEURS_REPARTITION: Record<string, string> = {
  en_attente: 'secondary',
  transmise: 'info',
  approuvee: 'success',
  refusee: 'danger',
};

@Component({
  selector: 'app-mon-tableau-de-bord',
  standalone: true,
  imports: [SlicePipe, KeyValuePipe, RouterLink, FormsModule, StatTileComponent, PanelComponent],
  templateUrl: './mon-tableau-de-bord.component.html',
})
export class MonTableauDeBordComponent implements OnInit {
  data: DashboardMe | null = null;
  loading = true;
  error: string | null = null;
  filtreDomaine: DomaineDemande | 'toutes' = 'toutes';
  readonly labelsStatut = LABELS_STATUT;
  readonly couleursRepartition = COULEURS_REPARTITION;

  constructor(private readonly api: ProfilApiService) {}

  ngOnInit(): void {
    this.api.getMonTableauDeBord().subscribe({
      next: (data) => {
        this.data = data;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger votre tableau de bord.';
        this.loading = false;
      },
    });
  }

  get demandesFiltrees(): DemandeEnAttenteResume[] {
    if (!this.data) {
      return [];
    }
    if (this.filtreDomaine === 'toutes') {
      return this.data.demandesEnAttente;
    }
    return this.data.demandesEnAttente.filter((d) => d.domaine === this.filtreDomaine);
  }

  routeDemande(demande: DemandeEnAttenteResume): string {
    return ROUTES_DOMAINE[demande.domaine];
  }

  totalDemandes(): number {
    if (!this.data) {
      return 0;
    }
    const r = this.data.repartitionDemandes;
    return r.en_attente + r.transmise + r.approuvee + r.refusee;
  }

  pourcentage(valeur: number): number {
    const total = this.totalDemandes();
    return total > 0 ? Math.round((valeur / total) * 100) : 0;
  }
}
