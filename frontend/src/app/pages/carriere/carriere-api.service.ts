import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { HistoriqueAffectation } from '../../core/models/historique-affectation.model';

@Injectable({ providedIn: 'root' })
export class CarriereApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<HistoriqueAffectation[]> {
    return this.http.get<HistoriqueAffectation[]>(`${API_BASE}/historique-affectations`);
  }

  getByPersonnel(personnelId: number): Observable<HistoriqueAffectation[]> {
    return this.http.get<HistoriqueAffectation[]>(`${API_BASE}/historique-affectations`, {
      params: { personnel: personnelId },
    });
  }

  create(mouvement: HistoriqueAffectation): Observable<HistoriqueAffectation> {
    return this.http.post<HistoriqueAffectation>(`${API_BASE}/historique-affectations`, mouvement);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/historique-affectations/${id}`);
  }
}
