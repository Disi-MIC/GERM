import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { DemandeDecision } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PersonnelApiService } from '../../../personnel/personnel-api.service';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { DemandeDecisionApiService } from '../../demande-decision-api.service';

@Component({
  selector: 'app-demande-decision-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent],
  templateUrl: './demande-decision-form.component.html',
})
export class DemandeDecisionFormComponent implements OnInit {
  personnels: Personnel[] = [];
  saving = false;
  error: string | null = null;
  fichier1: File | null = null;
  fichier2: File | null = null;

  form = this.fb.nonNullable.group({
    personnel: [null as number | null],
    dateDerniereDecision: [''],
    numeroDerniereDecision: [''],
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

  onFichier1Change(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.fichier1 = input.files?.[0] ?? null;
  }

  onFichier2Change(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.fichier2 = input.files?.[0] ?? null;
  }

  submit(): void {
    if (!this.form.value.personnel) {
      this.error = "Merci de sélectionner l'agent.";
      return;
    }

    const raw = this.form.getRawValue();
    const payload: DemandeDecision = {
      personnel: `/api/personnels/${raw.personnel}`,
      dateDerniereDecision: raw.dateDerniereDecision || null,
      numeroDerniereDecision: raw.numeroDerniereDecision || null,
      motif: raw.motif || null,
    };

    this.saving = true;
    this.api.create(payload).subscribe({
      next: (demande) => this.uploadPiecesEtRediriger(demande),
      error: () => {
        this.saving = false;
        this.error = "Erreur lors de l'enregistrement de la demande.";
      },
    });
  }

  private uploadPiecesEtRediriger(demande: DemandeDecision): void {
    const id = demande.id;
    if (!id || (!this.fichier1 && !this.fichier2)) {
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

    if (this.fichier1 && this.fichier2) {
      this.api.uploadPiece1(id, this.fichier1).subscribe({
        next: () => this.api.uploadPiece2(id, this.fichier2!).subscribe({ next: done, error: onError }),
        error: onError,
      });
    } else if (this.fichier1) {
      this.api.uploadPiece1(id, this.fichier1).subscribe({ next: done, error: onError });
    } else if (this.fichier2) {
      this.api.uploadPiece2(id, this.fichier2).subscribe({ next: done, error: onError });
    }
  }
}
