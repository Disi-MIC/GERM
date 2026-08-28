import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef } from '../../../core/models/personnel.model';
import { StatutTicket, TicketIncident } from '../../../core/models/ticket-incident.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { DataTableCellDirective } from '../../../shared/data-table/data-table-cell.directive';
import { DataTableColumn } from '../../../shared/data-table/data-table-column.model';
import { DataTableMobileItemDirective } from '../../../shared/data-table/data-table-mobile-item.directive';
import { DataTableComponent } from '../../../shared/data-table/data-table.component';
import { TicketApercuComponent } from '../../../shared/ticket-apercu/ticket-apercu.component';
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
  imports: [
    RouterLink,
    SlicePipe,
    PageHeaderComponent,
    DataTableComponent,
    DataTableCellDirective,
    DataTableMobileItemDirective,
    TicketApercuComponent,
  ],
  templateUrl: './mes-tickets.component.html',
})
export class MesTicketsComponent implements OnInit {
  tickets: TicketIncident[] = [];
  loading = true;
  error: string | null = null;
  readonly labelsStatut = LABELS_STATUT;
  ticketEnApercu: TicketIncident | null = null;

  readonly columns: DataTableColumn<TicketIncident>[] = [
    { key: 'objet', label: 'Objet', sortable: true, value: (t) => t.titre },
    { key: 'materiel', label: 'Matériel', sortable: true, value: (t) => this.materielLabel(t) },
    { key: 'priorite', label: 'Priorité', sortable: true, value: (t) => this.prioriteLabel(t) },
    { key: 'statut', label: 'Statut', sortable: true, value: (t) => this.statutLabel(t.statut) },
    { key: 'creeLe', label: 'Créé le', sortable: true, value: (t) => t.createdAt },
    { key: 'action', label: 'Suivi', align: 'end', alwaysVisible: true },
  ];

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

  voirApercu(ticket: TicketIncident): void {
    this.ticketEnApercu = ticket;
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

  statutLabel(statut: StatutTicket | undefined): string {
    return statut ? LABELS_STATUT[statut] : '—';
  }
}
