import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import {
  DashboardCartesProfessionnelles,
  DashboardConges,
  DashboardInformatique,
  DashboardPersonnel,
} from '../../core/models/dashboard.model';

@Injectable({ providedIn: 'root' })
export class DashboardApiService {
  constructor(private readonly http: HttpClient) {}

  getPersonnel(direction?: number | null, service?: number | null): Observable<DashboardPersonnel> {
    const params: Record<string, string> = {};
    if (direction) {
      params['direction'] = String(direction);
    }
    if (service) {
      params['service'] = String(service);
    }
    return this.http.get<DashboardPersonnel>(`${API_BASE}/dashboard/personnel`, { params });
  }

  getConges(): Observable<DashboardConges> {
    return this.http.get<DashboardConges>(`${API_BASE}/dashboard/conges`);
  }

  getCartesProfessionnelles(): Observable<DashboardCartesProfessionnelles> {
    return this.http.get<DashboardCartesProfessionnelles>(`${API_BASE}/dashboard/cartes-professionnelles`);
  }

  getInformatique(): Observable<DashboardInformatique> {
    return this.http.get<DashboardInformatique>(`${API_BASE}/dashboard/informatique`);
  }
}
