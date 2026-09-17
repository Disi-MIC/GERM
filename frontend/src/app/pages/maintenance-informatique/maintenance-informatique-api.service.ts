import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { Maintenance, MaintenancePayload } from '../../core/models/maintenance.model';

@Injectable({ providedIn: 'root' })
export class MaintenanceInformatiqueApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<Maintenance[]> {
    return this.http.get<Maintenance[]>(`${API_BASE}/maintenances`);
  }

  create(payload: MaintenancePayload): Observable<Maintenance> {
    const formData = new FormData();
    formData.append('materiel', String(payload.materiel));
    formData.append('type', String(payload.type));
    formData.append('description', payload.description);
    formData.append('dateRealisation', payload.dateRealisation);
    if (payload.realisePar) {
      formData.append('realisePar', String(payload.realisePar));
    }
    if (payload.prestataireExterne) {
      formData.append('prestataireExterne', payload.prestataireExterne);
    }
    if (payload.ticketOrigine) {
      formData.append('ticketOrigine', String(payload.ticketOrigine));
    }
    if (payload.observations) {
      formData.append('observations', payload.observations);
    }
    if (payload.fichier) {
      formData.append('fichier', payload.fichier);
    }

    return this.http.post<Maintenance>(`${API_BASE}/maintenances`, formData);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/maintenances/${id}`);
  }

  pieceUrl(id: number): string {
    return `${API_BASE}/maintenances/${id}/piece`;
  }
}
