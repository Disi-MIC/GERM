import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { NiveauTicket, StatutTicket, TicketIncident } from '../../../core/models/ticket-incident.model';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef, Personnel } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
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

/**
 * Couleurs pour les 4 codes de priorité livrés par défaut — purement
 * cosmétique, un code ajouté depuis le paramétrage superadmin retombe sur
 * 'secondary' (voir badgeClassePriorite). Contrairement à BADGES_STATUT, ce
 * n'est plus un Record exhaustif puisque la liste des priorités n'est plus
 * figée dans le code.
 */
const BADGES_PRIORITE: Record<string, string> = {
  basse: 'secondary',
  normale: 'info',
  haute: 'warning',
  critique: 'danger',
};

const LABELS_NIVEAU: Record<NiveauTicket, string> = {
  n1: 'N1',
  n2: 'N2',
  n3: 'N3',
};

@Component({
  selector: 'app-tickets-informatique-list',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, PanelComponent],
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
  readonly labelsNiveau = LABELS_NIVEAU;

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

  prioriteLabel(ticket: TicketIncident): string {
    const priorite = ticket.priorite as ListeValeurRef | string;
    return typeof priorite === 'string' ? priorite : priorite.libelle;
  }

  badgeClassePriorite(ticket: TicketIncident): string {
    const priorite = ticket.priorite as ListeValeurRef | string;
    const code = typeof priorite === 'string' ? priorite : priorite.code;
    return BADGES_PRIORITE[code] ?? 'secondary';
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
