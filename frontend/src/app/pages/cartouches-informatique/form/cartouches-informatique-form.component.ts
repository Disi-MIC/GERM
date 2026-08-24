import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ChangementCartouche, CouleurCartouche } from '../../../core/models/changement-cartouche.model';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { MaterielInformatiqueApiService } from '../../materiel-informatique/materiel-informatique-api.service';
import { AgentOuServiceFiltreComponent } from '../../../shared/agent-ou-service-filtre/agent-ou-service-filtre.component';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../shared/searchable-select/searchable-select.component';
import { CartouchesInformatiqueApiService } from '../cartouches-informatique-api.service';

/** Code ListeValeur (catégorie type-materiel) identifiant une imprimante — voir CODES_CORRECTIFS dans maintenance-informatique-list pour la même convention de correspondance sur `.code`. */
const CODE_TYPE_IMPRIMANTE = 'imprimante';

/** Couleurs CMJN fixes — voir App\Entity\Enum\CouleurCartouche côté serveur. */
const COULEURS: { value: CouleurCartouche; label: string }[] = [
  { value: 'noir', label: 'Noir' },
  { value: 'cyan', label: 'Cyan' },
  { value: 'magenta', label: 'Magenta' },
  { value: 'jaune', label: 'Jaune' },
];

@Component({
  selector: 'app-cartouches-informatique-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent, SearchableSelectComponent, AgentOuServiceFiltreComponent],
  templateUrl: './cartouches-informatique-form.component.html',
})
export class CartouchesInformatiqueFormComponent implements OnInit {
  materiels: MaterielInformatique[] = [];
  filtreServiceId: number | null = null;
  saving = false;
  error: string | null = null;
  readonly couleurs = COULEURS;

  form = this.fb.nonNullable.group({
    materiel: [null as number | null, Validators.required],
    couleur: [null as CouleurCartouche | null, Validators.required],
    reference: [''],
    dateChangement: [new Date().toISOString().slice(0, 10), Validators.required],
    observations: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: CartouchesInformatiqueApiService,
    private readonly materielApi: MaterielInformatiqueApiService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.materielApi.getAll().subscribe((materiels) => (this.materiels = materiels));
  }

  /** Imprimantes uniquement (type.code === 'imprimante'), réduites au service choisi via le filtre agent/service le cas échéant. */
  get materielOptions(): SearchableSelectOption[] {
    return this.imprimantes
      .filter((m) => this.filtreServiceId === null || this.serviceId(m) === this.filtreServiceId)
      .map((m) => ({ value: m.id, label: `${m.marque} ${m.modele} (${m.numeroInventaire})` }));
  }

  private get imprimantes(): MaterielInformatique[] {
    return this.materiels.filter((m) => {
      const type = m.type as ListeValeurRef | string;
      return typeof type !== 'string' && type.code === CODE_TYPE_IMPRIMANTE;
    });
  }

  private serviceId(materiel: MaterielInformatique): number | null {
    return materiel.service && typeof materiel.service !== 'string' ? materiel.service.id : null;
  }

  onFiltreServiceChange(serviceId: number | null): void {
    this.filtreServiceId = serviceId;
    // La sélection précédente peut ne plus correspondre au nouveau filtre — on
    // laisse l'utilisateur re-choisir plutôt que de garder une imprimante
    // silencieusement hors du périmètre affiché.
    this.form.controls.materiel.setValue(null);
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();
    const payload: ChangementCartouche = {
      materiel: `/api/materiels-informatiques/${raw.materiel}`,
      couleur: raw.couleur!,
      reference: raw.reference || null,
      dateChangement: raw.dateChangement,
      observations: raw.observations || null,
    };

    this.saving = true;
    this.api.create(payload).subscribe({
      next: () => this.router.navigateByUrl('/cartouches-informatique'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }
}
