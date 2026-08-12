import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { LicenceLogiciel } from '../../core/models/licence-logiciel.model';

@Injectable({ providedIn: 'root' })
export class LicencesLogiciellesApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<LicenceLogiciel[]> {
    return this.http.get<LicenceLogiciel[]>(`${API_BASE}/licences-logicielles`);
  }

  create(licence: LicenceLogiciel): Observable<LicenceLogiciel> {
    return this.http.post<LicenceLogiciel>(`${API_BASE}/licences-logicielles`, licence);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/licences-logicielles/${id}`);
  }
}
