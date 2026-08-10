import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { ProfilApiService } from '../profil-api.service';

@Component({
  selector: 'app-mon-parc-informatique',
  standalone: true,
  imports: [SlicePipe],
  templateUrl: './mon-parc-informatique.component.html',
})
export class MonParcInformatiqueComponent implements OnInit {
  materiels: MaterielInformatique[] = [];
  loading = true;
  error: string | null = null;

  constructor(private readonly api: ProfilApiService) {}

  ngOnInit(): void {
    this.api.getMesMateriels().subscribe({
      next: (materiels) => {
        this.materiels = materiels;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger votre parc informatique.';
        this.loading = false;
      },
    });
  }

  libelle(ref: ListeValeurRef | string | undefined): string {
    return ref && typeof ref !== 'string' ? ref.libelle : '';
  }
}
