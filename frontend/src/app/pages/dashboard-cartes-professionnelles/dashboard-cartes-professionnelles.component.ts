import { Component, OnInit } from '@angular/core';
import { DashboardCartesProfessionnelles } from '../../core/models/dashboard.model';
import { DashboardApiService } from '../dashboard/dashboard-api.service';

type PeriodeKey = 'aujourdhui' | 'semaine' | 'mois' | 'total';

const LABELS_PERIODE: Record<PeriodeKey, string> = {
  aujourdhui: "Aujourd'hui",
  semaine: 'Cette semaine',
  mois: 'Ce mois',
  total: 'Depuis toujours',
};

@Component({
  selector: 'app-dashboard-cartes-professionnelles',
  standalone: true,
  templateUrl: './dashboard-cartes-professionnelles.component.html',
})
export class DashboardCartesProfessionnellesComponent implements OnInit {
  data: DashboardCartesProfessionnelles | null = null;
  loading = true;
  error: string | null = null;
  periode: PeriodeKey = 'mois';
  readonly periodes: PeriodeKey[] = ['aujourdhui', 'semaine', 'mois', 'total'];
  readonly labelsPeriode = LABELS_PERIODE;

  constructor(private readonly api: DashboardApiService) {}

  ngOnInit(): void {
    this.loading = true;
    this.api.getCartesProfessionnelles().subscribe({
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

  traitesPeriode() {
    return this.data?.traites[this.periode] ?? { approuvees: 0, refusees: 0 };
  }
}
