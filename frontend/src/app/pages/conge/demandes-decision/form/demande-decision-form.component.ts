import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { DemandeDecision } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PersonnelApiService } from '../../../personnel/personnel-api.service';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../../shared/searchable-select/searchable-select.component';
import { DemandeDecisionApiService } from '../../demande-decision-api.service';

@Component({
  selector: 'app-demande-decision-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent, SearchableSelectComponent],
  templateUrl: './demande-decision-form.component.html',
})
export class DemandeDecisionFormComponent implements OnInit {
  personnels: Personnel[] = [];
  saving = false;
  error: string | null = null;
  fichierPriseDeService: File | null = null;
  fichierAncienneDecision: File | null = null;

  form = this.fb.nonNullable.group({
    personnel: [null as number | null, Validators.required],
    nouvellementAffecte: [null as boolean | null, Validators.required],
    numeroDerniereDecision: [''],
    dateDerniereDecision: [''],
    motif: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: DemandeDecisionApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));
  }

  get personnelOptions(): SearchableSelectOption[] {
    return this.personnels.map((p) => ({ value: p.id, label: p.nomComplet ?? p.matricule ?? '' }));
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.error = "Merci de sélectionner l'agent et d'indiquer s'il est nouvellement affecté.";
      return;
    }

    const raw = this.form.getRawValue();
    const nouvellementAffecte = raw.nouvellementAffecte!;

    if (!this.fichierPriseDeService) {
      this.error = 'Merci de joindre la prise de service.';
      return;
    }
    if (!nouvellementAffecte && !this.fichierAncienneDecision) {
      this.error = "Merci de joindre l'ancienne décision de congé.";
      return;
    }

    const payload: DemandeDecision = {
      personnel: `/api/personnels/${raw.personnel}`,
      nouvellementAffecte,
      numeroDerniereDecision: nouvellementAffecte ? null : raw.numeroDerniereDecision || null,
      dateDerniereDecision: nouvellementAffecte ? null : raw.dateDerniereDecision || null,
      motif: raw.motif || null,
    };

    this.saving = true;
    this.error = null;
    this.api.create(payload).subscribe({
      next: (demande) => this.uploadPiecesEtRediriger(demande, nouvellementAffecte),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }

  private uploadPiecesEtRediriger(demande: DemandeDecision, nouvellementAffecte: boolean): void {
    const id = demande.id;
    if (!id) {
      this.router.navigateByUrl('/conges/demandes-decision');
      return;
    }

    const done = () => this.router.navigateByUrl('/conges/demandes-decision');
    const onError = (err: { error?: { errors?: Record<string, string> } }) => {
      this.saving = false;
      const detail = err?.error?.errors ? Object.values(err.error.errors).join(' ') : null;
      this.error = detail
        ? `Demande créée, mais une pièce jointe n'a pas pu être envoyée : ${detail}`
        : "Demande créée, mais l'envoi d'une pièce jointe a échoué.";
    };

    this.api.uploadPiece1(id, this.fichierPriseDeService!).subscribe({
      next: () => {
        if (!nouvellementAffecte && this.fichierAncienneDecision) {
          this.api.uploadPiece2(id, this.fichierAncienneDecision).subscribe({ next: done, error: onError });
        } else {
          done();
        }
      },
      error: onError,
    });
  }
}
