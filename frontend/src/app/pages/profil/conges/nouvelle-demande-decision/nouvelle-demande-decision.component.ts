import { Component } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { DemandeDecision } from '../../../../core/models/conge.model';
import { ProfilApiService } from '../../profil-api.service';

@Component({
  selector: 'app-nouvelle-demande-decision',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './nouvelle-demande-decision.component.html',
})
export class NouvelleDemandeDecisionComponent {
  saving = false;
  error: string | null = null;
  fichier1: File | null = null;
  fichier2: File | null = null;

  form = this.fb.nonNullable.group({
    dateDerniereDecision: [''],
    numeroDerniereDecision: [''],
    motif: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: ProfilApiService,
    private readonly router: Router,
  ) {}

  onFichier1Change(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.fichier1 = input.files?.[0] ?? null;
  }

  onFichier2Change(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.fichier2 = input.files?.[0] ?? null;
  }

  submit(): void {
    const raw = this.form.getRawValue();
    // 'personnel' est volontairement omis : le serveur le déduit toujours du compte
    // connecté (voir MeDemandesController) et une IRI vide ferait échouer la désérialisation.
    const payload = {
      dateDerniereDecision: raw.dateDerniereDecision || null,
      numeroDerniereDecision: raw.numeroDerniereDecision || null,
      motif: raw.motif || null,
    } as DemandeDecision;

    this.saving = true;
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
    if (!id || (!this.fichier1 && !this.fichier2)) {
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

    if (this.fichier1 && this.fichier2) {
      this.api.uploadPieceDecision1(id, this.fichier1).subscribe({
        next: () => this.api.uploadPieceDecision2(id, this.fichier2!).subscribe({ next: done, error: onError }),
        error: onError,
      });
    } else if (this.fichier1) {
      this.api.uploadPieceDecision1(id, this.fichier1).subscribe({ next: done, error: onError });
    } else if (this.fichier2) {
      this.api.uploadPieceDecision2(id, this.fichier2).subscribe({ next: done, error: onError });
    }
  }
}
