import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { LicenceLogiciel } from '../../../core/models/licence-logiciel.model';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { PersonnelApiService } from '../../personnel/personnel-api.service';
import { LicencesLogiciellesApiService } from '../licences-logicielles-api.service';

const CATEGORIES_LOGICIEL = ['logiciel-os', 'logiciel-antivirus', 'logiciel-bureautique'];

@Component({
  selector: 'app-licence-logicielle-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './licence-logicielle-form.component.html',
})
export class LicenceLogicielleFormComponent implements OnInit {
  logiciels: ListeValeurRef[] = [];
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    logiciel: [null as number | null, Validators.required],
    numeroLicence: [''],
    // Date du jour par défaut : l'agent n'a alors plus qu'à choisir la durée
    // pour que l'échéance soit calculée automatiquement (voir submit()).
    dateDebut: [new Date().toISOString().substring(0, 10)],
    dureeMois: [null as number | null],
    fournisseur: [''],
    observations: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: LicencesLogiciellesApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.personnelApi
      .getTypesContrat()
      .subscribe((valeurs) => (this.logiciels = valeurs.filter((v) => CATEGORIES_LOGICIEL.includes(v.categorie))));

    // Préremplissage depuis le lien "Renouveler" du tableau de bord (licence
    // arrivant à expiration) : /licences-logicielles/new?logiciel=id.
    const logicielParam = this.route.snapshot.queryParamMap.get('logiciel');
    if (logicielParam) {
      this.form.patchValue({ logiciel: Number(logicielParam) });
    }
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();
    const payload: LicenceLogiciel = {
      logiciel: `/api/liste_valeurs/${raw.logiciel}`,
      numeroLicence: raw.numeroLicence || null,
      dateDebut: raw.dateDebut || null,
      dureeMois: raw.dureeMois,
      fournisseur: raw.fournisseur || null,
      observations: raw.observations || null,
    };

    this.saving = true;
    this.api.create(payload).subscribe({
      next: () => this.router.navigateByUrl('/licences-logicielles'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }
}
