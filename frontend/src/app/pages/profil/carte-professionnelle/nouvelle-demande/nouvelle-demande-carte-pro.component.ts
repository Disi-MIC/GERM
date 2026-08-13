import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { CarteProfessionnelle } from '../../../../core/models/carte-professionnelle.model';
import { TypeDemandeCartePro } from '../../../../core/models/demande-carte-pro.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { DemandeCarteProSelfPayload, ProfilApiService } from '../../profil-api.service';

@Component({
  selector: 'app-nouvelle-demande-carte-pro',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './nouvelle-demande-carte-pro.component.html',
})
export class NouvelleDemandeCarteProComponent implements OnInit {
  personnel: Personnel | null = null;
  mesCartes: CarteProfessionnelle[] = [];
  loading = true;
  saving = false;
  error: string | null = null;
  fichier: File | null = null;

  form = this.fb.nonNullable.group({
    typeDemande: ['nouvelle' as TypeDemandeCartePro, Validators.required],
    carteReference: [null as number | null],
    motif: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: ProfilApiService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.api.getMonPersonnel().subscribe({
      next: (personnel) => {
        this.personnel = personnel;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger votre fiche personnel.';
        this.loading = false;
      },
    });
    this.api.getMesCartesProfessionnelles().subscribe((cartes) => (this.mesCartes = cartes));
  }

  onFichierChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.fichier = input.files?.[0] ?? null;
  }

  libellePiece(): string {
    const type = this.form.getRawValue().typeDemande;

    if (type === 'renouvellement') {
      return 'Copie de la dernière carte professionnelle';
    }
    if (type === 'nouvelle') {
      return this.personnel?.matricule ? 'Prise de service' : 'Contrat';
    }

    return 'Pièce justificative (ex. déclaration de perte/vol)';
  }

  pieceObligatoire(): boolean {
    const type = this.form.getRawValue().typeDemande;
    return type === 'nouvelle' || type === 'renouvellement';
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();

    if (raw.typeDemande !== 'nouvelle' && !raw.carteReference) {
      this.error = 'Votre carte actuelle est requise pour un renouvellement ou une déclaration de perte/vol.';
      return;
    }

    if (this.pieceObligatoire() && !this.fichier) {
      this.error = `Merci de joindre le justificatif requis (${this.libellePiece()}).`;
      return;
    }

    const payload: DemandeCarteProSelfPayload = {
      typeDemande: raw.typeDemande,
      carteReferenceId: raw.carteReference,
      motif: raw.motif || null,
    };

    this.saving = true;
    this.api.creerDemandeCartePro(payload).subscribe({
      next: (demande) => {
        if (this.fichier && demande.id) {
          this.api.uploadPieceDemandeCartePro(demande.id, this.fichier).subscribe({
            next: () => this.router.navigateByUrl('/mon-espace/carte-professionnelle'),
            error: (err) => {
              this.saving = false;
              const detail = err?.error?.errors ? Object.values(err.error.errors).join(' ') : null;
              this.error = detail
                ? `Demande créée, mais la pièce jointe n'a pas pu être envoyée : ${detail}`
                : "Demande créée, mais l'envoi de la pièce jointe a échoué.";
            },
          });
        } else {
          this.router.navigateByUrl('/mon-espace/carte-professionnelle');
        }
      },
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement de la demande.";
      },
    });
  }
}
