import { TicketIncident } from '../core/models/ticket-incident.model';
import { EtapeTimeline } from './status-timeline/status-timeline.component';

function formatDate(iso?: string | null): string | null {
  return iso ? `${iso.slice(0, 10)} · ${iso.slice(11, 16)}` : null;
}

/**
 * Les étapes du circuit d'un TicketIncident pour StatusTimelineComponent —
 * partagé entre la page de traitement (ticket-informatique-traiter) et
 * l'aperçu lecture seule proposé à l'agent (ticket-apercu), mêmes règles des
 * deux côtés. Reflète toujours le statut courant (pas un historique figé) :
 * un ticket rouvert après résolution repasse par exemple "Résolu" en
 * 'actuel'. L'escalade de niveau (N1/N2/N3) reste hors de cette frise.
 */
export function etapesTimelineTicket(t: TicketIncident): EtapeTimeline[] {
  const ouvert: EtapeTimeline = { label: 'Ouvert', sousTitre: formatDate(t.createdAt), etat: 'termine' };

  if (t.statut === 'refuse') {
    return [ouvert, { label: 'Refusé', sousTitre: formatDate(t.dateResolution ?? t.dateCloture), etat: 'rejete' }];
  }

  const priseEnCharge = !!t.datePriseEnCharge;
  const resolu = t.statut === 'resolu' || t.statut === 'cloture';
  const cloture = t.statut === 'cloture';

  return [
    ouvert,
    {
      label: 'En cours',
      sousTitre: formatDate(t.datePriseEnCharge),
      etat: priseEnCharge ? 'termine' : 'actuel',
    },
    {
      label: 'Résolu',
      sousTitre: formatDate(t.dateResolution),
      etat: resolu ? 'termine' : priseEnCharge ? 'actuel' : 'a-venir',
    },
    {
      label: 'Clôturé',
      sousTitre: formatDate(t.dateCloture),
      etat: cloture ? 'termine' : resolu ? 'actuel' : 'a-venir',
    },
  ];
}
