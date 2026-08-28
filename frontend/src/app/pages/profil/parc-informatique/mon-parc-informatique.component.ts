import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { LicenceLogiciel } from '../../../core/models/licence-logiciel.model';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { FileGridComponent } from '../../../shared/file-grid/file-grid.component';
import { FileGridColor, FileGridItem } from '../../../shared/file-grid/file-grid-item.model';
import { antivirusDe, badgesAntivirus, COULEURS_ETAT_MATERIEL, ICONES_ETAT_MATERIEL } from '../../../shared/materiel/materiel-indicateurs.util';
import { ProfilApiService } from '../profil-api.service';

const ICONES_TYPE: Record<string, { icon: string; color: FileGridColor }> = {
  ordinateur_bureau: { icon: 'pc-display', color: 'blue' },
  ordinateur_portable: { icon: 'laptop', color: 'primary' },
  imprimante: { icon: 'printer', color: 'purple' },
  serveur: { icon: 'server', color: 'red' },
  routeur: { icon: 'router', color: 'orange' },
  switch: { icon: 'hdd-network', color: 'yellow' },
  scanner: { icon: 'upc-scan', color: 'green' },
  onduleur: { icon: 'battery-charging', color: 'secondary' },
  videoprojecteur: { icon: 'projector', color: 'blue' },
  telephone: { icon: 'telephone', color: 'green' },
  autre: { icon: 'box-seam', color: 'secondary' },
};
const ICONE_DEFAUT = { icon: 'box-seam', color: 'secondary' as FileGridColor };

@Component({
  selector: 'app-mon-parc-informatique',
  standalone: true,
  imports: [SlicePipe, PageHeaderComponent, FileGridComponent],
  templateUrl: './mon-parc-informatique.component.html',
})
export class MonParcInformatiqueComponent implements OnInit {
  materiels: MaterielInformatique[] = [];
  loading = true;
  error: string | null = null;
  materielSelectionne: MaterielInformatique | null = null;

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

  get items(): FileGridItem<MaterielInformatique>[] {
    return this.materiels.map((materiel) => {
      const { icon, color } = this.iconeType(materiel);
      const code = typeof materiel.etat === 'string' ? '' : materiel.etat.code;
      return {
        row: materiel,
        name: `${materiel.marque} ${materiel.modele}`,
        meta: materiel.numeroTelephone ? `Poste ${materiel.numeroTelephone}` : materiel.numeroInventaire,
        icon,
        color,
        statusLabel: this.libelle(materiel.etat),
        statusColor: COULEURS_ETAT_MATERIEL[code] ?? 'secondary',
        statusIcon: ICONES_ETAT_MATERIEL[code] ?? 'question-circle-fill',
        badges: badgesAntivirus(materiel),
      };
    });
  }

  iconeType(materiel: MaterielInformatique): { icon: string; color: FileGridColor } {
    const code = typeof materiel.type === 'string' ? '' : materiel.type.code;
    return ICONES_TYPE[code] ?? ICONE_DEFAUT;
  }

  badgeClasseEtat(materiel: MaterielInformatique): string {
    const code = typeof materiel.etat === 'string' ? '' : materiel.etat.code;
    return COULEURS_ETAT_MATERIEL[code] ?? 'secondary';
  }

  licenceLabel(licence: LicenceLogiciel): string {
    return this.libelle(licence.logiciel);
  }

  antivirusLicence(materiel: MaterielInformatique): LicenceLogiciel | null {
    return antivirusDe(materiel);
  }

  libelle(ref: ListeValeurRef | string | undefined): string {
    return ref && typeof ref !== 'string' ? ref.libelle : '';
  }

  selectionner(materiel: MaterielInformatique): void {
    this.materielSelectionne = materiel;
  }

  fermer(): void {
    this.materielSelectionne = null;
  }
}
