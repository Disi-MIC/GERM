import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { CarteProfessionnelle } from '../../../core/models/carte-professionnelle.model';
import { Personnel } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { CarteProApiService } from '../carte-pro-api.service';

@Component({
  selector: 'app-carte-pro-validation',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './carte-pro-validation.component.html',
})
export class CarteProValidationComponent implements OnInit {
  cartes: CarteProfessionnelle[] = [];
  loading = true;
  error: string | null = null;

  constructor(private readonly api: CarteProApiService) {}

  ngOnInit(): void {
    this.charger();
  }

  private charger(): void {
    this.loading = true;
    this.api.getEnAttenteValidation().subscribe({
      next: (cartes) => {
        this.cartes = cartes;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les cartes en attente de validation.';
        this.loading = false;
      },
    });
  }

  agentLabel(carte: CarteProfessionnelle): string {
    if (typeof carte.personnel === 'string') {
      return carte.personnel;
    }
    const personnel: Personnel = carte.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  valider(carte: CarteProfessionnelle): void {
    if (!carte.id) {
      return;
    }
    this.api.valider(carte.id).subscribe({
      next: () => {
        this.cartes = this.cartes.filter((c) => c.id !== carte.id);
      },
      error: () => {
        this.error = 'Erreur lors de la validation.';
      },
    });
  }
}
