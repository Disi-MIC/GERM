import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { Service } from '../../core/models/service.model';

@Injectable({ providedIn: 'root' })
export class ServicesApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<Service[]> {
    return this.http.get<Service[]>(`${API_BASE}/services`);
  }

  getOne(id: number): Observable<Service> {
    return this.http.get<Service>(`${API_BASE}/services/${id}`);
  }

  create(service: Service): Observable<Service> {
    return this.http.post<Service>(`${API_BASE}/services`, service);
  }

  update(id: number, service: Service): Observable<Service> {
    return this.http.put<Service>(`${API_BASE}/services/${id}`, service);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/services/${id}`);
  }

  uploadNoteService(id: number, fichier: File): Observable<Service> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<Service>(`${API_BASE}/services/${id}/note-service`, formData);
  }

  noteServiceUrl(id: number): string {
    return `${API_BASE}/services/${id}/note-service`;
  }
}
