import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { ListeValeurRef, Personnel, ServiceRef } from '../../../core/models/personnel.model';
import { PersonnelApiService } from '../personnel-api.service';

@Component({
  selector: 'app-personnel-detail',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './personnel-detail.component.html',
})
export class PersonnelDetailComponent implements OnInit {
  personnelId: number | null = null;
  services: ServiceRef[] = [];
  typesContrat: ListeValeurRef[] = [];
  loading = true;
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    matricule: ['', Validators.required],
    nom: ['', Validators.required],
    prenom: ['', Validators.required],
    sexe: ['M', Validators.required],
    dateNaissance: [''],
    service: [null as number | null, Validators.required],
    fonction: ['', Validators.required],
    grade: [''],
    typeContrat: [null as number | null, Validators.required],
    dateEmbauche: [''],
    statut: ['actif', Validators.required],
    telephone: [''],
    email: [''],
    adresse: [''],
    observations: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: PersonnelApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    this.personnelId = idParam ? Number(idParam) : null;

    this.api.getServices().subscribe((services) => (this.services = services));
    this.api.getTypesContrat().subscribe((valeurs) => {
      this.typesContrat = valeurs.filter((v) => v.categorie === 'type-contrat');
    });

    if (this.personnelId) {
      this.api.getOne(this.personnelId).subscribe({
        next: (personnel) => {
          this.form.patchValue({
            matricule: personnel.matricule,
            nom: personnel.nom,
            prenom: personnel.prenom,
            sexe: personnel.sexe,
            dateNaissance: personnel.dateNaissance?.substring(0, 10) ?? '',
            service: typeof personnel.service === 'string' ? null : personnel.service.id,
            fonction: personnel.fonction,
            grade: personnel.grade ?? '',
            typeContrat:
              typeof personnel.typeContrat === 'string' ? null : personnel.typeContrat.id,
            dateEmbauche: personnel.dateEmbauche?.substring(0, 10) ?? '',
            statut: personnel.statut,
            telephone: personnel.telephone ?? '',
            email: personnel.email ?? '',
            adresse: personnel.adresse ?? '',
            observations: personnel.observations ?? '',
          });
          this.loading = false;
        },
        error: () => {
          this.error = "Impossible de charger cette fiche.";
          this.loading = false;
        },
      });
    } else {
      this.loading = false;
    }
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();
    const payload: Personnel = {
      matricule: raw.matricule,
      nom: raw.nom,
      prenom: raw.prenom,
      sexe: raw.sexe as 'M' | 'F',
      dateNaissance: raw.dateNaissance || null,
      service: `/api/services/${raw.service}`,
      fonction: raw.fonction,
      grade: raw.grade || null,
      typeContrat: `/api/liste_valeurs/${raw.typeContrat}`,
      dateEmbauche: raw.dateEmbauche || null,
      statut: raw.statut,
      telephone: raw.telephone || null,
      email: raw.email || null,
      adresse: raw.adresse || null,
      observations: raw.observations || null,
    };

    this.saving = true;
    const request = this.personnelId
      ? this.api.update(this.personnelId, payload)
      : this.api.create(payload);

    request.subscribe({
      next: () => this.router.navigateByUrl('/personnel'),
      error: () => {
        this.saving = false;
        this.error = "Erreur lors de l'enregistrement.";
      },
    });
  }
}
