import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Capacitor } from '@capacitor/core';
import { forkJoin } from 'rxjs';
import { CarteProfessionnelle } from '../../../core/models/carte-professionnelle.model';
import { DemandeCartePro } from '../../../core/models/demande-carte-pro.model';
import { NativePdfService } from '../../../core/native-pdf.service';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableMobileItemDirective } from '../../../shared/data-table/data-table-mobile-item.directive';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { ProfilApiService } from '../profil-api.service';

const LABELS_STATUT_CARTE: Record<string, string> = {
  valide: 'Valide',
  perdue: 'Perdue',
  volee: 'Volée',
  annulee: 'Annulée',
};

const LABELS_STATUT_DEMANDE: Record<string, string> = {
  en_attente: 'En attente',
  transmise: 'Transmise au RH Admin',
  approuvee: 'Approuvée',
  refusee: 'Refusée',
};

const LABELS_TYPE_DEMANDE: Record<string, string> = {
  nouvelle: 'Nouvelle carte',
  renouvellement: 'Renouvellement',
  perte_vol: 'Perte ou vol',
};

@Component({
  selector: 'app-ma-carte-professionnelle',
  standalone: true,
  imports: [SlicePipe, RouterLink, PageHeaderComponent, DataTableComponent, DataTableCellDirective, DataTableMobileItemDirective],
  templateUrl: './ma-carte-professionnelle.component.html',
})
export class MaCarteProfessionnelleComponent implements OnInit {
  readonly estNatif = Capacitor.isNativePlatform();
  cartes: CarteProfessionnelle[] = [];
  demandes: DemandeCartePro[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsStatutCarte = LABELS_STATUT_CARTE;
  readonly labelsStatutDemande = LABELS_STATUT_DEMANDE;
  readonly labelsTypeDemande = LABELS_TYPE_DEMANDE;

  readonly colonnesCartes: DataTableColumn<CarteProfessionnelle>[] = [
    { key: 'numero', label: 'Numéro', sortable: true, value: (c) => c.numero },
    { key: 'delivrance', label: 'Délivrance', sortable: true, value: (c) => c.dateDelivrance },
    { key: 'expiration', label: 'Expiration', sortable: true, value: (c) => c.dateExpiration ?? '' },
    { key: 'statut', label: 'Statut', sortable: true, value: (c) => this.labelsStatutCarte[c.statut] ?? c.statut },
    { key: 'action', label: 'Action', align: 'end', alwaysVisible: true },
  ];

  readonly colonnesDemandes: DataTableColumn<DemandeCartePro>[] = [
    { key: 'type', label: 'Type', sortable: true, value: (d) => this.labelsTypeDemande[d.typeDemande] ?? d.typeDemande },
    { key: 'statut', label: 'Statut', sortable: true, value: (d) => this.labelsStatutDemande[d.statut ?? ''] ?? '' },
    { key: 'creeeLe', label: 'Créée le', sortable: true, value: (d) => d.createdAt },
    { key: 'traiteeLe', label: 'Traitée le', sortable: true, value: (d) => d.dateTraitement ?? '' },
    { key: 'commentaire', label: 'Commentaire', sortable: false, value: (d) => d.commentaireTraitement ?? '' },
  ];

  telechargementEnCoursId: number | null = null;
  erreurTelechargement: string | null = null;

  constructor(
    private readonly api: ProfilApiService,
    private readonly nativePdf: NativePdfService,
  ) {}

  ngOnInit(): void {
    forkJoin({
      cartes: this.api.getMesCartesProfessionnelles(),
      demandes: this.api.getMesDemandesCartePro(),
    }).subscribe({
      next: ({ cartes, demandes }) => {
        this.cartes = cartes;
        this.demandes = demandes;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger vos cartes professionnelles.';
        this.loading = false;
      },
    });
  }

  badgeClasseCarte(statut: string): string {
    return statut === 'valide' ? 'success' : 'danger';
  }

  cartePdfTelechargerUrl(id: number): string {
    return this.api.cartePdfTelechargerUrl(id);
  }

  /**
   * Sur natif, un <a target="_blank"> n'ouvre rien de perceptible (pas
   * d'onglet dans WKWebView) — on passe par NativePdfService, seul moyen
   * d'ouvrir un PDF authentifié sur natif (voir ce service pour le détail :
   * Browser.open()/SFSafariViewController ne partage pas le cookie de
   * session de la WebView).
   */
  async telecharger(id: number): Promise<void> {
    if (this.telechargementEnCoursId !== null) {
      return;
    }
    this.telechargementEnCoursId = id;
    this.erreurTelechargement = null;
    const carte = this.cartes.find((c) => c.id === id);
    try {
      await this.nativePdf.ouvrir(this.cartePdfTelechargerUrl(id), `Carte professionnelle ${carte?.numero ?? id}.pdf`);
    } catch {
      this.erreurTelechargement = "Impossible d'ouvrir cette carte pour le moment.";
    } finally {
      this.telechargementEnCoursId = null;
    }
  }

  badgeClasseDemande(statut: string | undefined): string {
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
}
