import { SlicePipe } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { DemandeDecision } from '../../core/models/conge.model';
import { EtapeTimeline, StatusTimelineComponent } from '../status-timeline/status-timeline.component';
import { etapesTimelineDemandeDecision } from '../demande-decision-etapes';

const LABELS_STATUT: Record<string, string> = {
  en_attente: 'En attente',
  validee: 'Validée, à déposer au courrier',
  deposee_courrier: 'Déposée au service courrier',
  retournee: 'Revenue du circuit, à finaliser',
  transmise_agent: "Transmise à l'agent",
  refusee: 'Refusée',
};

/**
 * Aperçu en lecture seule du statut d'une demande de décision de congé (la
 * même timeline que voit le RH Congé sur demande-decision-traiter, voir
 * etapesTimelineDemandeDecision()) — donne à l'agent une vue équivalente
 * sur sa propre demande, sans les actions de traitement réservées au RH.
 */
@Component({
  selector: 'app-demande-decision-apercu',
  standalone: true,
  imports: [SlicePipe, StatusTimelineComponent],
  templateUrl: './demande-decision-apercu.component.html',
  styleUrl: './demande-decision-apercu.component.scss',
})
export class DemandeDecisionApercuComponent {
  @Input() demande: DemandeDecision | null = null;
  @Output() fermer = new EventEmitter<void>();

  readonly labelsStatut = LABELS_STATUT;

  get etapesTimeline(): EtapeTimeline[] {
    return this.demande ? etapesTimelineDemandeDecision(this.demande) : [];
  }
}
