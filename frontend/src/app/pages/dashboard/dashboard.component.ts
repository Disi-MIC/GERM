import { KeyValuePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DashboardPersonnel } from '../../core/models/dashboard.model';
import { DashboardApiService } from './dashboard-api.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [FormsModule, KeyValuePipe],
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  data: DashboardPersonnel | null = null;
  loading = true;
  error: string | null = null;
  filtreDirection: number | null = null;
  filtreService: number | null = null;

  constructor(private readonly api: DashboardApiService) {}

  ngOnInit(): void {
    this.charger();
  }

  onFiltreChange(): void {
    this.charger();
  }

  private charger(): void {
    this.loading = true;
    this.api.getPersonnel(this.filtreDirection, this.filtreService).subscribe({
      next: (data) => {
        this.data = data;
        this.filtreDirection = data.filtreDirection;
        this.filtreService = data.filtreService;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le tableau de bord.';
        this.loading = false;
      },
    });
  }

  pourcentage(m: number, f: number): number {
    const total = m + f;
    return total > 0 ? Math.round((m / total) * 100) : 0;
  }
}
