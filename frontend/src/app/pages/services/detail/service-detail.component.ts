import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Personnel } from '../../../core/models/personnel.model';
import { Service } from '../../../core/models/service.model';
import { DirectionsApiService } from '../../directions/directions-api.service';
import { Direction } from '../../../core/models/direction.model';
import { PersonnelApiService } from '../../personnel/personnel-api.service';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../shared/searchable-select/searchable-select.component';
import { ServicesApiService } from '../services-api.service';

/** Agents éligibles au poste de responsable : ceux déjà affectés à ce service précis (voir ServiceType::responsable côté Twig — même règle). */
@Component({
  selector: 'app-service-detail',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent, SearchableSelectComponent],
  templateUrl: './service-detail.component.html',
})
export class ServiceDetailComponent implements OnInit {
  serviceId: number | null = null;
  directions: Direction[] = [];
  personnels: Personnel[] = [];
  loading = true;
  saving = false;
  uploadingNoteService = false;
  hasNoteServiceFichier = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    code: ['', Validators.required],
    nom: ['', Validators.required],
    description: [''],
    actif: [true],
    direction: [null as number | null, Validators.required],
    responsable: [null as number | null],
    noteServiceNumero: [''],
    noteServiceDate: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: ServicesApiService,
    private readonly directionsApi: DirectionsApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    this.serviceId = idParam ? Number(idParam) : null;

    this.directionsApi.getAll().subscribe((directions) => (this.directions = directions));
    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));

    if (this.serviceId) {
      this.api.getOne(this.serviceId).subscribe({
        next: (service) => {
          this.form.patchValue({
            code: service.code,
            nom: service.nom,
            description: service.description ?? '',
            actif: service.actif,
            direction: service.direction && typeof service.direction !== 'string' ? service.direction.id : null,
            responsable: service.responsable && typeof service.responsable !== 'string' ? service.responsable.id : null,
            noteServiceNumero: service.noteServiceNumero ?? '',
            noteServiceDate: service.noteServiceDate?.substring(0, 10) ?? '',
          });
          this.hasNoteServiceFichier = service.hasNoteServiceFichier ?? false;
          this.loading = false;
        },
        error: () => {
          this.error = 'Impossible de charger ce service.';
          this.loading = false;
        },
      });
    } else {
      this.loading = false;
    }
  }

  get agentsEligibles(): SearchableSelectOption[] {
    return this.personnels
      .filter((p) => {
        const service = p.service;
        const serviceId = service && typeof service !== 'string' ? service.id : null;
        return serviceId !== null && serviceId === this.serviceId;
      })
      .map((p) => ({ value: p.id, label: p.nomComplet ?? `${p.prenom} ${p.nom}` }));
  }

  noteServiceUrl(): string {
    return this.serviceId ? this.api.noteServiceUrl(this.serviceId) : '';
  }

  onNoteServiceFichierChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const fichier = input.files?.[0];
    if (!fichier || !this.serviceId) {
      return;
    }

    this.uploadingNoteService = true;
    this.api.uploadNoteService(this.serviceId, fichier).subscribe({
      next: () => {
        this.uploadingNoteService = false;
        this.hasNoteServiceFichier = true;
      },
      error: (err) => {
        this.uploadingNoteService = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'envoi de la note de service.";
      },
    });
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();
    const payload: Service = {
      code: raw.code,
      nom: raw.nom,
      description: raw.description || null,
      actif: raw.actif,
      direction: raw.direction ? `/api/directions/${raw.direction}` : null,
      responsable: raw.responsable ? `/api/personnels/${raw.responsable}` : null,
      noteServiceNumero: raw.noteServiceNumero || null,
      noteServiceDate: raw.noteServiceDate || null,
    };

    this.saving = true;
    const request = this.serviceId ? this.api.update(this.serviceId, payload) : this.api.create(payload);

    request.subscribe({
      next: () => this.router.navigateByUrl('/services'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }
}
