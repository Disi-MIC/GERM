import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { DemandeDecision } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { DemandeDecisionApiService } from '../../demande-decision-api.service';

@Component({
  selector: 'app-demande-decision-traiter',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent],
  templateUrl: './demande-decision-traiter.component.html',
})
export class DemandeDecisionTraiterComponent implements OnInit {
  demande: DemandeDecision | null = null;
  loading = true;
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    numero_decision: [''],
    date_decision: [''],
    date_expiration: [''],
    commentaire: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: DemandeDecisionApiService,
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
    if (!raw.numero_decision.trim() || !raw.date_decision || !raw.date_expiration) {
      this.error = "Merci de renseigner un numéro et des dates valides (date d'expiration postérieure à la date d'octroi) pour approuver.";
      return;
    }

    this.saving = true;
    this.api
      .traiter(this.demande.id, {
        decision: 'approuver',
        commentaire: raw.commentaire || null,
        numero_decision: raw.numero_decision.trim(),
        date_decision: raw.date_decision,
        date_expiration: raw.date_expiration,
      })
      .subscribe({
        next: () => this.router.navigateByUrl('/conges/demandes-decision'),
        error: (err) => {
          this.saving = false;
          this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'approbation.";
        },
      });
  }

  refuser(): void {
    if (!this.demande?.id) {
      return;
    }
    const raw = this.form.getRawValue();

    this.saving = true;
    this.api.traiter(this.demande.id, { decision: 'refuser', commentaire: raw.commentaire || null }).subscribe({
      next: () => this.router.navigateByUrl('/conges/demandes-decision'),
      error: () => {
        this.saving = false;
        this.error = 'Erreur lors du refus de la demande.';
      },
    });
  }
}
