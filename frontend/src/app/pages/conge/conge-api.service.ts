import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { Conge } from '../../core/models/conge.model';

@Injectable({ providedIn: 'root' })
export class CongeApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<Conge[]> {
    return this.http.get<Conge[]>(`${API_BASE}/conges`);
  }

  getByPersonnel(personnelId: number): Observable<Conge[]> {
    return this.http.get<Conge[]>(`${API_BASE}/conges`, { params: { personnel: personnelId } });
  }

  getOne(id: number): Observable<Conge> {
    return this.http.get<Conge>(`${API_BASE}/conges/${id}`);
  }

  create(conge: Conge): Observable<Conge> {
    return this.http.post<Conge>(`${API_BASE}/conges`, conge);
  }

  update(id: number, conge: Conge): Observable<Conge> {
    return this.http.put<Conge>(`${API_BASE}/conges/${id}`, conge);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/conges/${id}`);
  }
}
