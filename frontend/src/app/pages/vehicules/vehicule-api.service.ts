import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { BonEssence } from '../../core/models/bon-essence.model';
import { HistoriqueVidange } from '../../core/models/historique-vidange.model';
import { Vehicule } from '../../core/models/vehicule.model';

/**
 * Pas de suffixe `.json` : l'entité déclare un `uriTemplate` explicite pour
 * ses opérations GetCollection/Get (voir Vehicule), qui partage les mêmes
 * URLs kebab-case que les actions d'écriture ci-dessous — même raison que
 * MaterielInformatiqueApiService.
 */
@Injectable({ providedIn: 'root' })
export class VehiculeApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<Vehicule[]> {
    return this.http.get<Vehicule[]>(`${API_BASE}/vehicules`);
  }

  getOne(id: number): Observable<Vehicule> {
    return this.http.get<Vehicule>(`${API_BASE}/vehicules/${id}`);
  }

  create(vehicule: Vehicule): Observable<Vehicule> {
    return this.http.post<Vehicule>(`${API_BASE}/vehicules`, vehicule);
  }

  update(id: number, vehicule: Vehicule): Observable<Vehicule> {
    return this.http.put<Vehicule>(`${API_BASE}/vehicules/${id}`, vehicule);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/vehicules/${id}`);
  }

  getHistoriqueVidanges(vehiculeId: number): Observable<HistoriqueVidange[]> {
    return this.http.get<HistoriqueVidange[]>(`${API_BASE}/historique-vidanges`, { params: { vehicule: vehiculeId } });
  }

  creerVidange(vidange: HistoriqueVidange): Observable<HistoriqueVidange> {
    return this.http.post<HistoriqueVidange>(`${API_BASE}/historique-vidanges`, vidange);
  }

  supprimerVidange(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/historique-vidanges/${id}`);
  }

  getBonsEssence(vehiculeId: number): Observable<BonEssence[]> {
    return this.http.get<BonEssence[]>(`${API_BASE}/bons-essence`, { params: { vehicule: vehiculeId } });
  }

  creerBonEssence(bon: BonEssence): Observable<BonEssence> {
    return this.http.post<BonEssence>(`${API_BASE}/bons-essence`, bon);
  }

  supprimerBonEssence(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/bons-essence/${id}`);
  }

  /**
   * En blob plutôt qu'une URL directe sur l'iframe : même raison que
   * CarteProApiService.getPdfBlob() — dans l'app mobile (Capacitor), une
   * requête émise directement par la WebView ne transporte pas fiablement le
   * cookie de session vers l'origine cross-site du backend.
   */
  getCartePdfBlob(id: number): Observable<Blob> {
    return this.http.get(`${API_BASE}/vehicules/${id}/carte`, { responseType: 'blob' });
  }
}
