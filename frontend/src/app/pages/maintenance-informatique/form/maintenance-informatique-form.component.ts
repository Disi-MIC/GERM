import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MaintenancePayload } from '../../../core/models/maintenance.model';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef, Personnel } from '../../../core/models/personnel.model';
import { TicketIncident } from '../../../core/models/ticket-incident.model';
import { MaterielInformatiqueApiService } from '../../materiel-informatique/materiel-informatique-api.service';
import { PersonnelApiService } from '../../personnel/personnel-api.service';
import { TicketsInformatiqueApiService } from '../../tickets-informatique/tickets-informatique-api.service';
import { FilePieceInputComponent } from '../../../shared/file-piece-input/file-piece-input.component';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../shared/searchable-select/searchable-select.component';
import { MaintenanceInformatiqueApiService } from '../maintenance-informatique-api.service';

@Component({
  selector: 'app-maintenance-informatique-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent, SearchableSelectComponent, FilePieceInputComponent],
  templateUrl: './maintenance-informatique-form.component.html',
})
export class MaintenanceInformatiqueFormComponent implements OnInit {
  materiels: MaterielInformatique[] = [];
  personnels: Personnel[] = [];
  tickets: TicketIncident[] = [];
  typesMaintenance: ListeValeurRef[] = [];
  fichier: File | null = null;
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    materiel: [null as number | null, Validators.required],
    type: [null as number | null, Validators.required],
    description: ['', Validators.required],
    dateRealisation: ['', Validators.required],
    realisePar: [null as number | null],
    prestataireExterne: [''],
    ticketOrigine: [null as number | null],
    observations: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: MaintenanceInformatiqueApiService,
    private readonly materielApi: MaterielInformatiqueApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly ticketsApi: TicketsInformatiqueApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.materielApi.getAll().subscribe((materiels) => (this.materiels = materiels));
    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));
    this.ticketsApi.getAll().subscribe((tickets) => (this.tickets = tickets));
    this.personnelApi
      .getTypesContrat()
      .subscribe((valeurs) => (this.typesMaintenance = valeurs.filter((v) => v.categorie === 'type-maintenance')));

    // Préremplissage depuis le lien "Planifier" du tableau de bord (échéance
    // de maintenance préventive) : /maintenance-informatique/new?materiel=id.
    const materielParam = this.route.snapshot.queryParamMap.get('materiel');
    if (materielParam) {
      this.form.patchValue({ materiel: Number(materielParam) });
    }
  }

  get materielOptions(): SearchableSelectOption[] {
    return this.materiels.map((m) => ({ value: m.id, label: this.materielLabel(m) }));
  }

  /**
   * Priorité à l'agent affecté (nom + matricule) plutôt qu'à la marque : dans
   * ce formulaire, le technicien recherche le plus souvent "le poste de tel
   * agent", pas une référence matérielle — le type et le modèle restent
   * affichés pour départager plusieurs postes d'un même agent.
   */
  private materielLabel(m: MaterielInformatique): string {
    const type = typeof m.type === 'string' ? m.type : m.type.libelle;
    const materiel = `${type} ${m.modele}`;

    const agent = m.affecteA;
    if (!agent || typeof agent === 'string') {
      return `${materiel} (${m.numeroInventaire}) — Non affecté`;
    }

    const nomAgent = agent.nomComplet ?? `${agent.prenom} ${agent.nom}`;
    return `${nomAgent} — ${agent.matricule ?? '—'} — ${materiel}`;
  }

  get personnelOptions(): SearchableSelectOption[] {
    return this.personnels.map((p) => ({ value: p.id, label: p.nomComplet ?? p.matricule ?? '' }));
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();
    const payload: MaintenancePayload = {
      materiel: raw.materiel!,
      type: raw.type!,
      description: raw.description,
      dateRealisation: raw.dateRealisation,
      realisePar: raw.realisePar,
      prestataireExterne: raw.prestataireExterne || null,
      ticketOrigine: raw.ticketOrigine,
      observations: raw.observations || null,
      fichier: this.fichier,
    };

    this.saving = true;
    this.api.create(payload).subscribe({
      next: () => this.router.navigateByUrl('/maintenance-informatique'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }
}
