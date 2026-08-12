import { KeyValuePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { DashboardInformatique } from '../../core/models/dashboard.model';
import { DashboardApiService } from '../dashboard/dashboard-api.service';

type PeriodeKey = 'aujourdhui' | 'semaine' | 'mois' | 'total';

const LABELS_PERIODE: Record<PeriodeKey, string> = {
  aujourdhui: "Aujourd'hui",
  semaine: 'Cette semaine',
  mois: 'Ce mois',
  total: 'Depuis toujours',
};

@Component({
  selector: 'app-dashboard-informatique',
  standalone: true,
  imports: [KeyValuePipe, RouterLink],
  templateUrl: './dashboard-informatique.component.html',
})
export class DashboardInformatiqueComponent implements OnInit {
  data: DashboardInformatique | null = null;
  loading = true;
  error: string | null = null;
  periode: PeriodeKey = 'mois';
  readonly periodes: PeriodeKey[] = ['aujourdhui', 'semaine', 'mois', 'total'];
  readonly labelsPeriode = LABELS_PERIODE;

  constructor(private readonly api: DashboardApiService) {}

  ngOnInit(): void {
    this.loading = true;
    this.api.getInformatique().subscribe({
      next: (data) => {
        this.data = data;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le tableau de bord.';
        this.loading = false;
      },
    });
  }

  ticketsTraitesPeriode() {
    return this.data?.tickets.traites[this.periode] ?? { resolus: 0, refuses: 0 };
  }

  maintenancePeriode(): number {
    return this.data?.maintenance[this.periode] ?? 0;
  }
}
