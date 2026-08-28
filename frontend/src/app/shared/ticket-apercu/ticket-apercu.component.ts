import { SlicePipe } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { ListeValeurRef } from '../../core/models/personnel.model';
import { StatutTicket, TicketIncident } from '../../core/models/ticket-incident.model';
import { EtapeTimeline, StatusTimelineComponent } from '../status-timeline/status-timeline.component';
import { etapesTimelineTicket } from '../ticket-etapes';

const LABELS_STATUT: Record<StatutTicket, string> = {
  ouvert: 'Ouvert',
  en_cours: 'En cours',
  resolu: 'Résolu',
  cloture: 'Clôturé',
  refuse: 'Refusé',
};

/**
 * Aperçu en lecture seule du statut d'un ticket (la même timeline que voit
 * le support sur ticket-informatique-traiter, voir etapesTimelineTicket()) —
 * donne à l'agent une vue équivalente sur son propre ticket, sans les
 * actions de traitement/escalade réservées au support.
 */
@Component({
  selector: 'app-ticket-apercu',
  standalone: true,
  imports: [SlicePipe, StatusTimelineComponent],
  templateUrl: './ticket-apercu.component.html',
  styleUrl: './ticket-apercu.component.scss',
})
export class TicketApercuComponent {
  @Input() ticket: TicketIncident | null = null;
  @Output() fermer = new EventEmitter<void>();

  readonly labelsStatut = LABELS_STATUT;

  get etapesTimeline(): EtapeTimeline[] {
    return this.ticket ? etapesTimelineTicket(this.ticket) : [];
  }

  prioriteLabel(): string {
    const priorite = this.ticket?.priorite as ListeValeurRef | string | undefined;
    return priorite && typeof priorite !== 'string' ? priorite.libelle : (priorite ?? '');
  }
}
