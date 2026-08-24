import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { ChangementCartouche } from '../../core/models/changement-cartouche.model';

@Injectable({ providedIn: 'root' })
export class CartouchesInformatiqueApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<ChangementCartouche[]> {
    return this.http.get<ChangementCartouche[]>(`${API_BASE}/changements-cartouche`);
  }

  create(changement: ChangementCartouche): Observable<ChangementCartouche> {
    return this.http.post<ChangementCartouche>(`${API_BASE}/changements-cartouche`, changement);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/changements-cartouche/${id}`);
  }
}
