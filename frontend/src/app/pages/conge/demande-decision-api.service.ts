import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { DemandeDecision } from '../../core/models/conge.model';

export interface TraiterDecisionPayload {
  decision: 'approuver' | 'refuser';
  commentaire?: string | null;
  numero_decision?: string;
  date_decision?: string;
  date_expiration?: string;
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

  traiter(id: number, payload: TraiterDecisionPayload): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/traiter`, payload);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/demandes-decision/${id}`);
  }

  pieceUrl(id: number): string {
    return `${API_BASE}/pieces-decision/${id}`;
  }
}
