import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { PrioriteTicket, StatutTicket, TicketIncident } from '../../../core/models/ticket-incident.model';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { Personnel } from '../../../core/models/personnel.model';
import { TicketsInformatiqueApiService } from '../tickets-informatique-api.service';

const LABELS_STATUT: Record<StatutTicket, string> = {
  ouvert: 'Ouvert',
  en_cours: 'En cours',
  resolu: 'Résolu',
  cloture: 'Clôturé',
  refuse: 'Refusé',
};

const BADGES_STATUT: Record<StatutTicket, string> = {
  ouvert: 'secondary',
  en_cours: 'info',
  resolu: 'warning',
  cloture: 'success',
  refuse: 'danger',
};

const LABELS_PRIORITE: Record<PrioriteTicket, string> = {
  basse: 'Basse',
  normale: 'Normale',
  haute: 'Haute',
  critique: 'Critique',
};

const BADGES_PRIORITE: Record<PrioriteTicket, string> = {
  basse: 'secondary',
  normale: 'info',
  haute: 'warning',
  critique: 'danger',
};

@Component({
  selector: 'app-tickets-informatique-list',
  standalone: true,
  imports: [RouterLink, SlicePipe],
  templateUrl: './tickets-informatique-list.component.html',
})
export class TicketsInformatiqueListComponent implements OnInit {
  tickets: TicketIncident[] = [];
  ticketsAffiches: TicketIncident[] = [];
  loading = true;
  error: string | null = null;
  filtreStatut: StatutTicket | null = null;
  compteurs: Record<string, number> = {};
  readonly statuts: StatutTicket[] = ['ouvert', 'en_cours', 'resolu', 'cloture', 'refuse'];
  readonly labelsStatut = LABELS_STATUT;
  readonly labelsPriorite = LABELS_PRIORITE;

  constructor(private readonly api: TicketsInformatiqueApiService) {}

  ngOnInit(): void {
    this.api.getAll().subscribe({
      next: (tickets) => {
        this.tickets = tickets;
        this.recalculerCompteurs();
        this.appliquerFiltre();
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les tickets.';
        this.loading = false;
      },
    });
  }

  private recalculerCompteurs(): void {
    const compteurs: Record<string, number> = {};
    for (const statut of this.statuts) {
      compteurs[statut] = 0;
    }
    for (const ticket of this.tickets) {
      const statut = ticket.statut ?? '';
      compteurs[statut] = (compteurs[statut] ?? 0) + 1;
    }
    this.compteurs = compteurs;
  }

  private appliquerFiltre(): void {
    this.ticketsAffiches = this.filtreStatut ? this.tickets.filter((t) => t.statut === this.filtreStatut) : this.tickets;
  }

  filtrer(statut: StatutTicket): void {
    this.filtreStatut = this.filtreStatut === statut ? null : statut;
    this.appliquerFiltre();
  }

  badgeClasseStatut(statut: StatutTicket | undefined): string {
    return statut ? (BADGES_STATUT[statut] ?? 'secondary') : 'secondary';
  }

  badgeClassePriorite(priorite: PrioriteTicket): string {
    return BADGES_PRIORITE[priorite] ?? 'secondary';
  }

  agentLabel(ticket: TicketIncident): string {
    const personnel = ticket.personnel as Personnel | string;
    if (typeof personnel === 'string') {
      return personnel;
    }
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  materielLabel(ticket: TicketIncident): string {
    const materiel = ticket.materiel as MaterielInformatique | string;
    if (typeof materiel === 'string') {
      return materiel;
    }
    return `${materiel.marque} ${materiel.modele} (${materiel.numeroInventaire})`;
  }
}
