import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { ProfilApiService } from '../profil-api.service';

const BADGES_ETAT: Record<string, string> = {
  en_service: 'success',
  en_stock: 'info',
  en_panne: 'danger',
  en_maintenance: 'warning',
  reforme: 'secondary',
};

@Component({
  selector: 'app-mon-parc-informatique',
  standalone: true,
  imports: [SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './mon-parc-informatique.component.html',
})
export class MonParcInformatiqueComponent implements OnInit {
  materiels: MaterielInformatique[] = [];
  loading = true;
  error: string | null = null;

  constructor(private readonly api: ProfilApiService) {}

  badgeClasseEtat(materiel: MaterielInformatique): string {
    const code = typeof materiel.etat === 'string' ? '' : materiel.etat.code;
    return BADGES_ETAT[code] ?? 'secondary';
  }

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
