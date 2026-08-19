import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { ApercuMaDirection, ApercuMinistere, ApercuMonService } from '../../core/models/apercu-organisation.model';

@Injectable({ providedIn: 'root' })
export class ApercuOrganisationApiService {
  constructor(private readonly http: HttpClient) {}

  monService(): Observable<ApercuMonService> {
    return this.http.get<ApercuMonService>(`${API_BASE}/apercu-organisation/mon-service`);
  }

  maDirection(): Observable<ApercuMaDirection> {
    return this.http.get<ApercuMaDirection>(`${API_BASE}/apercu-organisation/ma-direction`);
  }

  ministere(direction?: number | null, service?: number | null, grade?: string | null): Observable<ApercuMinistere> {
    const params: Record<string, string> = {};
    if (direction) {
      params['direction'] = String(direction);
    }
    if (service) {
      params['service'] = String(service);
    }
    if (grade) {
      params['grade'] = grade;
    }
    return this.http.get<ApercuMinistere>(`${API_BASE}/apercu-organisation/ministere`, { params });
  }
}
