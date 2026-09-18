import { Injectable } from '@angular/core';
import { Capacitor } from '@capacitor/core';
import { DocumentScanner, ResponseType } from '@capgo/capacitor-document-scanner';

/**
 * Numérisation native (VisionKit sur iOS, ML Kit sur Android — non utilisé ici,
 * app iOS uniquement) : cadrage/correction de perspective automatiques, bien
 * plus lisible qu'une simple photo pour une pièce d'identité ou un diplôme.
 * `disponible()` doit toujours être vérifié avant `scanner()` : sur web
 * (navigateur desktop, y compris ce même code servi hors app native), le
 * plugin n'a pas d'implémentation et l'appel échouerait.
 */
@Injectable({ providedIn: 'root' })
export class DocumentScannerService {
  /**
   * Cible de recompression, pas une limite d'upload — ce service est partagé
   * par tous les formulaires utilisant app-file-piece-input (Mes documents,
   * carte pro, congés, maintenance), dont la plupart plafonnent encore
   * l'upload à 10 Mo côté serveur (FileStorage::TAILLE_MAX_OCTETS) ; la
   * maintenance accepte désormais jusqu'à 30 Mo, mais recompresser sous 10 Mo
   * ici garantit qu'un même scan passe sur n'importe lequel de ces
   * formulaires sans jamais buter sur la limite la plus stricte. VisionKit
   * peut produire des JPEG bien au-delà de 10 Mo sur un document détaillé
   * pris avec un capteur récent — sans ce repli, l'agent verrait l'erreur de
   * taille du serveur après le scan plutôt qu'avant (aucun retour visuel
   * pendant la capture elle-même côté plugin).
   */
  private static readonly TAILLE_CIBLE_OCTETS = 10 * 1024 * 1024;

  disponible(): boolean {
    return Capacitor.isNativePlatform();
  }

  /** Renvoie un File JPEG unique (page unique : cohérent avec l'upload actuel, un fichier par document), recompressé sous 10 Mo si besoin. */
  async scanner(): Promise<File | null> {
    const resultat = await DocumentScanner.scanDocument({
      responseType: ResponseType.Base64,
      maxNumDocuments: 1,
      letUserAdjustCrop: true,
    });

    const image = resultat.scannedImages?.[0];
    if (!image) {
      return null;
    }

    const reponse = await fetch(`data:image/jpeg;base64,${image}`);
    let blob = await reponse.blob();

    if (blob.size > DocumentScannerService.TAILLE_CIBLE_OCTETS) {
      blob = await this.recompresser(blob);
    }

    return new File([blob], `document-scanne-${Date.now()}.jpg`, { type: 'image/jpeg' });
  }

  /**
   * Réencode en JPEG via canvas, qualité puis dimensions décroissantes,
   * jusqu'à repasser sous la cible ou épuisement des tentatives. La qualité
   * seule a des rendements décroissants sur une image déjà en haute
   * résolution (un document scanné à pleine résolution capteur reste gros
   * même à qualité très basse) — d'où le repli sur une réduction de
   * dimensions après quelques essais, qui converge quelle que soit la
   * résolution de départ. Si le canvas ou l'encodage échoue pour une raison
   * quelconque (ex. image corrompue), l'original est renvoyé tel quel plutôt
   * que de faire échouer tout le scan pour un problème de compression.
   */
  private async recompresser(blob: Blob): Promise<Blob> {
    let image: HTMLImageElement;
    try {
      image = await this.chargerImage(blob);
    } catch {
      return blob;
    }

    let largeur = image.naturalWidth;
    let hauteur = image.naturalHeight;
    let qualite = 0.85;
    let meilleur = blob;

    for (let tentative = 0; tentative < 8 && meilleur.size > DocumentScannerService.TAILLE_CIBLE_OCTETS; tentative++) {
      // Les 3 premiers essais ne touchent qu'à la qualité (perte la moins
      // visible) ; au-delà, la moitié de résolution en plus à chaque tour.
      if (tentative >= 3) {
        largeur = Math.round(largeur * 0.7);
        hauteur = Math.round(hauteur * 0.7);
      } else {
        qualite = Math.max(0.4, qualite - 0.15);
      }

      const canvas = document.createElement('canvas');
      canvas.width = largeur;
      canvas.height = hauteur;
      const contexte = canvas.getContext('2d');
      if (!contexte) {
        return meilleur;
      }
      contexte.drawImage(image, 0, 0, largeur, hauteur);

      const resultat = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/jpeg', qualite));
      if (!resultat) {
        return meilleur;
      }

      meilleur = resultat;
    }

    return meilleur;
  }

  private chargerImage(blob: Blob): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
      const url = URL.createObjectURL(blob);
      const image = new Image();
      image.onload = () => {
        URL.revokeObjectURL(url);
        resolve(image);
      };
      image.onerror = (erreur) => {
        URL.revokeObjectURL(url);
        reject(erreur instanceof ErrorEvent ? erreur.error : erreur);
      };
      image.src = url;
    });
  }
}
