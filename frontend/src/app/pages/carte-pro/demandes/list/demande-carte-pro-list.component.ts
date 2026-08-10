import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../../core/auth.service';
import { CarteProfessionnelle } from '../../../../core/models/carte-professionnelle.model';
import { DemandeCartePro } from '../../../../core/models/demande-carte-pro.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { DemandeCarteProApiService } from '../demande-carte-pro-api.service';

const LABELS_STATUT: Record<string, string> = {
  en_attente: 'En attente',
  transmise: 'Transmise au RH Admin',
  approuvee: 'Approuvée',
  refusee: 'Refusée',
};

const LABELS_TYPE: Record<string, string> = {
  nouvelle: 'Nouvelle carte',
  renouvellement: 'Renouvellement',
  perte_vol: 'Perte ou vol',
};

@Component({
  selector: 'app-demande-carte-pro-list',
  standalone: true,
  imports: [RouterLink, SlicePipe],
  templateUrl: './demande-carte-pro-list.component.html',
})
export class DemandeCarteProListComponent implements OnInit {
  demandes: DemandeCartePro[] = [];
  loading = true;
  error: string | null = null;
  acting: number | null = null;
  demandeSelectionnee: DemandeCartePro | null = null;
  readonly labelsStatut = LABELS_STATUT;
  readonly labelsType = LABELS_TYPE;

  constructor(
    private readonly api: DemandeCarteProApiService,
    readonly auth: AuthService,
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  private charger(): void {
    this.api.getAll().subscribe({
      next: (demandes) => {
        this.demandes = demandes;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les demandes de carte professionnelle.';
        this.loading = false;
      },
    });
  }

  agentLabel(demande: DemandeCartePro): string {
    if (typeof demande.personnel === 'string') {
      return demande.personnel;
    }
    const personnel: Personnel = demande.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  badgeClass(statut: string | undefined): string {
    switch (statut) {
      case 'approuvee':
        return 'success';
      case 'refusee':
        return 'danger';
      case 'transmise':
        return 'info';
      default:
        return 'secondary';
    }
  }

  selectionnerDemande(demande: DemandeCartePro): void {
    this.demandeSelectionnee = demande;
  }

  carteDe(demande: DemandeCartePro | null): CarteProfessionnelle | null {
    const carte = demande?.carteCreee;
    return carte && typeof carte !== 'string' ? carte : null;
  }

  /** Transmettre/Rejeter (sur une demande en attente) : réservé au profil RH Carte Pro uniquement, pas au RH Admin. */
  peutTransmettreOuRejeter(demande: DemandeCartePro): boolean {
    return !!demande.enAttente && this.auth.hasRole('ROLE_RH_CARTE_PRO');
  }

  /** Approuver/Rejeter (sur une demande transmise) : réservé au RH Admin, passe par la page dédiée (nécessite numéro/date). */
  peutTraiterTransmise(demande: DemandeCartePro): boolean {
    return !!demande.transmise && this.auth.hasRole('ROLE_ADMIN_RH');
  }

  transmettre(demande: DemandeCartePro): void {
    if (!demande.id) {
      return;
    }
    this.acting = demande.id;
    this.api.transmettre(demande.id).subscribe({
      next: () => this.charger(),
      error: () => {
        this.acting = null;
        this.error = 'Erreur lors de la transmission de la demande.';
      },
    });
  }

  rejeter(demande: DemandeCartePro): void {
    if (!demande.id) {
      return;
    }
    const commentaire = prompt('Motif du rejet (optionnel) :');
    if (commentaire === null) {
      return;
    }

    this.acting = demande.id;
    this.api.rejeter(demande.id, commentaire || null).subscribe({
      next: () => this.charger(),
      error: () => {
        this.acting = null;
        this.error = 'Erreur lors du rejet de la demande.';
      },
    });
  }
}
