import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { DecisionConge } from '../../core/models/conge.model';

@Injectable({ providedIn: 'root' })
export class DecisionCongeApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<DecisionConge[]> {
    return this.http.get<DecisionConge[]>(`${API_BASE}/decisions-conge`);
  }

  getByPersonnel(personnelId: number): Observable<DecisionConge[]> {
    return this.http.get<DecisionConge[]>(`${API_BASE}/decisions-conge`, { params: { personnel: personnelId } });
  }

  getOne(id: number): Observable<DecisionConge> {
    return this.http.get<DecisionConge>(`${API_BASE}/decisions-conge/${id}`);
  }

  create(decision: DecisionConge): Observable<DecisionConge> {
    return this.http.post<DecisionConge>(`${API_BASE}/decisions-conge`, decision);
  }

  update(id: number, decision: DecisionConge): Observable<DecisionConge> {
    return this.http.put<DecisionConge>(`${API_BASE}/decisions-conge/${id}`, decision);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/decisions-conge/${id}`);
  }

  exportCsvUrl(): string {
    return `${API_BASE}/decisions-conge/export.csv`;
  }
}
