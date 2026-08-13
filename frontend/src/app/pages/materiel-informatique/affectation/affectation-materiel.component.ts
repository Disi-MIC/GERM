import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef, Personnel, ServiceRef } from '../../../core/models/personnel.model';
import { PersonnelApiService } from '../../personnel/personnel-api.service';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { MaterielInformatiqueApiService } from '../materiel-informatique-api.service';

type Filtre = 'tous' | 'non_affectes' | 'affectes';

@Component({
  selector: 'app-affectation-materiel',
  standalone: true,
  imports: [FormsModule, PageHeaderComponent, PanelComponent],
  templateUrl: './affectation-materiel.component.html',
})
export class AffectationMaterielComponent implements OnInit {
  materiels: MaterielInformatique[] = [];
  materielsAffiches: MaterielInformatique[] = [];
  personnels: Personnel[] = [];
  loading = true;
  error: string | null = null;
  filtre: Filtre = 'non_affectes';
  compteurs: Record<Filtre, number> = { tous: 0, non_affectes: 0, affectes: 0 };
  /** Sélection en cours dans le <select> de chaque ligne, avant clic sur "Affecter". */
  selections: Record<number, number | null> = {};
  saving: Record<number, boolean> = {};

  constructor(
    private readonly api: MaterielInformatiqueApiService,
    private readonly personnelApi: PersonnelApiService,
  ) {}

  ngOnInit(): void {
    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));

    this.api.getAll().subscribe({
      next: (materiels) => {
        this.materiels = materiels;
        for (const materiel of materiels) {
          if (materiel.id) {
            this.selections[materiel.id] = this.affecteId(materiel);
          }
        }
        this.recalculer();
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le parc informatique.';
        this.loading = false;
      },
    });
  }

  private affecteId(materiel: MaterielInformatique): number | null {
    const personnel = materiel.affecteA as Personnel | string | null | undefined;
    return personnel && typeof personnel !== 'string' ? (personnel.id ?? null) : null;
  }

  private recalculer(): void {
    const affectes = this.materiels.filter((m) => this.affecteId(m) !== null).length;
    this.compteurs = {
      tous: this.materiels.length,
      affectes,
      non_affectes: this.materiels.length - affectes,
    };
    this.appliquerFiltre();
  }

  private appliquerFiltre(): void {
    this.materielsAffiches =
      this.filtre === 'tous'
        ? this.materiels
        : this.materiels.filter((m) => (this.filtre === 'affectes') === (this.affecteId(m) !== null));
  }

  filtrer(filtre: Filtre): void {
    this.filtre = filtre;
    this.appliquerFiltre();
  }

  libelle(ref: ListeValeurRef | string | undefined): string {
    return ref && typeof ref !== 'string' ? ref.libelle : '';
  }

  serviceLabel(materiel: MaterielInformatique): string {
    const service = materiel.service as ServiceRef | string;
    return typeof service === 'string' ? service : service.nom;
  }

  affecteLabel(materiel: MaterielInformatique): string {
    const personnel = materiel.affecteA as Personnel | string | null | undefined;
    if (!personnel) {
      return 'En stock (non affecté)';
    }
    return typeof personnel === 'string' ? personnel : (personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`);
  }

  /** Désactive le bouton "Affecter" tant que la sélection n'a pas changé par rapport à l'affectation actuelle. */
  selectionInchangee(materiel: MaterielInformatique): boolean {
    if (!materiel.id) {
      return true;
    }
    return (this.selections[materiel.id] ?? null) === this.affecteId(materiel);
  }

  affecter(materiel: MaterielInformatique): void {
    if (!materiel.id) {
      return;
    }
    const id = materiel.id;
    const personnelId = this.selections[id] ?? null;
    this.saving[id] = true;
    this.error = null;
    this.api.affecter(id, personnelId).subscribe({
      next: (miseAJour) => {
        const index = this.materiels.findIndex((m) => m.id === id);
        if (index !== -1) {
          this.materiels[index] = miseAJour;
        }
        this.saving[id] = false;
        this.recalculer();
      },
      error: () => {
        this.saving[id] = false;
        this.error = "Erreur lors de l'affectation.";
      },
    });
  }
}
