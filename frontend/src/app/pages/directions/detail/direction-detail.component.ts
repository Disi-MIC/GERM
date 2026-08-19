import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Direction } from '../../../core/models/direction.model';
import { Personnel } from '../../../core/models/personnel.model';
import { Service } from '../../../core/models/service.model';
import { PersonnelApiService } from '../../personnel/personnel-api.service';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../shared/searchable-select/searchable-select.component';
import { ServicesApiService } from '../../services/services-api.service';
import { DirectionsApiService } from '../directions-api.service';

/**
 * Agents éligibles au poste de directeur : ceux d'un service rattaché à
 * cette direction (voir DirectionType::directeur côté Twig — même règle,
 * un directeur vient forcément de la direction qu'il dirigera).
 */
@Component({
  selector: 'app-direction-detail',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent, SearchableSelectComponent],
  templateUrl: './direction-detail.component.html',
})
export class DirectionDetailComponent implements OnInit {
  directionId: number | null = null;
  services: Service[] = [];
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
    directeur: [null as number | null],
    noteServiceNumero: [''],
    noteServiceDate: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: DirectionsApiService,
    private readonly servicesApi: ServicesApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    this.directionId = idParam ? Number(idParam) : null;

    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));

    if (this.directionId) {
      this.servicesApi.getAll().subscribe((services) => {
        this.services = services.filter((s) => this.directionIdDe(s) === this.directionId);
      });

      this.api.getOne(this.directionId).subscribe({
        next: (direction) => {
          this.form.patchValue({
            code: direction.code,
            nom: direction.nom,
            description: direction.description ?? '',
            actif: direction.actif,
            directeur: this.idDepuisIri(direction.directeur),
            noteServiceNumero: direction.noteServiceNumero ?? '',
            noteServiceDate: direction.noteServiceDate?.substring(0, 10) ?? '',
          });
          this.hasNoteServiceFichier = direction.hasNoteServiceFichier ?? false;
          this.loading = false;
        },
        error: () => {
          this.error = 'Impossible de charger cette direction.';
          this.loading = false;
        },
      });
    } else {
      this.loading = false;
    }
  }

  private directionIdDe(service: Service): number | null {
    if (!service.direction || typeof service.direction === 'string') {
      return null;
    }
    return service.direction.id;
  }

  /** `directeur` est renvoyé en IRI (ApiProperty readableLink: false côté entité, voir Direction::$directeur) — jamais en objet imbriqué. */
  private idDepuisIri(iri: string | { id: number } | null | undefined): number | null {
    if (!iri) {
      return null;
    }
    if (typeof iri !== 'string') {
      return iri.id;
    }
    const id = Number(iri.split('/').pop());
    return Number.isFinite(id) ? id : null;
  }

  get agentsEligibles(): SearchableSelectOption[] {
    const idsServices = new Set(this.services.map((s) => s.id));
    return this.personnels
      .filter((p) => {
        const service = p.service;
        const serviceId = service && typeof service !== 'string' ? service.id : null;
        return serviceId !== null && idsServices.has(serviceId);
      })
      .map((p) => ({ value: p.id, label: p.nomComplet ?? `${p.prenom} ${p.nom}` }));
  }

  noteServiceUrl(): string {
    return this.directionId ? this.api.noteServiceUrl(this.directionId) : '';
  }

  onNoteServiceFichierChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const fichier = input.files?.[0];
    if (!fichier || !this.directionId) {
      return;
    }

    this.uploadingNoteService = true;
    this.api.uploadNoteService(this.directionId, fichier).subscribe({
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
    const payload: Direction = {
      code: raw.code,
      nom: raw.nom,
      description: raw.description || null,
      actif: raw.actif,
      directeur: raw.directeur ? `/api/personnels/${raw.directeur}` : null,
      noteServiceNumero: raw.noteServiceNumero || null,
      noteServiceDate: raw.noteServiceDate || null,
    };

    this.saving = true;
    const request = this.directionId ? this.api.update(this.directionId, payload) : this.api.create(payload);

    request.subscribe({
      next: () => this.router.navigateByUrl('/directions'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }
}
