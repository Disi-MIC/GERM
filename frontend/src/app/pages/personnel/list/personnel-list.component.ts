import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Personnel } from '../../../core/models/personnel.model';
import { PersonnelApiService } from '../personnel-api.service';

const LABELS_STATUT: Record<string, string> = {
  actif: 'Actif',
  en_conge: 'En congé',
  suspendu: 'Suspendu',
  retraite: 'Retraité',
  demissionnaire: 'Démissionnaire',
};

const BADGES_STATUT: Record<string, string> = {
  actif: 'success',
  en_conge: 'info',
  suspendu: 'warning',
  retraite: 'secondary',
  demissionnaire: 'danger',
};

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
  readonly labelsStatut = LABELS_STATUT;

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

  badgeClasseStatut(statut: string): string {
    return BADGES_STATUT[statut] ?? 'secondary';
  }
}
