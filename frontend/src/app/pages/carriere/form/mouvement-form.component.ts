import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { HistoriqueAffectation, TypeMouvementCarriere } from '../../../core/models/historique-affectation.model';
import { Personnel, ServiceRef } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../shared/searchable-select/searchable-select.component';
import { PersonnelApiService } from '../../personnel/personnel-api.service';
import { CarriereApiService } from '../carriere-api.service';

@Component({
  selector: 'app-mouvement-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent, SearchableSelectComponent],
  templateUrl: './mouvement-form.component.html',
})
export class MouvementFormComponent implements OnInit {
  personnels: Personnel[] = [];
  services: ServiceRef[] = [];
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    personnel: [null as number | null, Validators.required],
    service: [null as number | null, Validators.required],
    fonction: ['', Validators.required],
    grade: [''],
    typeMouvement: ['nomination' as TypeMouvementCarriere, Validators.required],
    dateEffet: ['', Validators.required],
    numeroDecision: [''],
    dateDecision: [''],
    observations: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: CarriereApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));
    this.personnelApi.getServices().subscribe((services) => (this.services = services));
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
    const payload: HistoriqueAffectation = {
      personnel: `/api/personnels/${raw.personnel}`,
      service: `/api/services/${raw.service}`,
      fonction: raw.fonction,
      grade: raw.grade || null,
      typeMouvement: raw.typeMouvement,
      dateEffet: raw.dateEffet,
      numeroDecision: raw.numeroDecision || null,
      dateDecision: raw.dateDecision || null,
      observations: raw.observations || null,
    };

    this.saving = true;
    this.api.create(payload).subscribe({
      next: () => this.router.navigateByUrl('/carrieres'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }
}
