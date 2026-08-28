import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { HistoriqueAffectationMateriel } from '../../core/models/historique-affectation-materiel.model';
import { MaterielInformatique } from '../../core/models/materiel-informatique.model';

/**
 * Pas de suffixe `.json` : l'entité déclare un `uriTemplate` explicite pour
 * ses opérations GetCollection/Get (voir MaterielInformatique), qui partage
 * les mêmes URLs kebab-case que les actions d'écriture ci-dessous — même
 * raison que CarteProApiService.
 */
@Injectable({ providedIn: 'root' })
export class MaterielInformatiqueApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<MaterielInformatique[]> {
    return this.http.get<MaterielInformatique[]>(`${API_BASE}/materiels-informatiques`);
  }

  getOne(id: number): Observable<MaterielInformatique> {
    return this.http.get<MaterielInformatique>(`${API_BASE}/materiels-informatiques/${id}`);
  }

  create(materiel: MaterielInformatique): Observable<MaterielInformatique> {
    return this.http.post<MaterielInformatique>(`${API_BASE}/materiels-informatiques`, materiel);
  }

  update(id: number, materiel: MaterielInformatique): Observable<MaterielInformatique> {
    return this.http.put<MaterielInformatique>(`${API_BASE}/materiels-informatiques/${id}`, materiel);
  }

  /**
   * PUT partiel : le contrôleur désérialise sur l'entité existante
   * (object_to_populate), donc seul `affecteA` est envoyé — les autres
   * champs du matériel restent inchangés. `null` renvoie le matériel au
   * stock (aucun propriétaire).
   */
  affecter(id: number, personnelId: number | null): Observable<MaterielInformatique> {
    return this.http.put<MaterielInformatique>(`${API_BASE}/materiels-informatiques/${id}`, {
      affecteA: personnelId ? `/api/personnels/${personnelId}` : null,
    });
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/materiels-informatiques/${id}`);
  }

  getHistoriqueAffectations(materielId: number): Observable<HistoriqueAffectationMateriel[]> {
    return this.http.get<HistoriqueAffectationMateriel[]>(`${API_BASE}/historique-affectations-materiel`, {
      params: { materiel: materielId },
    });
  }

  uploadPhoto(id: number, fichier: File): Observable<MaterielInformatique> {
    const formData = new FormData();
    formData.append('photoFichier', fichier);
    return this.http.post<MaterielInformatique>(`${API_BASE}/materiels-informatiques/${id}/photo`, formData);
  }

  photoUrl(id: number): string {
    return `${API_BASE}/materiels-informatiques/${id}/photo`;
  }

  exportCsvUrl(): string {
    return `${API_BASE}/materiels-informatiques/export.csv`;
  }
}
