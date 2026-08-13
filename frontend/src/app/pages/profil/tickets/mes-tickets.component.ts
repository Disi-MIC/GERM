import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { StatutTicket, TicketIncident } from '../../../core/models/ticket-incident.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
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

@Component({
  selector: 'app-mes-tickets',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './mes-tickets.component.html',
})
export class MesTicketsComponent implements OnInit {
  tickets: TicketIncident[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsStatut = LABELS_STATUT;

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

  prioriteLabel(ticket: TicketIncident): string {
    const priorite = ticket.priorite as ListeValeurRef | string;
    return typeof priorite === 'string' ? priorite : priorite.libelle;
  }

  badgeClasseStatut(statut: StatutTicket | undefined): string {
    return statut ? (BADGES_STATUT[statut] ?? 'secondary') : 'secondary';
  }
}
