import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { CategorieAgentConge } from '../../core/models/parametre-eligibilite-conge.model';
import { ParametresDecisionConge } from '../../core/models/parametres-decision-conge.model';

@Injectable({ providedIn: 'root' })
export class ParametresDecisionCongeApiService {
  constructor(private readonly http: HttpClient) {}

  liste(): Observable<ParametresDecisionConge[]> {
    return this.http.get<ParametresDecisionConge[]>(`${API_BASE}/parametres-decision-conge`);
  }

  update(
    categorie: CategorieAgentConge,
    parametres: Pick<ParametresDecisionConge, 'visasDecrets' | 'article2' | 'article3' | 'ampliations'>,
  ): Observable<ParametresDecisionConge> {
    return this.http.put<ParametresDecisionConge>(`${API_BASE}/parametres-decision-conge/${categorie}`, parametres);
  }
}
