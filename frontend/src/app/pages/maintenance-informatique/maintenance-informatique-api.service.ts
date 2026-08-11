import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { Maintenance } from '../../core/models/maintenance.model';

@Injectable({ providedIn: 'root' })
export class MaintenanceInformatiqueApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<Maintenance[]> {
    return this.http.get<Maintenance[]>(`${API_BASE}/maintenances`);
  }

  create(maintenance: Maintenance): Observable<Maintenance> {
    return this.http.post<Maintenance>(`${API_BASE}/maintenances`, maintenance);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/maintenances/${id}`);
  }
}
