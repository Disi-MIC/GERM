import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ChangementCartouche } from '../../../core/models/changement-cartouche.model';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { AgentOuServiceFiltreComponent } from '../../../shared/agent-ou-service-filtre/agent-ou-service-filtre.component';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { CartouchesInformatiqueApiService } from '../cartouches-informatique-api.service';

const LABELS_COULEUR: Record<string, string> = { noir: 'Noir', cyan: 'Cyan', magenta: 'Magenta', jaune: 'Jaune' };

/** Un changement enrichi du nombre de jours écoulés depuis le précédent changement de même couleur, sur la même imprimante. */
interface ChangementAvecEcoulement extends ChangementCartouche {
  joursEcoulement: number | null;
}

@Component({
  selector: 'app-cartouches-informatique-list',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, DataTableComponent, DataTableCellDirective, AgentOuServiceFiltreComponent],
  templateUrl: './cartouches-informatique-list.component.html',
})
export class CartouchesInformatiqueListComponent implements OnInit {
  tousLesChangements: ChangementAvecEcoulement[] = [];
  filtreServiceId: number | null = null;
  loading = true;
  error: string | null = null;

  readonly columns: DataTableColumn<ChangementAvecEcoulement>[] = [
    { key: 'materiel', label: 'Imprimante', sortable: true, value: (c) => this.materielLabel(c) },
    { key: 'couleur', label: 'Couleur', sortable: true, value: (c) => this.couleurLabel(c) },
    { key: 'reference', label: 'Référence', sortable: true, value: (c) => c.reference ?? '' },
    { key: 'enregistrePar', label: 'Enregistré par', sortable: true, value: (c) => c.enregistreParNom ?? '' },
    { key: 'date', label: 'Date', sortable: true, value: (c) => c.dateChangement },
    { key: 'ecoulement', label: 'Écoulement', sortable: true, value: (c) => c.joursEcoulement ?? -1 },
    { key: 'actions', label: 'Actions', align: 'end', alwaysVisible: true },
  ];

  constructor(private readonly api: CartouchesInformatiqueApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (changements) => {
        this.tousLesChangements = this.avecEcoulement(changements);
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le journal des cartouches.';
        this.loading = false;
      },
    });
  }

  /** Réduit l'historique au service choisi via le filtre agent/service (MaterielInformatique::$service, déjà le service effectif — voir AgentOuServiceFiltreComponent). */
  get changements(): ChangementAvecEcoulement[] {
    if (this.filtreServiceId === null) {
      return this.tousLesChangements;
    }
    return this.tousLesChangements.filter((c) => this.serviceId(c) === this.filtreServiceId);
  }

  /** Taux de consommation du périmètre affiché : nombre de changements et durée moyenne entre deux changements d'une même couleur/imprimante. */
  get nombreChangements(): number {
    return this.changements.length;
  }

  get ecoulementMoyenJours(): number | null {
    const valeurs = this.changements.map((c) => c.joursEcoulement).filter((j): j is number => j !== null);
    if (valeurs.length === 0) {
      return null;
    }
    return Math.round(valeurs.reduce((total, j) => total + j, 0) / valeurs.length);
  }

  onFiltreServiceChange(serviceId: number | null): void {
    this.filtreServiceId = serviceId;
  }

  /**
   * Nombre de jours écoulés depuis le changement précédent de la même couleur
   * sur la même imprimante — c'est tout l'intérêt de ce journal (savoir combien
   * de temps tient une cartouche). Calculé sur l'historique complet (avant tout
   * filtre agent/service) pour rester correct même quand la vue est réduite.
   */
  private avecEcoulement(changements: ChangementCartouche[]): ChangementAvecEcoulement[] {
    const parGroupe = new Map<string, ChangementCartouche[]>();
    for (const c of changements) {
      const cle = `${this.materielId(c)}::${c.couleur}`;
      const groupe = parGroupe.get(cle) ?? [];
      groupe.push(c);
      parGroupe.set(cle, groupe);
    }

    const joursParId = new Map<number, number | null>();
    for (const groupe of parGroupe.values()) {
      const trie = [...groupe].sort((a, b) => a.dateChangement.localeCompare(b.dateChangement));
      for (let i = 0; i < trie.length; i++) {
        const actuel = trie[i];
        if (actuel.id === undefined) {
          continue;
        }
        if (i === 0) {
          joursParId.set(actuel.id, null);
          continue;
        }
        const precedent = trie[i - 1];
        const jours = Math.round(
          (new Date(actuel.dateChangement).getTime() - new Date(precedent.dateChangement).getTime()) / 86_400_000,
        );
        joursParId.set(actuel.id, jours);
      }
    }

    return changements.map((c) => ({ ...c, joursEcoulement: c.id !== undefined ? (joursParId.get(c.id) ?? null) : null }));
  }

  private materielId(changement: ChangementCartouche): number | string {
    const materiel = changement.materiel as MaterielInformatique | string;
    return typeof materiel === 'string' ? materiel : (materiel.id ?? '');
  }

  private serviceId(changement: ChangementCartouche): number | null {
    const materiel = changement.materiel as MaterielInformatique | string;
    if (typeof materiel === 'string' || !materiel.service || typeof materiel.service === 'string') {
      return null;
    }
    return materiel.service.id;
  }

  materielLabel(changement: ChangementCartouche): string {
    const materiel = changement.materiel as MaterielInformatique | string;
    return typeof materiel === 'string' ? materiel : `${materiel.marque} ${materiel.modele} (${materiel.numeroInventaire})`;
  }

  couleurLabel(changement: ChangementCartouche): string {
    return LABELS_COULEUR[changement.couleur] ?? changement.couleur;
  }

  supprimer(changement: ChangementCartouche): void {
    if (!changement.id) {
      return;
    }
    if (!confirm('Supprimer cette entrée du journal des cartouches ?')) {
      return;
    }
    this.api.delete(changement.id).subscribe({
      next: () => {
        this.tousLesChangements = this.tousLesChangements.filter((c) => c.id !== changement.id);
      },
      error: () => {
        this.error = 'Erreur lors de la suppression.';
      },
    });
  }
}
