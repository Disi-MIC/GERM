import { Component } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { DemandeDecision } from '../../../../core/models/conge.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { FilePieceInputComponent } from '../../../../shared/file-piece-input/file-piece-input.component';
import { ProfilApiService } from '../../profil-api.service';

/**
 * Un agent nouvellement affecté n'a jamais eu de décision de congé : seule sa
 * prise de service est exigée. Sinon, l'ancienne décision est exigée en plus
 * (voir DemandeDecision::$nouvellementAffecte côté serveur, source de vérité
 * partagée avec le formulaire RH demande-decision-form).
 */
@Component({
  selector: 'app-nouvelle-demande-decision',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent, FilePieceInputComponent],
  templateUrl: './nouvelle-demande-decision.component.html',
})
export class NouvelleDemandeDecisionComponent {
  saving = false;
  error: string | null = null;
  fichierPriseDeService: File | null = null;
  fichierAncienneDecision: File | null = null;
  readonly anneeMax = new Date().getFullYear();

  form = this.fb.nonNullable.group({
    nouvellementAffecte: [null as boolean | null, Validators.required],
    numeroDerniereDecision: [''],
    anneeDerniereDecision: [null as number | null, [Validators.min(1960), Validators.max(this.anneeMax)]],
    motif: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: ProfilApiService,
    private readonly router: Router,
  ) {}

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.error = "Merci d'indiquer si vous êtes nouvellement affecté(e).";
      return;
    }

    const raw = this.form.getRawValue();
    const nouvellementAffecte = raw.nouvellementAffecte!;

    if (!this.fichierPriseDeService) {
      this.error = 'Merci de joindre votre prise de service.';
      return;
    }
    if (!nouvellementAffecte) {
      if (!this.fichierAncienneDecision) {
        this.error = 'Merci de joindre votre ancienne décision de congé.';
        return;
      }
      if (!raw.numeroDerniereDecision.trim() || !raw.anneeDerniereDecision) {
        this.error = "Merci d'indiquer le numéro et l'année d'obtention de votre dernière décision de congé.";
        return;
      }
    }

    // 'personnel' est volontairement omis : le serveur le déduit toujours du compte
    // connecté (voir MeDemandesController) et une IRI vide ferait échouer la désérialisation.
    const payload = {
      nouvellementAffecte,
      numeroDerniereDecision: nouvellementAffecte ? null : raw.numeroDerniereDecision.trim(),
      dateDerniereDecision: nouvellementAffecte ? null : `${raw.anneeDerniereDecision}-01-01`,
      motif: raw.motif || null,
    } as DemandeDecision;

    this.saving = true;
    this.error = null;
    this.api.creerDemandeDecision(payload).subscribe({
      next: (demande) => this.uploadPiecesEtRediriger(demande, nouvellementAffecte),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement de la demande.";
      },
    });
  }

  private uploadPiecesEtRediriger(demande: DemandeDecision, nouvellementAffecte: boolean): void {
    const id = demande.id;
    if (!id) {
      this.router.navigateByUrl('/mon-espace/conges');
      return;
    }

    const done = () => this.router.navigateByUrl('/mon-espace/conges');
    const onError = (err: { error?: { errors?: Record<string, string> } }) => {
      this.saving = false;
      const detail = err?.error?.errors ? Object.values(err.error.errors).join(' ') : null;
      this.error = detail
        ? `Demande créée, mais une pièce jointe n'a pas pu être envoyée : ${detail}`
        : "Demande créée, mais l'envoi d'une pièce jointe a échoué.";
    };

    this.api.uploadPieceDecision1(id, this.fichierPriseDeService!).subscribe({
      next: () => {
        if (!nouvellementAffecte && this.fichierAncienneDecision) {
          this.api.uploadPieceDecision2(id, this.fichierAncienneDecision).subscribe({ next: done, error: onError });
        } else {
          done();
        }
      },
      error: onError,
    });
  }
}
