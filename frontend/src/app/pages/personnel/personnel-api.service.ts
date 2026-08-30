import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { HistoriqueChangementPersonnel } from '../../core/models/historique-changement-personnel.model';
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
    return this.http.post<Personnel>(`${API_BASE}/personnels`, personnel);
  }

  update(id: number, personnel: Personnel): Observable<Personnel> {
    return this.http.put<Personnel>(`${API_BASE}/personnels/${id}`, personnel);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/personnels/${id}`);
  }

  uploadPhoto(id: number, fichier: File): Observable<Personnel> {
    const formData = new FormData();
    formData.append('photoFichier', fichier);
    return this.http.post<Personnel>(`${API_BASE}/personnels/${id}/photo`, formData);
  }

  photoUrl(id: number): string {
    return `${API_BASE}/personnels/${id}/photo`;
  }

  getServices(): Observable<ServiceRef[]> {
    return this.http.get<ServiceRef[]>(`${API_BASE}/services.json`);
  }

  getTypesContrat(): Observable<ListeValeurRef[]> {
    return this.http.get<ListeValeurRef[]>(`${API_BASE}/liste_valeurs.json`);
  }

  exportCsvUrl(): string {
    return `${API_BASE}/personnels/export.csv`;
  }

  getHistoriqueChangements(personnelId: number): Observable<HistoriqueChangementPersonnel[]> {
    return this.http.get<HistoriqueChangementPersonnel[]>(`${API_BASE}/historiques-changement-personnel`, {
      params: { personnel: personnelId },
    });
  }
}
