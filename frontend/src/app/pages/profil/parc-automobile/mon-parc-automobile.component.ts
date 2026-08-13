import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { Vehicule } from '../../../core/models/vehicule.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { ProfilApiService } from '../profil-api.service';

const LABELS_CARBURANT: Record<string, string> = {
  essence: 'Essence',
  diesel: 'Diesel',
  electrique: 'Électrique',
  hybride: 'Hybride',
};

@Component({
  selector: 'app-mon-parc-automobile',
  standalone: true,
  imports: [SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './mon-parc-automobile.component.html',
})
export class MonParcAutomobileComponent implements OnInit {
  vehicules: Vehicule[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsCarburant = LABELS_CARBURANT;

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

  libelle(ref: ListeValeurRef | string | undefined): string {
    return ref && typeof ref !== 'string' ? ref.libelle : '';
  }
}
