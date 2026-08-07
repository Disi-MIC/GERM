import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { DemandeCartePro } from '../../../../core/models/demande-carte-pro.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { DemandeCarteProApiService } from '../demande-carte-pro-api.service';

const LABELS_TYPE: Record<string, string> = {
  nouvelle: 'Nouvelle carte',
  renouvellement: 'Renouvellement',
  perte_vol: 'Perte ou vol',
};

@Component({
  selector: 'app-demande-carte-pro-traiter',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './demande-carte-pro-traiter.component.html',
})
export class DemandeCarteProTraiterComponent implements OnInit {
  demande: DemandeCartePro | null = null;
  loading = true;
  saving = false;
  error: string | null = null;
  readonly labelsType = LABELS_TYPE;

  form = this.fb.nonNullable.group({
    numero: [''],
    dateDelivrance: [''],
    commentaire: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: DemandeCarteProApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.api.getOne(id).subscribe({
      next: (demande) => {
        this.demande = demande;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger cette demande.';
        this.loading = false;
      },
    });
  }

  agentLabel(): string {
    if (!this.demande) {
      return '';
    }
    if (typeof this.demande.personnel === 'string') {
      return this.demande.personnel;
    }
    const personnel: Personnel = this.demande.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  approuver(): void {
    if (!this.demande?.id) {
      return;
    }
    const raw = this.form.getRawValue();
    if (!raw.numero.trim() || !raw.dateDelivrance) {
      this.error = 'Merci de renseigner un numéro et une date de délivrance valides pour approuver.';
      return;
    }

    this.saving = true;
    this.api
      .traiter(this.demande.id, {
        decision: 'approuver',
        commentaire: raw.commentaire || null,
        numero: raw.numero.trim(),
        dateDelivrance: raw.dateDelivrance,
      })
      .subscribe({
        next: () => this.router.navigateByUrl('/cartes-professionnelles/demandes'),
        error: () => {
          this.saving = false;
          this.error = "Erreur lors de l'approbation de la demande.";
        },
      });
  }

  refuser(): void {
    if (!this.demande?.id) {
      return;
    }
    const raw = this.form.getRawValue();

    this.saving = true;
    this.api
      .traiter(this.demande.id, { decision: 'refuser', commentaire: raw.commentaire || null })
      .subscribe({
        next: () => this.router.navigateByUrl('/cartes-professionnelles/demandes'),
        error: () => {
          this.saving = false;
          this.error = 'Erreur lors du refus de la demande.';
        },
      });
  }
}
