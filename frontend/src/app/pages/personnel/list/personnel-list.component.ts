import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Personnel } from '../../../core/models/personnel.model';
import { PersonnelApiService } from '../personnel-api.service';

@Component({
  selector: 'app-personnel-list',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './personnel-list.component.html',
})
export class PersonnelListComponent implements OnInit {
  personnels: Personnel[] = [];
  loading = true;
  error: string | null = null;

  constructor(private readonly api: PersonnelApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (personnels) => {
        this.personnels = personnels;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger la liste du personnel.';
        this.loading = false;
      },
    });
  }

  serviceLabel(personnel: Personnel): string {
    return typeof personnel.service === 'string' ? personnel.service : personnel.service.nom;
  }
}
