import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { SlicePipe } from '@angular/common';
import { DecisionConge, DemandeDecision } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PersonnelApiService } from '../../../personnel/personnel-api.service';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../../shared/searchable-select/searchable-select.component';
import { DemandeDecisionApiService } from '../../demande-decision-api.service';

@Component({
  selector: 'app-demande-decision-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, SlicePipe, PageHeaderComponent, PanelComponent, SearchableSelectComponent],
  templateUrl: './demande-decision-form.component.html',
})
export class DemandeDecisionFormComponent implements OnInit {
  personnels: Personnel[] = [];
  saving = false;
  error: string | null = null;
  fichierPriseDeService: File | null = null;
  fichierPiece2: File | null = null;
  readonly anneeMax = new Date().getFullYear();
  decisionValide: DecisionConge | null = null;
  verificationDecisionEnCours = false;

  form = this.fb.nonNullable.group({
    personnel: [null as number | null, Validators.required],
    nouvellementAffecte: [null as boolean | null, Validators.required],
    datePriseDeService: [''],
    numeroDerniereDecision: [''],
    anneeDerniereDecision: [null as number | null, [Validators.min(1960), Validators.max(this.anneeMax)]],
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

    this.form.controls.personnel.valueChanges.subscribe((personnelId) => {
      this.decisionValide = null;
      if (!personnelId) {
        return;
      }
      this.verificationDecisionEnCours = true;
      this.api.decisionValide(personnelId).subscribe({
        next: (decision) => {
          this.decisionValide = decision;
          this.verificationDecisionEnCours = false;
        },
        error: () => {
          this.verificationDecisionEnCours = false;
        },
      });
    });
  }

  get personnelOptions(): SearchableSelectOption[] {
    return this.personnels.map((p) => ({ value: p.id, label: p.nomComplet ?? p.matricule ?? '' }));
  }

  /** Libellé de la pièce 2, dépendant de nouvellementAffecte — voir DemandeDecision côté serveur. */
  get labelPiece2(): string {
    return this.form.value.nouvellementAffecte ? "Acte d'engagement" : 'Ancienne décision de congé';
  }

  submit(): void {
    if (this.decisionValide) {
      this.error = "Cet agent dispose déjà d'une décision de congé valide : impossible de déposer une nouvelle demande.";
      return;
    }

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
    if (!this.fichierPiece2) {
      this.error = nouvellementAffecte ? "Merci de joindre l'acte d'engagement." : "Merci de joindre l'ancienne décision de congé.";
      return;
    }
    if (nouvellementAffecte) {
      if (!raw.datePriseDeService) {
        this.error = 'Merci d\'indiquer la date de prise de service.';
        return;
      }
    } else if (!raw.numeroDerniereDecision.trim() || !raw.anneeDerniereDecision) {
      this.error = "Merci d'indiquer le numéro et l'année d'obtention de la dernière décision de congé.";
      return;
    }

    const payload: DemandeDecision = {
      personnel: `/api/personnels/${raw.personnel}`,
      nouvellementAffecte,
      datePriseDeService: nouvellementAffecte ? raw.datePriseDeService : null,
      numeroDerniereDecision: nouvellementAffecte ? null : raw.numeroDerniereDecision.trim(),
      dateDerniereDecision: nouvellementAffecte ? null : `${raw.anneeDerniereDecision}-01-01`,
      motif: raw.motif || null,
    };

    this.saving = true;
    this.error = null;
    this.api.create(payload).subscribe({
      next: (demande) => this.uploadPiecesEtRediriger(demande),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }

  private uploadPiecesEtRediriger(demande: DemandeDecision): void {
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
      next: () => this.api.uploadPiece2(id, this.fichierPiece2!).subscribe({ next: done, error: onError }),
      error: onError,
    });
  }
}
