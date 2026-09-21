import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { ListeValeurRef, Personnel, ServiceRef } from '../../../core/models/personnel.model';
import { PersonnelApiService } from '../personnel-api.service';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';

@Component({
  selector: 'app-personnel-detail',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent],
  templateUrl: './personnel-detail.component.html',
})
export class PersonnelDetailComponent implements OnInit {
  personnelId: number | null = null;
  services: ServiceRef[] = [];
  typesContrat: ListeValeurRef[] = [];
  corpsOptions: ListeValeurRef[] = [];
  hierarchieOptions: ListeValeurRef[] = [];
  programmeOptions: ListeValeurRef[] = [];
  chapitreOptions: ListeValeurRef[] = [];
  loading = true;
  saving = false;
  error: string | null = null;
  hasPhoto = false;
  uploadingPhoto = false;

  form = this.fb.nonNullable.group({
    matricule: [''],
    nom: ['', Validators.required],
    prenom: ['', Validators.required],
    sexe: ['M', Validators.required],
    cni: [''],
    dateNaissance: [''],
    service: [null as number | null, Validators.required],
    fonction: ['', Validators.required],
    grade: [''],
    corps: [null as number | null],
    hierarchie: [null as number | null],
    typeContrat: [null as number | null, Validators.required],
    programme: [null as number | null],
    chapitre: [null as number | null],
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
      this.corpsOptions = valeurs.filter((v) => v.categorie === 'corps');
      this.hierarchieOptions = valeurs.filter((v) => v.categorie === 'hierarchie');
      this.programmeOptions = valeurs.filter((v) => v.categorie === 'programme');
      this.chapitreOptions = valeurs.filter((v) => v.categorie === 'chapitre');
    });

    if (this.personnelId) {
      this.api.getOne(this.personnelId).subscribe({
        next: (personnel) => {
          this.form.patchValue({
            matricule: personnel.matricule ?? '',
            nom: personnel.nom,
            prenom: personnel.prenom,
            sexe: personnel.sexe,
            cni: personnel.cni ?? '',
            dateNaissance: personnel.dateNaissance?.substring(0, 10) ?? '',
            service: personnel.service && typeof personnel.service !== 'string' ? personnel.service.id : null,
            fonction: personnel.fonction,
            grade: personnel.grade ?? '',
            corps: personnel.corps && typeof personnel.corps !== 'string' ? personnel.corps.id : null,
            hierarchie: personnel.hierarchie && typeof personnel.hierarchie !== 'string' ? personnel.hierarchie.id : null,
            typeContrat:
              personnel.typeContrat && typeof personnel.typeContrat !== 'string' ? personnel.typeContrat.id : null,
            programme: personnel.programme && typeof personnel.programme !== 'string' ? personnel.programme.id : null,
            chapitre: personnel.chapitre && typeof personnel.chapitre !== 'string' ? personnel.chapitre.id : null,
            dateEmbauche: personnel.dateEmbauche?.substring(0, 10) ?? '',
            statut: personnel.statut,
            telephone: personnel.telephone ?? '',
            email: personnel.email ?? '',
            adresse: personnel.adresse ?? '',
            observations: personnel.observations ?? '',
          });
          this.hasPhoto = personnel.hasPhoto ?? false;
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
      matricule: raw.matricule || null,
      nom: raw.nom,
      prenom: raw.prenom,
      sexe: raw.sexe as 'M' | 'F',
      cni: raw.cni || null,
      dateNaissance: raw.dateNaissance || null,
      service: `/api/services/${raw.service}`,
      fonction: raw.fonction,
      grade: raw.grade || null,
      corps: raw.corps ? `/api/liste_valeurs/${raw.corps}` : null,
      hierarchie: raw.hierarchie ? `/api/liste_valeurs/${raw.hierarchie}` : null,
      typeContrat: `/api/liste_valeurs/${raw.typeContrat}`,
      programme: raw.programme ? `/api/liste_valeurs/${raw.programme}` : null,
      chapitre: raw.chapitre ? `/api/liste_valeurs/${raw.chapitre}` : null,
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

  photoUrl(): string {
    return this.personnelId ? this.api.photoUrl(this.personnelId) : '';
  }

  onPhotoChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const fichier = input.files?.[0];
    if (!fichier || !this.personnelId) {
      return;
    }

    this.uploadingPhoto = true;
    this.api.uploadPhoto(this.personnelId, fichier).subscribe({
      next: () => {
        this.uploadingPhoto = false;
        this.hasPhoto = true;
      },
      error: (err) => {
        this.uploadingPhoto = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'envoi de la photo.";
      },
    });
  }

  supprimer(): void {
    if (!this.personnelId) {
      return;
    }
    if (!confirm('Supprimer cette fiche personnel ? Cette action est irréversible.')) {
      return;
    }

    this.api.delete(this.personnelId).subscribe({
      next: () => this.router.navigateByUrl('/personnel'),
      error: (err) => {
        this.error = err?.error?.errors?.personnel ?? 'Erreur lors de la suppression.';
      },
    });
  }
}
