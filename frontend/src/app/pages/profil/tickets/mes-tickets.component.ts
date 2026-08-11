import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { PrioriteTicket, StatutTicket, TicketIncident } from '../../../core/models/ticket-incident.model';
import { ProfilApiService } from '../profil-api.service';

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

@Component({
  selector: 'app-mes-tickets',
  standalone: true,
  imports: [RouterLink, SlicePipe],
  templateUrl: './mes-tickets.component.html',
})
export class MesTicketsComponent implements OnInit {
  tickets: TicketIncident[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsStatut = LABELS_STATUT;
  readonly labelsPriorite = LABELS_PRIORITE;

  constructor(private readonly api: ProfilApiService) {}

  ngOnInit(): void {
    this.api.getMesTickets().subscribe({
      next: (tickets) => {
        this.tickets = tickets;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger vos tickets.';
        this.loading = false;
      },
    });
  }

  materielLabel(ticket: TicketIncident): string {
    const materiel = ticket.materiel as MaterielInformatique | string;
    return typeof materiel === 'string' ? materiel : `${materiel.marque} ${materiel.modele} (${materiel.numeroInventaire})`;
  }

  badgeClasseStatut(statut: StatutTicket | undefined): string {
    return statut ? (BADGES_STATUT[statut] ?? 'secondary') : 'secondary';
  }
}
