import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { DecisionConge, DemandeDecision } from '../../../../core/models/conge.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { FilePieceInputComponent } from '../../../../shared/file-piece-input/file-piece-input.component';
import { ProfilApiService } from '../../profil-api.service';

/**
 * Un agent nouvellement affecté n'a jamais eu de décision de congé : sa
 * prise de service (piece1) et son acte d'engagement délivré par la fonction
 * publique sénégalaise (piece2) sont exigés, ainsi que la date effective de
 * sa prise de service. Sinon, sa prise de service et son ancienne décision de
 * congé (numéro + année) sont exigés à la place (voir
 * DemandeDecision::$nouvellementAffecte côté serveur, source de vérité
 * partagée avec le formulaire RH demande-decision-form).
 *
 * Tant qu'une décision de congé valide (non expirée) existe déjà pour
 * l'agent connecté, une nouvelle demande n'a pas de sens (congé encore
 * disponible sous l'ancienne décision) : on l'en informe et on masque le
 * formulaire plutôt que de le laisser déposer un doublon que le RH Congé
 * devrait de toute façon rejeter (voir MeDemandesController::creerDemandeDecision(),
 * qui applique la même règle côté serveur).
 */
@Component({
  selector: 'app-nouvelle-demande-decision',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, SlicePipe, PageHeaderComponent, PanelComponent, FilePieceInputComponent],
  templateUrl: './nouvelle-demande-decision.component.html',
})
export class NouvelleDemandeDecisionComponent implements OnInit {
  saving = false;
  error: string | null = null;
  fichierPriseDeService: File | null = null;
  fichierPiece2: File | null = null;
  readonly anneeMax = new Date().getFullYear();
  chargementDecision = true;
  decisionValide: DecisionConge | null = null;

  form = this.fb.nonNullable.group({
    nouvellementAffecte: [null as boolean | null, Validators.required],
    datePriseDeService: [''],
    numeroDerniereDecision: [''],
    anneeDerniereDecision: [null as number | null, [Validators.min(1960), Validators.max(this.anneeMax)]],
    motif: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: ProfilApiService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.api.getMesDecisionsConge().subscribe({
      next: (decisions) => {
        this.decisionValide = decisions[0] ?? null;
        this.chargementDecision = false;
      },
      error: () => {
        this.chargementDecision = false;
      },
    });
  }

  /** Libellé de la pièce 2, dépendant de nouvellementAffecte — voir DemandeDecision côté serveur. */
  get labelPiece2(): string {
    return this.form.value.nouvellementAffecte ? "Acte d'engagement" : 'Ancienne décision de congé';
  }

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
    if (!this.fichierPiece2) {
      this.error = nouvellementAffecte
        ? "Merci de joindre votre acte d'engagement."
        : 'Merci de joindre votre ancienne décision de congé.';
      return;
    }
    if (nouvellementAffecte) {
      if (!raw.datePriseDeService) {
        this.error = 'Merci d\'indiquer la date de votre prise de service.';
        return;
      }
    } else if (!raw.numeroDerniereDecision.trim() || !raw.anneeDerniereDecision) {
      this.error = "Merci d'indiquer le numéro et l'année d'obtention de votre dernière décision de congé.";
      return;
    }

    // 'personnel' est volontairement omis : le serveur le déduit toujours du compte
    // connecté (voir MeDemandesController) et une IRI vide ferait échouer la désérialisation.
    const payload = {
      nouvellementAffecte,
      datePriseDeService: nouvellementAffecte ? raw.datePriseDeService : null,
      numeroDerniereDecision: nouvellementAffecte ? null : raw.numeroDerniereDecision.trim(),
      dateDerniereDecision: nouvellementAffecte ? null : `${raw.anneeDerniereDecision}-01-01`,
      motif: raw.motif || null,
    } as DemandeDecision;

    this.saving = true;
    this.error = null;
    this.api.creerDemandeDecision(payload).subscribe({
      next: (demande) => this.uploadPiecesEtRediriger(demande),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement de la demande.";
      },
    });
  }

  private uploadPiecesEtRediriger(demande: DemandeDecision): void {
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
      next: () => this.api.uploadPieceDecision2(id, this.fichierPiece2!).subscribe({ next: done, error: onError }),
      error: onError,
    });
  }
}
