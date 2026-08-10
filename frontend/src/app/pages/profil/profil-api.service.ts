import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { CarteProfessionnelle } from '../../core/models/carte-professionnelle.model';
import { Conge } from '../../core/models/conge.model';
import { DemandeCartePro } from '../../core/models/demande-carte-pro.model';
import { HistoriqueAffectation } from '../../core/models/historique-affectation.model';
import { MaterielInformatique } from '../../core/models/materiel-informatique.model';
import { Personnel } from '../../core/models/personnel.model';
import { Vehicule } from '../../core/models/vehicule.model';

@Injectable({ providedIn: 'root' })
export class ProfilApiService {
  constructor(private readonly http: HttpClient) {}

  getMonPersonnel(): Observable<Personnel> {
    return this.http.get<Personnel>(`${API_BASE}/me/personnel`);
  }

  photoUrl(): string {
    return `${API_BASE}/me/personnel/photo`;
  }

  getMaCarriere(): Observable<HistoriqueAffectation[]> {
    return this.http.get<HistoriqueAffectation[]>(`${API_BASE}/me/historique-affectations`);
  }

  getMesConges(): Observable<Conge[]> {
    return this.http.get<Conge[]>(`${API_BASE}/me/conges`);
  }

  getMesCartesProfessionnelles(): Observable<CarteProfessionnelle[]> {
    return this.http.get<CarteProfessionnelle[]>(`${API_BASE}/me/cartes-professionnelles`);
  }

  getMesDemandesCartePro(): Observable<DemandeCartePro[]> {
    return this.http.get<DemandeCartePro[]>(`${API_BASE}/me/demandes-carte-pro`);
  }

  getMesMateriels(): Observable<MaterielInformatique[]> {
    return this.http.get<MaterielInformatique[]>(`${API_BASE}/me/materiels`);
  }

  getMesVehicules(): Observable<Vehicule[]> {
    return this.http.get<Vehicule[]>(`${API_BASE}/me/vehicules`);
  }
}
