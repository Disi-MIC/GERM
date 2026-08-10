import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { DemandeJouissance } from '../../core/models/conge.model';

export interface TraiterJouissancePayload {
  decision: 'approuver' | 'refuser';
  commentaire?: string | null;
}

@Injectable({ providedIn: 'root' })
export class DemandeJouissanceApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<DemandeJouissance[]> {
    return this.http.get<DemandeJouissance[]>(`${API_BASE}/demandes-jouissance`);
  }

  getOne(id: number): Observable<DemandeJouissance> {
    return this.http.get<DemandeJouissance>(`${API_BASE}/demandes-jouissance/${id}`);
  }

  create(demande: DemandeJouissance): Observable<DemandeJouissance> {
    return this.http.post<DemandeJouissance>(`${API_BASE}/demandes-jouissance`, demande);
  }

  uploadPiece1(id: number, fichier: File): Observable<DemandeJouissance> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<DemandeJouissance>(`${API_BASE}/demandes-jouissance/${id}/piece1`, formData);
  }

  uploadPiece2(id: number, fichier: File): Observable<DemandeJouissance> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<DemandeJouissance>(`${API_BASE}/demandes-jouissance/${id}/piece2`, formData);
  }

  traiter(id: number, payload: TraiterJouissancePayload): Observable<DemandeJouissance> {
    return this.http.post<DemandeJouissance>(`${API_BASE}/demandes-jouissance/${id}/traiter`, payload);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/demandes-jouissance/${id}`);
  }

  pieceUrl(id: number): string {
    return `${API_BASE}/pieces-jouissance/${id}`;
  }
}
