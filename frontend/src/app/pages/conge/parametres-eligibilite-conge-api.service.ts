import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { CategorieAgentConge, ParametreEligibiliteConge } from '../../core/models/parametre-eligibilite-conge.model';

@Injectable({ providedIn: 'root' })
export class ParametresEligibiliteCongeApiService {
  constructor(private readonly http: HttpClient) {}

  liste(): Observable<ParametreEligibiliteConge[]> {
    return this.http.get<ParametreEligibiliteConge[]>(`${API_BASE}/parametres-eligibilite-conge`);
  }

  update(
    categorie: CategorieAgentConge,
    parametres: Pick<ParametreEligibiliteConge, 'joursParMois' | 'plafondJours' | 'delaiEligibiliteMois'>,
  ): Observable<ParametreEligibiliteConge> {
    return this.http.put<ParametreEligibiliteConge>(`${API_BASE}/parametres-eligibilite-conge/${categorie}`, parametres);
  }
}
