import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { CarteProfessionnelle, StatutCarteProfessionnelle } from '../../../core/models/carte-professionnelle.model';
import { Personnel } from '../../../core/models/personnel.model';
import { PersonnelApiService } from '../../personnel/personnel-api.service';
import { CarteProApiService } from '../carte-pro-api.service';

@Component({
  selector: 'app-carte-pro-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './carte-pro-form.component.html',
})
export class CarteProFormComponent implements OnInit {
  carteId: number | null = null;
  personnels: Personnel[] = [];
  loading = true;
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    personnel: [null as number | null, Validators.required],
    numero: ['', Validators.required],
    dateDelivrance: ['', Validators.required],
    statut: ['valide' as StatutCarteProfessionnelle, Validators.required],
    observations: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: CarteProApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    this.carteId = idParam ? Number(idParam) : null;

    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));

    if (this.carteId) {
      this.form.controls.personnel.disable();
      this.api.getOne(this.carteId).subscribe({
        next: (carte) => {
          this.form.patchValue({
            personnel: typeof carte.personnel === 'string' ? null : (carte.personnel.id ?? null),
            numero: carte.numero,
            dateDelivrance: carte.dateDelivrance?.substring(0, 10) ?? '',
            statut: carte.statut,
            observations: carte.observations ?? '',
          });
          this.loading = false;
        },
        error: () => {
          this.error = 'Impossible de charger cette carte.';
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
    const payload: CarteProfessionnelle = {
      personnel: `/api/personnels/${raw.personnel}`,
      numero: raw.numero,
      dateDelivrance: raw.dateDelivrance,
      statut: raw.statut,
      observations: raw.observations || null,
    };

    this.saving = true;
    const request = this.carteId
      ? this.api.update(this.carteId, payload)
      : this.api.create(payload);

    request.subscribe({
      next: (carte) => this.router.navigate(['/cartes-professionnelles', carte.id]),
      error: () => {
        this.saving = false;
        this.error = "Erreur lors de l'enregistrement.";
      },
    });
  }
}
