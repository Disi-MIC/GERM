import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Directory, Filesystem } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';
import { firstValueFrom } from 'rxjs';

/**
 * Ouvre un PDF protégé par session sur natif (app mobile).
 *
 * `Browser.open()` (SFSafariViewController) NE PARTAGE PAS le cookie de
 * session de la WebView de l'app : c'est un contexte de navigation Safari à
 * part, avec son propre stockage de cookies. Un lien vers un PDF authentifié
 * ouvert ainsi échoue donc toujours en "Full authentication is required",
 * même juste après un login réussi dans l'app.
 *
 * Solution : récupérer le PDF nous-mêmes via une requête HttpClient
 * authentifiée normale (elle passe par CapacitorHttp → le même cookie jar
 * natif que le login), l'écrire dans le cache local via Filesystem, puis
 * confier ce fichier LOCAL à la feuille de partage native (Share) — qui
 * sait afficher un aperçu (Quick Look) et proposer d'enregistrer dans
 * Fichiers, envoyer par Mail/Messages, etc. Aucune requête réseau
 * supplémentaire n'est alors faite hors du contexte authentifié de l'app.
 */
@Injectable({ providedIn: 'root' })
export class NativePdfService {
  constructor(private readonly http: HttpClient) {}

  async ouvrir(url: string, nomFichier: string): Promise<void> {
    const blob = await firstValueFrom(this.http.get(url, { responseType: 'blob' }));
    const base64 = await this.blobEnBase64(blob);
    const fichier = await Filesystem.writeFile({
      path: nomFichier,
      data: base64,
      directory: Directory.Cache,
    });
    await Share.share({ url: fichier.uri, title: nomFichier });
  }

  private blobEnBase64(blob: Blob): Promise<string> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onloadend = () => resolve(((reader.result as string) ?? '').split(',')[1] ?? '');
      reader.onerror = () => reject(reader.error);
      reader.readAsDataURL(blob);
    });
  }
}
