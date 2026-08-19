import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { Direction } from '../../core/models/direction.model';

@Injectable({ providedIn: 'root' })
export class DirectionsApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<Direction[]> {
    return this.http.get<Direction[]>(`${API_BASE}/directions`);
  }

  getOne(id: number): Observable<Direction> {
    return this.http.get<Direction>(`${API_BASE}/directions/${id}`);
  }

  create(direction: Direction): Observable<Direction> {
    return this.http.post<Direction>(`${API_BASE}/directions`, direction);
  }

  update(id: number, direction: Direction): Observable<Direction> {
    return this.http.put<Direction>(`${API_BASE}/directions/${id}`, direction);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/directions/${id}`);
  }

  uploadNoteService(id: number, fichier: File): Observable<Direction> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<Direction>(`${API_BASE}/directions/${id}/note-service`, formData);
  }

  noteServiceUrl(id: number): string {
    return `${API_BASE}/directions/${id}/note-service`;
  }
}
