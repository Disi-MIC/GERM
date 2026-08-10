import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { Delegation } from '../../core/models/delegation.model';
import { UserRef } from '../../core/models/user.model';

@Injectable({ providedIn: 'root' })
export class DelegationApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<Delegation[]> {
    return this.http.get<Delegation[]>(`${API_BASE}/delegations`);
  }

  create(delegation: Delegation): Observable<Delegation> {
    return this.http.post<Delegation>(`${API_BASE}/delegations`, delegation);
  }

  revoke(id: number): Observable<Delegation> {
    return this.http.post<Delegation>(`${API_BASE}/delegations/${id}/revoke`, {});
  }

  getUsers(): Observable<UserRef[]> {
    return this.http.get<UserRef[]>(`${API_BASE}/users?actif=true`);
  }
}
