import { DemandeCartePro } from '../core/models/demande-carte-pro.model';
import { EtapeTimeline } from './status-timeline/status-timeline.component';

function formatDate(iso?: string | null): string | null {
  return iso ? `${iso.slice(0, 10)} · ${iso.slice(11, 16)}` : null;
}

/**
 * Les étapes du circuit d'une DemandeCartePro pour StatusTimelineComponent —
 * partagé entre la page de traitement RH (demande-carte-pro-traiter) et
 * l'aperçu lecture seule proposé à l'agent (demande-carte-pro-apercu), mêmes
 * règles des deux côtés.
 *
 * Le workflow réel (transmise → approuvée) ne conserve aucune date de
 * transmission ; en cas de refus, impossible de savoir avec certitude à quel
 * stade il est intervenu (RH Carte Pro comme RH Admin peuvent rejeter). On
 * affiche donc alors seulement "Créée → Refusée", plutôt qu'une étape
 * intermédiaire dont on ne peut garantir l'exactitude.
 */
export function etapesTimelineDemandeCartePro(d: DemandeCartePro): EtapeTimeline[] {
  const creee: EtapeTimeline = { label: 'Créée', sousTitre: formatDate(d.createdAt), etat: 'termine' };

  if (d.statut === 'refusee') {
    return [creee, { label: 'Refusée', sousTitre: formatDate(d.dateTraitement), etat: 'rejete' }];
  }

  const transmiseFaite = d.statut === 'transmise' || d.statut === 'approuvee';
  return [
    creee,
    {
      label: 'Transmise au RH Admin',
      sousTitre: transmiseFaite ? 'Fait' : null,
      etat: transmiseFaite ? 'termine' : 'actuel',
    },
    {
      label: 'Approuvée',
      sousTitre: d.statut === 'approuvee' ? formatDate(d.dateTraitement) : null,
      etat: d.statut === 'approuvee' ? 'termine' : transmiseFaite ? 'actuel' : 'a-venir',
    },
  ];
}
