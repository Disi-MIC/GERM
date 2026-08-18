import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { ParametresDecisionCongeApiService } from '../parametres-decision-conge-api.service';

/**
 * Réglages RH Admin du texte légal par défaut (visas des décrets, articles 2
 * et 3) inséré automatiquement dans chaque décision de congé générée par le
 * RH Congé — voir ParametresDecisionConge et
 * DemandeDecisionController::genererEtTransmettre() côté serveur. Ce texte
 * n'est jamais saisi à la main par le RH Congé, qui n'a pas accès à cette
 * page (route gardée ROLE_ADMIN_RH, voir conge.routes.ts).
 */
@Component({
  selector: 'app-parametres-decision',
  standalone: true,
  imports: [ReactiveFormsModule, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './parametres-decision.component.html',
})
export class ParametresDecisionComponent implements OnInit {
  loading = true;
  saving = false;
  error: string | null = null;
  succes = false;
  misAJourParNom: string | null = null;
  updatedAt: string | null = null;

  form = this.fb.nonNullable.group({
    visasDecrets: [''],
    article2: [''],
    article3: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: ParametresDecisionCongeApiService,
  ) {}

  ngOnInit(): void {
    this.api.get().subscribe({
      next: (parametres) => {
        this.form.patchValue({
          visasDecrets: parametres.visasDecrets ?? '',
          article2: parametres.article2 ?? '',
          article3: parametres.article3 ?? '',
        });
        this.misAJourParNom = parametres.misAJourParNom ?? null;
        this.updatedAt = parametres.updatedAt ?? null;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les réglages.';
        this.loading = false;
      },
    });
  }

  enregistrer(): void {
    const raw = this.form.getRawValue();
    this.saving = true;
    this.error = null;
    this.succes = false;
    this.api
      .update({
        visasDecrets: raw.visasDecrets.trim() || null,
        article2: raw.article2.trim() || null,
        article3: raw.article3.trim() || null,
      })
      .subscribe({
        next: (parametres) => {
          this.saving = false;
          this.succes = true;
          this.misAJourParNom = parametres.misAJourParNom ?? null;
          this.updatedAt = parametres.updatedAt ?? null;
        },
        error: (err) => {
          this.saving = false;
          this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
        },
      });
  }
}
