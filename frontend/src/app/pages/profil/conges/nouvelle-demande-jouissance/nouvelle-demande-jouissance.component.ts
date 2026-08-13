import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { DecisionConge, DemandeJouissance, TypeConge } from '../../../../core/models/conge.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { DemandeJouissanceSelfPayload, ProfilApiService } from '../../profil-api.service';

@Component({
  selector: 'app-nouvelle-demande-jouissance',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent],
  templateUrl: './nouvelle-demande-jouissance.component.html',
})
export class NouvelleDemandeJouissanceComponent implements OnInit {
  mesDecisionsValides: DecisionConge[] = [];
  saving = false;
  error: string | null = null;
  fichier1: File | null = null;
  fichier2: File | null = null;

  form = this.fb.nonNullable.group({
    type: ['annuel' as TypeConge, Validators.required],
    decision: [null as number | null],
    dateDebut: ['', Validators.required],
    dateFin: ['', Validators.required],
    motif: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: ProfilApiService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.api.getMesDecisionsConge().subscribe((decisions) => (this.mesDecisionsValides = decisions));
  }

  onFichier1Change(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.fichier1 = input.files?.[0] ?? null;
  }

  onFichier2Change(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.fichier2 = input.files?.[0] ?? null;
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();

    if (raw.type === 'annuel' && !raw.decision) {
      this.error = 'Une décision de congé valide est requise pour un congé annuel.';
      return;
    }

    const payload: DemandeJouissanceSelfPayload = {
      type: raw.type,
      decisionId: raw.decision,
      dateDebut: raw.dateDebut,
      dateFin: raw.dateFin,
      motif: raw.motif || null,
    };

    this.saving = true;
    this.api.creerDemandeJouissance(payload).subscribe({
      next: (demande) => this.uploadPiecesEtRediriger(demande),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement de la demande.";
      },
    });
  }

  private uploadPiecesEtRediriger(demande: DemandeJouissance): void {
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
      this.api.uploadPieceJouissance1(id, this.fichier1).subscribe({
        next: () => this.api.uploadPieceJouissance2(id, this.fichier2!).subscribe({ next: done, error: onError }),
        error: onError,
      });
    } else if (this.fichier1) {
      this.api.uploadPieceJouissance1(id, this.fichier1).subscribe({ next: done, error: onError });
    } else if (this.fichier2) {
      this.api.uploadPieceJouissance2(id, this.fichier2).subscribe({ next: done, error: onError });
    }
  }
}
