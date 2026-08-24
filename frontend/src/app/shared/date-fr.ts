const MOIS_FR = [
  'janvier',
  'février',
  'mars',
  'avril',
  'mai',
  'juin',
  'juillet',
  'août',
  'septembre',
  'octobre',
  'novembre',
  'décembre',
];

/**
 * "2023-03-24" -> "24 mars 2023" (convention du document de décision de
 * congé, voir le modèle papier fourni par le ministère) — accepte une
 * chaîne ISO ou un Date déjà en heure locale. Découpe la chaîne ISO à la
 * main plutôt que new Date(iso) pour éviter tout décalage de fuseau horaire
 * (new Date('2023-03-24') vaut minuit UTC, donc potentiellement la veille
 * dans un fuseau négatif).
 */
export function dateFr(valeur: string | Date | null | undefined): string {
  if (!valeur) {
    return '';
  }

  let jour: number;
  let mois: number;
  let annee: number;

  if (valeur instanceof Date) {
    jour = valeur.getDate();
    mois = valeur.getMonth() + 1;
    annee = valeur.getFullYear();
  } else {
    [annee, mois, jour] = valeur.slice(0, 10).split('-').map(Number);
  }

  if (!annee || !mois || !jour) {
    return typeof valeur === 'string' ? valeur.slice(0, 10) : '';
  }

  return `${jour} ${MOIS_FR[mois - 1]} ${annee}`;
}

/** "2026-08" -> "août 2026" — clé "YYYY-MM" telle que renvoyée par DashboardController::calculerCartouches() (buckets mensuels, pas de jour). */
export function moisAnneeFr(cle: string): string {
  const [annee, mois] = cle.split('-').map(Number);
  if (!annee || !mois) {
    return cle;
  }
  return `${MOIS_FR[mois - 1]} ${annee}`;
}
