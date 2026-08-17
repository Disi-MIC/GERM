import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { DecisionConge } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PersonnelApiService } from '../../../personnel/personnel-api.service';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../../shared/searchable-select/searchable-select.component';
import { DecisionCongeApiService } from '../../decision-conge-api.service';

@Component({
  selector: 'app-decision-conge-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent, SearchableSelectComponent],
  templateUrl: './decision-conge-form.component.html',
})
export class DecisionCongeFormComponent implements OnInit {
  decisionId: number | null = null;
  personnels: Personnel[] = [];
  loading = true;
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    personnel: [null as number | null, Validators.required],
    numeroDecision: ['', Validators.required],
    dateDecision: ['', Validators.required],
    dateExpiration: ['', Validators.required],
    observations: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: DecisionCongeApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    this.decisionId = idParam ? Number(idParam) : null;

    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));

    if (this.decisionId) {
      this.api.getOne(this.decisionId).subscribe({
        next: (decision) => {
          this.form.patchValue({
            personnel: typeof decision.personnel === 'string' ? null : (decision.personnel.id ?? null),
            numeroDecision: decision.numeroDecision,
            dateDecision: decision.dateDecision?.substring(0, 10) ?? '',
            dateExpiration: decision.dateExpiration?.substring(0, 10) ?? '',
            observations: decision.observations ?? '',
          });
          this.loading = false;
        },
        error: () => {
          this.error = 'Impossible de charger cette décision.';
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
    const payload: DecisionConge = {
      personnel: `/api/personnels/${raw.personnel}`,
      numeroDecision: raw.numeroDecision,
      dateDecision: raw.dateDecision,
      dateExpiration: raw.dateExpiration,
      observations: raw.observations || null,
    };

    this.saving = true;
    const request = this.decisionId
      ? this.api.update(this.decisionId, payload)
      : this.api.create(payload);

    request.subscribe({
      next: () => this.router.navigateByUrl('/conges/decisions'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }
}
