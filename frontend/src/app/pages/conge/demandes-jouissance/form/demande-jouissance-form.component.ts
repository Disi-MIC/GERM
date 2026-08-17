import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { DecisionConge, DemandeJouissance, TypeConge } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PersonnelApiService } from '../../../personnel/personnel-api.service';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../../shared/searchable-select/searchable-select.component';
import { DecisionCongeApiService } from '../../decision-conge-api.service';
import { DemandeJouissanceApiService } from '../../demande-jouissance-api.service';

@Component({
  selector: 'app-demande-jouissance-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, SlicePipe, PageHeaderComponent, PanelComponent, SearchableSelectComponent],
  templateUrl: './demande-jouissance-form.component.html',
})
export class DemandeJouissanceFormComponent implements OnInit {
  personnels: Personnel[] = [];
  decisions: DecisionConge[] = [];
  decisionsDuPersonnel: DecisionConge[] = [];
  saving = false;
  error: string | null = null;
  fichier1: File | null = null;
  fichier2: File | null = null;

  form = this.fb.nonNullable.group({
    personnel: [null as number | null, Validators.required],
    type: ['annuel' as TypeConge, Validators.required],
    decision: [null as number | null],
    dateDebut: ['', Validators.required],
    dateFin: ['', Validators.required],
    motif: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: DemandeJouissanceApiService,
    private readonly decisionApi: DecisionCongeApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));
    this.decisionApi.getAll().subscribe((decisions) => (this.decisions = decisions));

    this.form.controls.personnel.valueChanges.subscribe((personnelId) => {
      const today = new Date().toISOString().substring(0, 10);
      this.decisionsDuPersonnel = this.decisions.filter(
        (d) =>
          typeof d.personnel !== 'string' &&
          d.personnel.id === personnelId &&
          (d.dateExpiration ?? '') >= today,
      );
      this.form.controls.decision.setValue(null);
    });
  }

  get personnelOptions(): SearchableSelectOption[] {
    return this.personnels.map((p) => ({ value: p.id, label: p.nomComplet ?? p.matricule ?? '' }));
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

    const payload: DemandeJouissance = {
      personnel: `/api/personnels/${raw.personnel}`,
      type: raw.type,
      decision: raw.decision ? `/api/decisions-conge/${raw.decision}` : null,
      dateDebut: raw.dateDebut,
      dateFin: raw.dateFin,
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

  private uploadPiecesEtRediriger(demande: DemandeJouissance): void {
    const id = demande.id;
    if (!id || (!this.fichier1 && !this.fichier2)) {
      this.router.navigateByUrl('/conges/demandes-jouissance');
      return;
    }

    const done = () => this.router.navigateByUrl('/conges/demandes-jouissance');
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
