import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { DemandeCartePro, TypeDemandeCartePro } from '../../../../core/models/demande-carte-pro.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PersonnelApiService } from '../../../personnel/personnel-api.service';
import { CarteProfessionnelle } from '../../../../core/models/carte-professionnelle.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { CarteProApiService } from '../../carte-pro-api.service';
import { DemandeCarteProApiService } from '../demande-carte-pro-api.service';

@Component({
  selector: 'app-demande-carte-pro-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './demande-carte-pro-form.component.html',
})
export class DemandeCarteProFormComponent implements OnInit {
  personnels: Personnel[] = [];
  cartes: CarteProfessionnelle[] = [];
  cartesDuPersonnel: CarteProfessionnelle[] = [];
  saving = false;
  error: string | null = null;
  fichier: File | null = null;

  form = this.fb.nonNullable.group({
    personnel: [null as number | null, Validators.required],
    typeDemande: ['nouvelle' as TypeDemandeCartePro, Validators.required],
    carteReference: [null as number | null],
    motif: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: DemandeCarteProApiService,
    private readonly carteApi: CarteProApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));
    this.carteApi.getAll().subscribe((cartes) => (this.cartes = cartes));

    this.form.controls.personnel.valueChanges.subscribe((personnelId) => {
      this.cartesDuPersonnel = this.cartes.filter(
        (c) => typeof c.personnel !== 'string' && c.personnel.id === personnelId,
      );
      this.form.controls.carteReference.setValue(null);
    });
  }

  onFichierChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.fichier = input.files?.[0] ?? null;
  }

  /**
   * Le justificatif attendu dépend du type de demande, et pour une nouvelle
   * carte, de si l'agent a déjà un matricule (prise de service) ou non
   * (contrat, agent en attente d'affectation officielle).
   */
  libellePiece(): string {
    const raw = this.form.getRawValue();

    if (raw.typeDemande === 'renouvellement') {
      return 'Copie de la dernière carte professionnelle';
    }

    if (raw.typeDemande === 'nouvelle') {
      const personnel = this.personnels.find((p) => p.id === raw.personnel);
      return personnel?.matricule ? 'Prise de service' : 'Contrat';
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
      this.error = 'La carte actuelle est requise pour un renouvellement ou une déclaration de perte/vol.';
      return;
    }

    if (this.pieceObligatoire() && !this.fichier) {
      this.error = `Merci de joindre le justificatif requis (${this.libellePiece()}).`;
      return;
    }

    const payload: DemandeCartePro = {
      personnel: `/api/personnels/${raw.personnel}`,
      typeDemande: raw.typeDemande,
      carteReference: raw.carteReference ? `/api/cartes-professionnelles/${raw.carteReference}` : null,
      motif: raw.motif || null,
    };

    this.saving = true;
    this.api.create(payload).subscribe({
      next: (demande) => {
        if (this.fichier && demande.id) {
          this.api.uploadPiece(demande.id, this.fichier).subscribe({
            next: () => this.router.navigateByUrl('/cartes-professionnelles/demandes'),
            error: (err) => {
              this.saving = false;
              const detail = err?.error?.errors ? Object.values(err.error.errors).join(' ') : null;
              this.error = detail
                ? `Demande créée, mais la pièce jointe n'a pas pu être envoyée : ${detail}`
                : "Demande créée, mais l'envoi de la pièce jointe a échoué.";
            },
          });
        } else {
          this.router.navigateByUrl('/cartes-professionnelles/demandes');
        }
      },
      error: () => {
        this.saving = false;
        this.error = "Erreur lors de l'enregistrement de la demande.";
      },
    });
  }
}
