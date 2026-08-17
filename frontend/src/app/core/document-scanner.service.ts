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
  disponible(): boolean {
    return Capacitor.isNativePlatform();
  }

  /** Renvoie un File JPEG unique (page unique : cohérent avec l'upload actuel, un fichier par document). */
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
    const blob = await reponse.blob();
    return new File([blob], `document-scanne-${Date.now()}.jpg`, { type: 'image/jpeg' });
  }
}
