import { SlicePipe } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { DemandeCartePro } from '../../core/models/demande-carte-pro.model';
import { EtapeTimeline, StatusTimelineComponent } from '../status-timeline/status-timeline.component';
import { etapesTimelineDemandeCartePro } from '../demande-carte-pro-etapes';

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

/**
 * Aperçu en lecture seule du statut d'une demande de carte professionnelle
 * (la même timeline que voit le RH sur demande-carte-pro-traiter, voir
 * etapesTimelineDemandeCartePro()) — donne à l'agent une vue équivalente sur
 * sa propre demande, sans les actions de traitement réservées au RH.
 */
@Component({
  selector: 'app-demande-carte-pro-apercu',
  standalone: true,
  imports: [SlicePipe, StatusTimelineComponent],
  templateUrl: './demande-carte-pro-apercu.component.html',
  styleUrl: './demande-carte-pro-apercu.component.scss',
})
export class DemandeCarteProApercuComponent {
  @Input() demande: DemandeCartePro | null = null;
  @Output() fermer = new EventEmitter<void>();

  readonly labelsStatut = LABELS_STATUT;
  readonly labelsType = LABELS_TYPE;

  get etapesTimeline(): EtapeTimeline[] {
    return this.demande ? etapesTimelineDemandeCartePro(this.demande) : [];
  }
}
