import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { ParametresDecisionConge } from '../../core/models/parametres-decision-conge.model';

@Injectable({ providedIn: 'root' })
export class ParametresDecisionCongeApiService {
  constructor(private readonly http: HttpClient) {}

  get(): Observable<ParametresDecisionConge> {
    return this.http.get<ParametresDecisionConge>(`${API_BASE}/parametres-decision-conge`);
  }

  update(parametres: Pick<ParametresDecisionConge, 'visasDecrets' | 'article2' | 'article3'>): Observable<ParametresDecisionConge> {
    return this.http.put<ParametresDecisionConge>(`${API_BASE}/parametres-decision-conge`, parametres);
  }
}
