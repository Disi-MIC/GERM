import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { ListeValeurRef, Personnel, ServiceRef } from '../../core/models/personnel.model';

@Injectable({ providedIn: 'root' })
export class PersonnelApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<Personnel[]> {
    return this.http.get<Personnel[]>(`${API_BASE}/personnels.json`);
  }

  getOne(id: number): Observable<Personnel> {
    return this.http.get<Personnel>(`${API_BASE}/personnels/${id}.json`);
  }

  create(personnel: Personnel): Observable<Personnel> {
    return this.http.post<Personnel>(`${API_BASE}/personnels.json`, personnel);
  }

  update(id: number, personnel: Personnel): Observable<Personnel> {
    return this.http.put<Personnel>(`${API_BASE}/personnels/${id}.json`, personnel);
  }

  getServices(): Observable<ServiceRef[]> {
    return this.http.get<ServiceRef[]>(`${API_BASE}/services.json`);
  }

  getTypesContrat(): Observable<ListeValeurRef[]> {
    return this.http.get<ListeValeurRef[]>(`${API_BASE}/liste_valeurs.json`);
  }
}
