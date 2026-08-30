/**
 * Extrait le jeton chiffré d'une étiquette QR matériel (voir
 * QrTokenService côté serveur) — utilisé à la fois par le scanner intégré
 * (MaterielInformatiqueScannerComponent) et par l'écoute des liens
 * `germ://` ouverts depuis l'extérieur de l'app (AppComponent), pour ne
 * pas dupliquer le format `germ://materiel/{token}` aux deux endroits.
 */
export function extraireTokenMateriel(code: string): string | null {
  const correspondance = /^germ:\/\/materiel\/(.+)$/.exec(code.trim());
  return correspondance ? correspondance[1] : null;
}
