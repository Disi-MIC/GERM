const UNITES = [
  'zéro',
  'un',
  'deux',
  'trois',
  'quatre',
  'cinq',
  'six',
  'sept',
  'huit',
  'neuf',
  'dix',
  'onze',
  'douze',
  'treize',
  'quatorze',
  'quinze',
  'seize',
];

const DIZAINES: Record<number, string> = {
  2: 'vingt',
  3: 'trente',
  4: 'quarante',
  5: 'cinquante',
  6: 'soixante',
};

/**
 * Nombre entier (0-99) écrit en toutes lettres — le document de décision de
 * congé l'exige à côté du chiffre ("Quatre-vingt-dix (90) jours"), comme le
 * modèle papier fourni par le ministère. Couvre les irrégularités du
 * français (soixante-dix, quatre-vingts, pas de "et" après quatre-vingt) ;
 * au-delà de 99 (jamais atteint ici, le nombre de jours est plafonné à 90),
 * retombe sur le chiffre brut plutôt que de mal orthographier.
 */
export function nombreEnLettresFr(n: number): string {
  if (!Number.isInteger(n) || n < 0) {
    return String(n);
  }
  if (n <= 16) {
    return UNITES[n];
  }
  if (n <= 19) {
    return `dix-${UNITES[n - 10]}`;
  }
  if (n <= 69) {
    const dizaine = Math.floor(n / 10);
    const unite = n % 10;
    const mot = DIZAINES[dizaine];
    if (unite === 0) {
      return mot;
    }
    if (unite === 1) {
      return `${mot} et un`;
    }
    return `${mot}-${UNITES[unite]}`;
  }
  if (n <= 79) {
    const reste = n - 60;
    return reste === 11 ? 'soixante et onze' : `soixante-${nombreEnLettresFr(reste)}`;
  }
  if (n <= 99) {
    const reste = n - 80;
    return reste === 0 ? 'quatre-vingts' : `quatre-vingt-${nombreEnLettresFr(reste)}`;
  }
  return String(n);
}
