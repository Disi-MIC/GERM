import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { DemandeDecision } from '../../core/models/conge.model';

export interface GenererEtTransmettrePayload {
  numero: string;
  dateDecision: string;
  dateExpiration: string;
  nombreJours: number;
  dateDerniereDecision?: string | null;
}

@Injectable({ providedIn: 'root' })
export class DemandeDecisionApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<DemandeDecision[]> {
    return this.http.get<DemandeDecision[]>(`${API_BASE}/demandes-decision`);
  }

  getOne(id: number): Observable<DemandeDecision> {
    return this.http.get<DemandeDecision>(`${API_BASE}/demandes-decision/${id}`);
  }

  create(demande: DemandeDecision): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision`, demande);
  }

  uploadPiece1(id: number, fichier: File): Observable<DemandeDecision> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/piece1`, formData);
  }

  uploadPiece2(id: number, fichier: File): Observable<DemandeDecision> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/piece2`, formData);
  }

  /** RH Congé, depuis "en_attente" — vérifie que les pièces attendues sont présentes, autorise l'agent à déposer au courrier. */
  valider(id: number): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/valider`, {});
  }

  /** RH Congé, depuis "en_attente" — motif prédéterminé obligatoire (pièces incomplètes). */
  rejeter(id: number, motifRejet: number, commentaire?: string | null): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/rejeter`, { motifRejet, commentaire });
  }

  /** RH Admin, depuis "deposee_courrier" — dépose les documents scannés reçus en retour du circuit ministériel. */
  retour(id: number, fichier: File): Observable<DemandeDecision> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/retour`, formData);
  }

  documentRetourUrl(id: number): string {
    return `${API_BASE}/demandes-decision/${id}/document-retour`;
  }

  /** RH Congé, depuis "retournee" — calcule/valide le nombre de jours, crée la DecisionConge, transmet à l'agent. */
  genererEtTransmettre(id: number, payload: GenererEtTransmettrePayload): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/generer-et-transmettre`, payload);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/demandes-decision/${id}`);
  }

  pieceUrl(id: number): string {
    return `${API_BASE}/pieces-decision/${id}`;
  }
}
