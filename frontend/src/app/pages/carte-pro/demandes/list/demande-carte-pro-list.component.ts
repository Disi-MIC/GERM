import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../../core/auth.service';
import { CarteProfessionnelle } from '../../../../core/models/carte-professionnelle.model';
import { DemandeCartePro } from '../../../../core/models/demande-carte-pro.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../../shared/data-table/data-table.component';
import { CarteProApiService } from '../../carte-pro-api.service';
import { DemandeCarteProApiService } from '../demande-carte-pro-api.service';

const LABELS_STATUT: Record<string, string> = {
  en_attente: 'En attente',
  transmise: 'Transmise au RH Admin',
  approuvee: 'Approuvée',
  refusee: 'Refusée',
};

const LABELS_TYPE: Record<string, string> = {
  nouvelle: 'Nouvelle carte',
  renouvellement: 'Renouvellement',
  perte_vol: 'Perte ou vol',
};

@Component({
  selector: 'app-demande-carte-pro-list',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './demande-carte-pro-list.component.html',
})
export class DemandeCarteProListComponent implements OnInit {
  demandes: DemandeCartePro[] = [];
  demandesAffichees: DemandeCartePro[] = [];
  loading = true;
  error: string | null = null;
  demandeSelectionnee: DemandeCartePro | null = null;
  filtreStatut: string | null = null;
  compteurs: Record<string, number> = {};
  readonly labelsStatut = LABELS_STATUT;
  readonly labelsType = LABELS_TYPE;
  readonly statuts = Object.keys(LABELS_STATUT);

  readonly columns: DataTableColumn<DemandeCartePro>[] = [
    { key: 'agent', label: 'Agent', sortable: true, value: (d) => this.agentLabel(d) },
    { key: 'type', label: 'Type', sortable: true, value: (d) => this.labelsType[d.typeDemande] ?? d.typeDemande },
    { key: 'statut', label: 'Statut', sortable: true, value: (d) => this.labelsStatut[d.statut ?? ''] ?? '' },
    { key: 'creeeLe', label: 'Créée le', sortable: true, value: (d) => d.createdAt },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(
    private readonly api: DemandeCarteProApiService,
    private readonly carteApi: CarteProApiService,
    readonly auth: AuthService,
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  private charger(): void {
    this.api.getAll().subscribe({
      next: (demandes) => {
        this.demandes = demandes;
        this.recalculerCompteurs();
        this.appliquerFiltre();
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les demandes de carte professionnelle.';
        this.loading = false;
      },
    });
  }

  private recalculerCompteurs(): void {
    const compteurs: Record<string, number> = {};
    for (const statut of this.statuts) {
      compteurs[statut] = 0;
    }
    for (const demande of this.demandes) {
      const statut = demande.statut ?? '';
      compteurs[statut] = (compteurs[statut] ?? 0) + 1;
    }
    this.compteurs = compteurs;
  }

  private appliquerFiltre(): void {
    this.demandesAffichees = this.filtreStatut
      ? this.demandes.filter((d) => d.statut === this.filtreStatut)
      : this.demandes;
  }

  /**
   * Les pastilles "En attente"/"Transmise" résument ce qui reste à traiter
   * (les tâches en cours du RH Carte Pro/RH Admin), pas juste un décompte —
   * cliquer filtre directement la liste sur ce statut.
   */
  filtrer(statut: string): void {
    this.filtreStatut = this.filtreStatut === statut ? null : statut;
    this.appliquerFiltre();
  }

  agentLabel(demande: DemandeCartePro): string {
    if (typeof demande.personnel === 'string') {
      return demande.personnel;
    }
    const personnel: Personnel = demande.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  badgeClass(statut: string | undefined): string {
    switch (statut) {
      case 'approuvee':
        return 'success';
      case 'refusee':
        return 'danger';
      case 'transmise':
        return 'info';
      default:
        return 'secondary';
    }
  }

  selectionnerDemande(demande: DemandeCartePro): void {
    this.demandeSelectionnee = demande;
  }

  carteDe(demande: DemandeCartePro | null): CarteProfessionnelle | null {
    const carte = demande?.carteCreee;
    return carte && typeof carte !== 'string' ? carte : null;
  }

  telechargerUrl(id: number): string {
    return this.carteApi.telechargerUrl(id);
  }

  /** Transmettre/Rejeter (sur une demande en attente) : réservé au profil RH Carte Pro uniquement, pas au RH Admin. */
  peutTransmettreOuRejeter(demande: DemandeCartePro): boolean {
    return !!demande.enAttente && this.auth.hasRole('ROLE_RH_CARTE_PRO');
  }

  /** Approuver/Rejeter (sur une demande transmise) : réservé au RH Admin, passe par la page dédiée (nécessite numéro/date). */
  peutTraiterTransmise(demande: DemandeCartePro): boolean {
    return !!demande.transmise && this.auth.hasRole('ROLE_RH_RESPONSABLE');
  }
}
