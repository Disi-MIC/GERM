import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../../core/api-base';
import { DemandeCartePro } from '../../../core/models/demande-carte-pro.model';

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

  /** RH Carte Pro : transmet la demande (vérifiée) au RH Admin. */
  transmettre(id: number): Observable<DemandeCartePro> {
    return this.http.post<DemandeCartePro>(`${API_BASE}/demandes-carte-pro/${id}/transmettre`, {});
  }

  /** RH Carte Pro (avant transmission) ou RH Admin (après transmission). */
  rejeter(id: number, commentaire?: string | null): Observable<DemandeCartePro> {
    return this.http.post<DemandeCartePro>(`${API_BASE}/demandes-carte-pro/${id}/rejeter`, { commentaire });
  }

  /** RH Admin uniquement, depuis l'état "transmise" — crée ET valide la carte. */
  approuver(id: number, numero: string, dateDelivrance: string, commentaire?: string | null): Observable<DemandeCartePro> {
    return this.http.post<DemandeCartePro>(`${API_BASE}/demandes-carte-pro/${id}/approuver`, {
      numero,
      dateDelivrance,
      commentaire,
    });
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
