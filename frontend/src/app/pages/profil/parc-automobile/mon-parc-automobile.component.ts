import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { Vehicule } from '../../../core/models/vehicule.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { FileGridComponent } from '../../../shared/file-grid/file-grid.component';
import { FileGridColor, FileGridItem } from '../../../shared/file-grid/file-grid-item.model';
import { ProfilApiService } from '../profil-api.service';

const LABELS_CARBURANT: Record<string, string> = {
  essence: 'Essence',
  diesel: 'Diesel',
  electrique: 'Électrique',
  hybride: 'Hybride',
};

const BADGES_ETAT: Record<string, string> = {
  en_service: 'success',
  en_mission: 'info',
  en_panne: 'danger',
  en_maintenance: 'warning',
  reforme: 'secondary',
};

const ICONES_TYPE: Record<string, { icon: string; color: FileGridColor }> = {
  berline: { icon: 'car-front', color: 'primary' },
  suv_4x4: { icon: 'truck-front', color: 'blue' },
  camionnette: { icon: 'truck', color: 'orange' },
  camion: { icon: 'truck-flatbed', color: 'red' },
  moto: { icon: 'scooter', color: 'purple' },
  bus: { icon: 'bus-front', color: 'green' },
  autre: { icon: 'box-seam', color: 'secondary' },
};
const ICONE_DEFAUT = { icon: 'box-seam', color: 'secondary' as FileGridColor };

@Component({
  selector: 'app-mon-parc-automobile',
  standalone: true,
  imports: [SlicePipe, PageHeaderComponent, FileGridComponent],
  templateUrl: './mon-parc-automobile.component.html',
})
export class MonParcAutomobileComponent implements OnInit {
  vehicules: Vehicule[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsCarburant = LABELS_CARBURANT;
  vehiculeSelectionne: Vehicule | null = null;

  constructor(private readonly api: ProfilApiService) {}

  ngOnInit(): void {
    this.api.getMesVehicules().subscribe({
      next: (vehicules) => {
        this.vehicules = vehicules;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger votre parc automobile.';
        this.loading = false;
      },
    });
  }

  get items(): FileGridItem<Vehicule>[] {
    return this.vehicules.map((vehicule) => {
      const { icon, color } = this.iconeType(vehicule);
      return {
        row: vehicule,
        name: `${vehicule.marque} ${vehicule.modele}`,
        meta: vehicule.immatriculation,
        icon,
        color,
        statusLabel: this.libelle(vehicule.etat),
        statusColor: this.badgeClasseEtat(vehicule),
      };
    });
  }

  iconeType(vehicule: Vehicule): { icon: string; color: FileGridColor } {
    const code = typeof vehicule.type === 'string' ? '' : vehicule.type.code;
    return ICONES_TYPE[code] ?? ICONE_DEFAUT;
  }

  badgeClasseEtat(vehicule: Vehicule): string {
    const code = typeof vehicule.etat === 'string' ? '' : vehicule.etat.code;
    return BADGES_ETAT[code] ?? 'secondary';
  }

  libelle(ref: ListeValeurRef | string | undefined): string {
    return ref && typeof ref !== 'string' ? ref.libelle : '';
  }

  selectionner(vehicule: Vehicule): void {
    this.vehiculeSelectionne = vehicule;
  }

  fermer(): void {
    this.vehiculeSelectionne = null;
  }
}
