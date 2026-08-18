import { DemandeDecision } from '../core/models/conge.model';
import { EtapeTimeline } from './status-timeline/status-timeline.component';

function formatDate(iso?: string | null): string | null {
  return iso ? `${iso.slice(0, 10)} · ${iso.slice(11, 16)}` : null;
}

/**
 * Les 5 étapes du circuit d'une DemandeDecision (voir son commentaire de
 * classe côté serveur) pour StatusTimelineComponent — partagé entre la page
 * de traitement RH (demande-decision-traiter) et l'aperçu lecture seule
 * proposé à l'agent (demande-decision-apercu), mêmes règles des deux côtés.
 */
export function etapesTimelineDemandeDecision(d: DemandeDecision): EtapeTimeline[] {
  const creee: EtapeTimeline = { label: 'Créée', sousTitre: formatDate(d.createdAt), etat: 'termine' };

  if (d.statut === 'refusee') {
    return [creee, { label: 'Refusée', sousTitre: formatDate(d.dateTraitement), etat: 'rejete' }];
  }

  const valideeFaite = d.statut !== 'en_attente';
  const deposeeFaite = d.statut === 'deposee_courrier' || d.statut === 'retournee' || d.statut === 'transmise_agent';
  const retourneeFaite = d.statut === 'retournee' || d.statut === 'transmise_agent';

  return [
    creee,
    { label: 'Validée par le RH Congé', sousTitre: valideeFaite ? 'Fait' : null, etat: valideeFaite ? 'termine' : 'actuel' },
    {
      label: 'Déposée au service courrier',
      sousTitre: deposeeFaite ? 'Fait' : null,
      etat: deposeeFaite ? 'termine' : valideeFaite ? 'actuel' : 'a-venir',
    },
    {
      label: 'Revenue du circuit (RH Admin)',
      sousTitre: retourneeFaite ? 'Fait' : null,
      etat: retourneeFaite ? 'termine' : deposeeFaite ? 'actuel' : 'a-venir',
    },
    {
      label: "Transmise à l'agent",
      sousTitre: d.statut === 'transmise_agent' ? formatDate(d.dateTraitement) : null,
      etat: d.statut === 'transmise_agent' ? 'termine' : retourneeFaite ? 'actuel' : 'a-venir',
    },
  ];
}
