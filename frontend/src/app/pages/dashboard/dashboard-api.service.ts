import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { DashboardPersonnel } from '../../core/models/dashboard.model';

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
}
