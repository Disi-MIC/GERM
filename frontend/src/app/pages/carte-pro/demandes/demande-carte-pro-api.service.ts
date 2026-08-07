import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../../core/api-base';
import { DemandeCartePro } from '../../../core/models/demande-carte-pro.model';

export interface TraiterPayload {
  decision: 'approuver' | 'refuser';
  commentaire?: string | null;
  numero?: string;
  dateDelivrance?: string;
}

@Injectable({ providedIn: 'root' })
export class DemandeCarteProApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<DemandeCartePro[]> {
    return this.http.get<DemandeCartePro[]>(`${API_BASE}/demandes-carte-pro`);
  }

  getOne(id: number): Observable<DemandeCartePro> {
    return this.http.get<DemandeCartePro>(`${API_BASE}/demandes-carte-pro/${id}`);
  }

  create(demande: DemandeCartePro): Observable<DemandeCartePro> {
    return this.http.post<DemandeCartePro>(`${API_BASE}/demandes-carte-pro`, demande);
  }

  traiter(id: number, payload: TraiterPayload): Observable<DemandeCartePro> {
    return this.http.post<DemandeCartePro>(`${API_BASE}/demandes-carte-pro/${id}/traiter`, payload);
  }

  uploadPiece(id: number, fichier: File): Observable<DemandeCartePro> {
    const formData = new FormData();
    formData.append('fichier', fichier);

    return this.http.post<DemandeCartePro>(`${API_BASE}/demandes-carte-pro/${id}/piece`, formData);
  }

  pieceUrl(id: number): string {
    return `${API_BASE}/demandes-carte-pro/${id}/piece`;
  }
}
