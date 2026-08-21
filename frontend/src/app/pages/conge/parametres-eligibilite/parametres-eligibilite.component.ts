import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { CategorieAgentConge, ParametreEligibiliteConge } from '../../../core/models/parametre-eligibilite-conge.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { ParametresEligibiliteCongeApiService } from '../parametres-eligibilite-conge-api.service';

function creerForm(fb: FormBuilder) {
  return fb.nonNullable.group({
    joursParMois: [2],
    plafondJours: [90],
    delaiEligibiliteMois: [12],
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
 * Réglages RH Admin du calcul d'éligibilité/octroi de jours de congé (jours
 * acquis par mois, plafond, délai d'éligibilité avant la première décision),
 * un jeu par catégorie — fonctionnaires (Loi 61-33 du 15/06/1961) et
 * non-fonctionnaires (Décret 74-347 du 12/04/1974), voir
 * EligibiliteDecisionCongeService côté serveur, seule source de vérité du
 * calcul. Route gardée ROLE_RH_RESPONSABLE (voir conge.routes.ts), comme
 * ParametresDecisionComponent (texte légal des décisions, réglage distinct).
 */
@Component({
  selector: 'app-parametres-eligibilite',
  standalone: true,
  imports: [ReactiveFormsModule, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './parametres-eligibilite.component.html',
})
export class ParametresEligibiliteComponent implements OnInit {
  cartes: CarteCategorie[];

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: ParametresEligibiliteCongeApiService,
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
    this.api.update(carte.categorie, raw).subscribe({
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

  private appliquer(carte: CarteCategorie, parametres: ParametreEligibiliteConge | undefined): void {
    if (parametres) {
      carte.form.patchValue({
        joursParMois: parametres.joursParMois,
        plafondJours: parametres.plafondJours,
        delaiEligibiliteMois: parametres.delaiEligibiliteMois,
      });
      carte.misAJourParNom = parametres.misAJourParNom ?? null;
      carte.updatedAt = parametres.updatedAt ?? null;
    }
  }
}
