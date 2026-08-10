import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Conge } from '../../../core/models/conge.model';
import { ProfilApiService } from '../profil-api.service';

const LABELS_TYPE: Record<string, string> = {
  annuel: 'Congé annuel',
  maladie: 'Congé maladie',
  maternite_paternite: 'Congé maternité / paternité',
  sans_solde: 'Congé sans solde',
  autre: 'Autre',
};

@Component({
  selector: 'app-mes-conges',
  standalone: true,
  imports: [SlicePipe],
  templateUrl: './mes-conges.component.html',
})
export class MesCongesComponent implements OnInit {
  conges: Conge[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsType = LABELS_TYPE;

  constructor(private readonly api: ProfilApiService) {}

  ngOnInit(): void {
    this.api.getMesConges().subscribe({
      next: (conges) => {
        this.conges = conges;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger vos congés.';
        this.loading = false;
      },
    });
  }
}
