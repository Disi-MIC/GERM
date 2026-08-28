import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef, Personnel, ServiceRef } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { badgesAntivirus, COULEURS_ETAT_MATERIEL, ICONES_ETAT_MATERIEL, IconIndicateur } from '../../../shared/materiel/materiel-indicateurs.util';
import { MaterielInformatiqueApiService } from '../materiel-informatique-api.service';

const BADGES_ETAT = COULEURS_ETAT_MATERIEL;

const LABELS_ETAT: Record<string, string> = {
  en_service: 'En service',
  en_stock: 'En stock',
  en_panne: 'En panne',
  en_maintenance: 'En maintenance',
  reforme: 'Réformé',
};

@Component({
  selector: 'app-materiel-informatique-list',
  standalone: true,
  imports: [RouterLink, PageHeaderComponent, DataTableComponent, DataTableCellDirective],
  templateUrl: './materiel-informatique-list.component.html',
})
export class MaterielInformatiqueListComponent implements OnInit {
  materiels: MaterielInformatique[] = [];
  materielsAffiches: MaterielInformatique[] = [];
  loading = true;
  error: string | null = null;
  deleteError: string | null = null;
  filtreEtat: string | null = null;
  compteurs: Record<string, number> = {};
  readonly etats = Object.keys(BADGES_ETAT);
  readonly labelsEtat = LABELS_ETAT;

  readonly columns: DataTableColumn<MaterielInformatique>[] = [
    { key: 'numeroInventaire', label: "N° inventaire", sortable: true, value: (m) => m.numeroInventaire },
    { key: 'numeroSerie', label: 'N° série', sortable: true, value: (m) => m.numeroSerie ?? '' },
    { key: 'type', label: 'Type', sortable: true, value: (m) => this.libelle(m.type) },
    { key: 'marqueModele', label: 'Marque / Modèle', sortable: true, value: (m) => `${m.marque} ${m.modele}` },
    { key: 'service', label: 'Service', sortable: true, value: (m) => this.serviceLabel(m) },
    { key: 'affecteA', label: 'Affecté à', sortable: true, value: (m) => this.affecteLabel(m) },
    { key: 'etat', label: 'État', sortable: true, value: (m) => this.libelle(m.etat) },
    { key: 'antivirus', label: 'Antivirus' },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(private readonly api: MaterielInformatiqueApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (materiels) => {
        this.materiels = materiels;
        this.recalculerCompteurs();
        this.appliquerFiltre();
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le parc informatique.';
        this.loading = false;
      },
    });
  }

  private codeEtat(materiel: MaterielInformatique): string {
    return typeof materiel.etat === 'string' ? '' : materiel.etat.code;
  }

  private recalculerCompteurs(): void {
    const compteurs: Record<string, number> = {};
    for (const etat of this.etats) {
      compteurs[etat] = 0;
    }
    for (const materiel of this.materiels) {
      const code = this.codeEtat(materiel);
      compteurs[code] = (compteurs[code] ?? 0) + 1;
    }
    this.compteurs = compteurs;
  }

  private appliquerFiltre(): void {
    this.materielsAffiches = this.filtreEtat
      ? this.materiels.filter((m) => this.codeEtat(m) === this.filtreEtat)
      : this.materiels;
  }

  filtrer(etat: string): void {
    this.filtreEtat = this.filtreEtat === etat ? null : etat;
    this.appliquerFiltre();
  }

  exportCsvUrl(): string {
    return this.api.exportCsvUrl();
  }

  libelle(ref: ListeValeurRef | string | undefined): string {
    return ref && typeof ref !== 'string' ? ref.libelle : '';
  }

  /** Type + numéro de poste entre parenthèses pour un téléphone (voir MaterielInformatique::$numeroTelephone côté serveur). */
  typeLabel(materiel: MaterielInformatique): string {
    const libelle = this.libelle(materiel.type);
    const type = materiel.type as ListeValeurRef | string | undefined;
    const estTelephone = type && typeof type !== 'string' && type.code === 'telephone';
    return estTelephone && materiel.numeroTelephone ? `${libelle} (poste ${materiel.numeroTelephone})` : libelle;
  }

  badgeClasseEtat(materiel: MaterielInformatique): string {
    return BADGES_ETAT[this.codeEtat(materiel)] ?? 'secondary';
  }

  iconeEtat(materiel: MaterielInformatique): string {
    return ICONES_ETAT_MATERIEL[this.codeEtat(materiel)] ?? 'question-circle-fill';
  }

  iconesAntivirus(materiel: MaterielInformatique): IconIndicateur[] {
    return badgesAntivirus(materiel);
  }

  serviceLabel(materiel: MaterielInformatique): string {
    const service = materiel.service as ServiceRef | string | null;
    if (!service) {
      return '—';
    }
    return typeof service === 'string' ? service : service.nom;
  }

  affecteLabel(materiel: MaterielInformatique): string {
    const personnel = materiel.affecteA as Personnel | string | null | undefined;
    if (!personnel) {
      return '—';
    }
    return typeof personnel === 'string' ? personnel : (personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`);
  }

  supprimer(materiel: MaterielInformatique): void {
    if (!materiel.id) {
      return;
    }
    if (!confirm('Supprimer ce matériel du parc informatique ? Cette action est irréversible.')) {
      return;
    }
    this.deleteError = null;
    this.api.delete(materiel.id).subscribe({
      next: () => {
        this.materiels = this.materiels.filter((m) => m.id !== materiel.id);
        this.recalculerCompteurs();
        this.appliquerFiltre();
      },
      error: (err) => {
        this.deleteError = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors de la suppression.';
      },
    });
  }
}
