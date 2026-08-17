import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { DemandeDecision } from '../../core/models/conge.model';

@Injectable({ providedIn: 'root' })
export class DemandeDecisionApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<DemandeDecision[]> {
    return this.http.get<DemandeDecision[]>(`${API_BASE}/demandes-decision`);
  }

  getOne(id: number): Observable<DemandeDecision> {
    return this.http.get<DemandeDecision>(`${API_BASE}/demandes-decision/${id}`);
  }

  create(demande: DemandeDecision): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision`, demande);
  }

  uploadPiece1(id: number, fichier: File): Observable<DemandeDecision> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/piece1`, formData);
  }

  uploadPiece2(id: number, fichier: File): Observable<DemandeDecision> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/piece2`, formData);
  }

  /** RH Congé : vérifie les pièces, génère la DecisionConge (numéro/dates/nombre de jours) et transmet au RH Admin. */
  transmettre(id: number, numero: string, dateDecision: string, dateExpiration: string, nombreJours: number): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/transmettre`, {
      numero,
      dateDecision,
      dateExpiration,
      nombreJours,
    });
  }

  /** RH Congé (avant transmission, pièces incomplètes) ou RH Admin (après transmission, filet de sécurité). */
  rejeter(id: number, motifRejet: number, commentaire?: string | null): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/rejeter`, { motifRejet, commentaire });
  }

  /** RH Admin uniquement, depuis l'état "transmise" — valide la DecisionConge déjà créée, n'en crée pas de nouvelle. Déclenche le circuit papier hors application. */
  approuver(id: number): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/approuver`, {});
  }

  /** RH Admin uniquement, depuis l'état "approuvee" — confirme la vérification du papier signé revenu du circuit et le transmet au RH Congé. */
  confirmerRetour(id: number): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/confirmer-retour`, {});
  }

  /** RH Congé uniquement, depuis l'état "retournee" — confirme la remise physique et électronique à l'agent. */
  transmettreAgent(id: number): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/demandes-decision/${id}/transmettre-agent`, {});
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/demandes-decision/${id}`);
  }

  pieceUrl(id: number): string {
    return `${API_BASE}/pieces-decision/${id}`;
  }
}
