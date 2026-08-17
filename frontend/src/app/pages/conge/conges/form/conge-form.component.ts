import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Conge, TypeConge } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PersonnelApiService } from '../../../personnel/personnel-api.service';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../../shared/searchable-select/searchable-select.component';
import { CongeApiService } from '../../conge-api.service';

@Component({
  selector: 'app-conge-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent, SearchableSelectComponent],
  templateUrl: './conge-form.component.html',
})
export class CongeFormComponent implements OnInit {
  congeId: number | null = null;
  personnels: Personnel[] = [];
  loading = true;
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    personnel: [null as number | null, Validators.required],
    type: ['annuel' as TypeConge, Validators.required],
    dateDebut: ['', Validators.required],
    dateFin: ['', Validators.required],
    motif: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: CongeApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    this.congeId = idParam ? Number(idParam) : null;

    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));

    if (this.congeId) {
      this.api.getOne(this.congeId).subscribe({
        next: (conge) => {
          this.form.patchValue({
            personnel: typeof conge.personnel === 'string' ? null : (conge.personnel.id ?? null),
            type: conge.type,
            dateDebut: conge.dateDebut?.substring(0, 10) ?? '',
            dateFin: conge.dateFin?.substring(0, 10) ?? '',
            motif: conge.motif ?? '',
          });
          this.loading = false;
        },
        error: () => {
          this.error = 'Impossible de charger ce congé.';
          this.loading = false;
        },
      });
    } else {
      this.loading = false;
    }
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
    const payload: Conge = {
      personnel: `/api/personnels/${raw.personnel}`,
      type: raw.type,
      dateDebut: raw.dateDebut,
      dateFin: raw.dateFin,
      motif: raw.motif || null,
    };

    this.saving = true;
    const request = this.congeId ? this.api.update(this.congeId, payload) : this.api.create(payload);

    request.subscribe({
      next: () => this.router.navigateByUrl('/conges'),
      error: () => {
        this.saving = false;
        this.error = "Erreur lors de l'enregistrement.";
      },
    });
  }
}
