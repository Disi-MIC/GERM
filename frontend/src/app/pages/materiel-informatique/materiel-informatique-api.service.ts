import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { ComposantMateriel } from '../../core/models/composant-materiel.model';
import { HistoriqueAffectationMateriel } from '../../core/models/historique-affectation-materiel.model';
import { HistoriqueChangementMateriel } from '../../core/models/historique-changement-materiel.model';
import { MaterielInformatique } from '../../core/models/materiel-informatique.model';
import { TicketIncident } from '../../core/models/ticket-incident.model';

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

  getHistoriqueChangements(materielId: number): Observable<HistoriqueChangementMateriel[]> {
    return this.http.get<HistoriqueChangementMateriel[]>(`${API_BASE}/historiques-changement-materiel`, {
      params: { materiel: materielId },
    });
  }

  /**
   * En blob plutôt qu'une URL directe sur <img> : même raison que
   * CarteProApiService.getPdfBlob() — dans l'app mobile (Capacitor), une
   * requête d'image émise directement par la WebView (pas via
   * fetch/XHR/CapacitorHttp) ne transporte pas fiablement le cookie de
   * session vers l'origine cross-site du backend, contrairement à un appel
   * HttpClient classique.
   */
  getQrcodeBlob(id: number): Observable<Blob> {
    return this.http.get(`${API_BASE}/materiels-informatiques/${id}/qrcode`, { responseType: 'blob' });
  }

  creerTicket(
    id: number,
    payload: { personnelId?: number | null; titre: string; description: string; priorite: string },
  ): Observable<TicketIncident> {
    return this.http.post<TicketIncident>(`${API_BASE}/materiels-informatiques/${id}/tickets-incident`, payload);
  }

  bulkEtat(ids: number[], etatId: number): Observable<{ modifies: number }> {
    return this.http.patch<{ modifies: number }>(`${API_BASE}/materiels-informatiques/bulk-etat`, { ids, etat: etatId });
  }

  bulkAffectation(ids: number[], personnelId: number | null): Observable<{ modifies: number }> {
    return this.http.patch<{ modifies: number }>(`${API_BASE}/materiels-informatiques/bulk-affectation`, {
      ids,
      affecteA: personnelId,
    });
  }

  /** Résout le jeton chiffré d'une étiquette QR scannée (voir QrTokenService côté serveur) en identifiant de matériel. */
  resoudreQrcode(token: string): Observable<{ materielId: number }> {
    return this.http.get<{ materielId: number }>(`${API_BASE}/materiels-informatiques/resoudre-qrcode/${encodeURIComponent(token)}`);
  }

  /**
   * Composants matériels (RAM, disque dur HDD/SSD, carte graphique...) —
   * embarqués directement dans MaterielInformatique.composants en lecture
   * (voir getOne()/getAll() ci-dessus), donc pas de getComposants() séparé ;
   * seules l'écriture passe par ces trois méthodes.
   */
  creerComposant(materielId: number, composant: ComposantMateriel): Observable<ComposantMateriel> {
    return this.http.post<ComposantMateriel>(`${API_BASE}/composants-materiel`, {
      ...composant,
      materiel: `/api/materiels-informatiques/${materielId}`,
    });
  }

  modifierComposant(id: number, composant: ComposantMateriel): Observable<ComposantMateriel> {
    return this.http.put<ComposantMateriel>(`${API_BASE}/composants-materiel/${id}`, composant);
  }

  supprimerComposant(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/composants-materiel/${id}`);
  }
}
