import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { CategorieAgentConge } from '../../../core/models/parametre-eligibilite-conge.model';
import { ParametresDecisionConge } from '../../../core/models/parametres-decision-conge.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { ParametresDecisionCongeApiService } from '../parametres-decision-conge-api.service';

function creerForm(fb: FormBuilder) {
  return fb.nonNullable.group({
    visasDecrets: [''],
    article2: [''],
    article3: [''],
    ampliations: [''],
  });
}

interface CarteCategorie {
  categorie: CategorieAgentConge;
  titre: string;
  baseLegale: string;
  form: ReturnType<typeof creerForm>;
  loading: boolean;
  saving: boolean;
  error: string | null;
  succes: boolean;
  misAJourParNom: string | null;
  updatedAt: string | null;
}

/**
 * Réglages RH Admin du texte légal par défaut (visas des décrets, articles 2
 * et 3, ampliations) inséré automatiquement dans chaque décision de congé
 * générée par le RH Congé — un jeu par catégorie (fonctionnaires/
 * non-fonctionnaires, bases légales différentes), voir
 * ParametresDecisionConge et DemandeDecisionController::genererEtTransmettre()
 * côté serveur. Ce texte n'est jamais saisi à la main par le RH Congé, qui
 * n'a pas accès à cette page (route gardée ROLE_RH_RESPONSABLE, voir
 * conge.routes.ts).
 *
 * Les deux visas variables (décision de congé antérieure de l'agent,
 * attestation de non jouissance) et la clause "Après avis favorable..." ne
 * sont volontairement pas dans $visasDecrets : ils sont insérés par
 * DecisionCongeApercuComponent entre ce texte et "DECIDE :", puisqu'ils
 * dépendent de l'agent/de la demande plutôt que d'être fixes.
 */
@Component({
  selector: 'app-parametres-decision',
  standalone: true,
  imports: [ReactiveFormsModule, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './parametres-decision.component.html',
})
export class ParametresDecisionComponent implements OnInit {
  cartes: CarteCategorie[];

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: ParametresDecisionCongeApiService,
  ) {
    this.cartes = [
      {
        categorie: 'fonctionnaire',
        titre: 'Fonctionnaires',
        baseLegale: 'Loi n°61-33 du 15/06/1961',
        form: creerForm(this.fb),
        loading: true,
        saving: false,
        error: null,
        succes: false,
        misAJourParNom: null,
        updatedAt: null,
      },
      {
        categorie: 'non_fonctionnaire',
        titre: 'Non-fonctionnaires',
        baseLegale: 'Décret n°74-347 du 12/04/1974',
        form: creerForm(this.fb),
        loading: true,
        saving: false,
        error: null,
        succes: false,
        misAJourParNom: null,
        updatedAt: null,
      },
    ];
  }

  ngOnInit(): void {
    this.api.liste().subscribe({
      next: (parametres) => {
        for (const carte of this.cartes) {
          const valeurs = parametres.find((p) => p.categorie === carte.categorie);
          this.appliquer(carte, valeurs);
          carte.loading = false;
        }
      },
      error: () => {
        for (const carte of this.cartes) {
          carte.error = 'Impossible de charger les réglages.';
          carte.loading = false;
        }
      },
    });
  }

  enregistrer(carte: CarteCategorie): void {
    const raw = carte.form.getRawValue();
    carte.saving = true;
    carte.error = null;
    carte.succes = false;
    this.api
      .update(carte.categorie, {
        visasDecrets: raw.visasDecrets.trim() || null,
        article2: raw.article2.trim() || null,
        article3: raw.article3.trim() || null,
        ampliations: raw.ampliations.trim() || null,
      })
      .subscribe({
        next: (parametres) => {
          carte.saving = false;
          carte.succes = true;
          this.appliquer(carte, parametres);
        },
        error: (err) => {
          carte.saving = false;
          carte.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
        },
      });
  }

  private appliquer(carte: CarteCategorie, parametres: ParametresDecisionConge | undefined): void {
    if (parametres) {
      carte.form.patchValue({
        visasDecrets: parametres.visasDecrets ?? '',
        article2: parametres.article2 ?? '',
        article3: parametres.article3 ?? '',
        ampliations: parametres.ampliations ?? '',
      });
      carte.misAJourParNom = parametres.misAJourParNom ?? null;
      carte.updatedAt = parametres.updatedAt ?? null;
    }
  }
}
