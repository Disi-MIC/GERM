import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { CarteProfessionnelle } from '../../core/models/carte-professionnelle.model';
import { Conge, DecisionConge, DemandeDecision, DemandeJouissance, TypeConge } from '../../core/models/conge.model';
import { DashboardMe } from '../../core/models/dashboard-me.model';
import { DemandeCartePro, TypeDemandeCartePro } from '../../core/models/demande-carte-pro.model';
import { DocumentAdministratif } from '../../core/models/document-administratif.model';
import { HistoriqueAffectation } from '../../core/models/historique-affectation.model';
import { MaterielInformatique } from '../../core/models/materiel-informatique.model';
import { ListeValeurRef, Personnel } from '../../core/models/personnel.model';
import { TicketIncident } from '../../core/models/ticket-incident.model';
import { Vehicule } from '../../core/models/vehicule.model';

/**
 * Auto-service : 'carteReferenceId'/'decisionId' sont de simples identifiants
 * numériques (jamais des IRI) — les ressources Personnel/CarteProfessionnelle/
 * DecisionConge ont un Get natif réservé au RH, donc le serveur les résout
 * lui-même par id plutôt que via la désérialisation API Platform habituelle.
 */
export interface DemandeCarteProSelfPayload {
  typeDemande: TypeDemandeCartePro;
  carteReferenceId?: number | null;
  motif?: string | null;
}

export interface DemandeJouissanceSelfPayload {
  type: TypeConge;
  decisionId?: number | null;
  dateDebut: string;
  dateFin: string;
  motif?: string | null;
}

/**
 * 'materielId' est un identifiant numérique, pas une IRI — voir
 * MeDemandesController::creerTicket(). 'priorite' est le `code` d'une
 * ListeValeur (catégorie priorite-ticket), pas un id/IRI non plus : le
 * serveur la résout par catégorie+code, comme materielId.
 */
export interface TicketIncidentSelfPayload {
  materielId: number;
  titre: string;
  description: string;
  priorite: string;
}

@Injectable({ providedIn: 'root' })
export class ProfilApiService {
  constructor(private readonly http: HttpClient) {}

  getMonPersonnel(): Observable<Personnel> {
    return this.http.get<Personnel>(`${API_BASE}/me/personnel`);
  }

  photoUrl(): string {
    return `${API_BASE}/me/personnel/photo`;
  }

  uploaderMaPhoto(fichier: File): Observable<Personnel> {
    const formData = new FormData();
    formData.append('photoFichier', fichier);
    return this.http.post<Personnel>(`${API_BASE}/me/personnel/photo`, formData);
  }

  getMonTableauDeBord(): Observable<DashboardMe> {
    return this.http.get<DashboardMe>(`${API_BASE}/me/dashboard`);
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

  /** Aperçu intégré (inline, pour affichage dans un iframe). */
  cartePdfUrl(id: number): string {
    return `${API_BASE}/me/cartes-professionnelles/${id}/pdf`;
  }

  /** En blob, même raison que CarteProApiService.getPdfBlob() : contourne le SecurityError d'un contentWindow.print() cross-origin. */
  getCartePdfBlob(id: number): Observable<Blob> {
    return this.http.get(this.cartePdfUrl(id), { responseType: 'blob' });
  }

  /** Téléchargement forcé (Content-Disposition: attachment). */
  cartePdfTelechargerUrl(id: number): string {
    return `${API_BASE}/me/cartes-professionnelles/${id}/pdf/telecharger`;
  }

  getMesDemandesCartePro(): Observable<DemandeCartePro[]> {
    return this.http.get<DemandeCartePro[]>(`${API_BASE}/me/demandes-carte-pro`);
  }

  getMesMateriels(): Observable<MaterielInformatique[]> {
    return this.http.get<MaterielInformatique[]>(`${API_BASE}/me/materiels`);
  }

  /** En blob, même raison que getCartePdfBlob() ci-dessus. */
  getMaterielQrcodeBlob(id: number): Observable<Blob> {
    return this.http.get(`${API_BASE}/me/materiels/${id}/qrcode`, { responseType: 'blob' });
  }

  getMesVehicules(): Observable<Vehicule[]> {
    return this.http.get<Vehicule[]>(`${API_BASE}/me/vehicules`);
  }

  getMesDecisionsConge(): Observable<DecisionConge[]> {
    return this.http.get<DecisionConge[]>(`${API_BASE}/me/decisions-conge`);
  }

  getMesDemandesDecision(): Observable<DemandeDecision[]> {
    return this.http.get<DemandeDecision[]>(`${API_BASE}/me/demandes-decision`);
  }

  getMesDemandesJouissance(): Observable<DemandeJouissance[]> {
    return this.http.get<DemandeJouissance[]>(`${API_BASE}/me/demandes-jouissance`);
  }

  getMesDocuments(): Observable<DocumentAdministratif[]> {
    return this.http.get<DocumentAdministratif[]>(`${API_BASE}/me/documents-administratifs`);
  }

  documentFichierUrl(id: number): string {
    return `${API_BASE}/me/documents-administratifs/${id}/fichier`;
  }

  /** Types de document-administratif que l'agent peut déposer lui-même (pièces justificatives uniquement). */
  getTypesDocumentsSoumissibles(): Observable<ListeValeurRef[]> {
    return this.http.get<ListeValeurRef[]>(`${API_BASE}/me/documents-administratifs/types`);
  }

  uploaderDocument(typeCode: string, libelle: string, fichier: File): Observable<DocumentAdministratif> {
    const formData = new FormData();
    formData.append('type', typeCode);
    formData.append('libelle', libelle);
    formData.append('fichier', fichier);
    return this.http.post<DocumentAdministratif>(`${API_BASE}/me/documents-administratifs`, formData);
  }

  /** Auto-service : la fiche personnel est toujours celle du compte connecté, jamais transmise par le client. */
  creerDemandeCartePro(demande: DemandeCarteProSelfPayload): Observable<DemandeCartePro> {
    return this.http.post<DemandeCartePro>(`${API_BASE}/me/demandes-carte-pro`, demande);
  }

  uploadPieceDemandeCartePro(id: number, fichier: File): Observable<DemandeCartePro> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<DemandeCartePro>(`${API_BASE}/me/demandes-carte-pro/${id}/piece`, formData);
  }

  creerDemandeDecision(demande: DemandeDecision): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/me/demandes-decision`, demande);
  }

  uploadPieceDecision1(id: number, fichier: File): Observable<DemandeDecision> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<DemandeDecision>(`${API_BASE}/me/demandes-decision/${id}/piece1`, formData);
  }

  uploadPieceDecision2(id: number, fichier: File): Observable<DemandeDecision> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<DemandeDecision>(`${API_BASE}/me/demandes-decision/${id}/piece2`, formData);
  }

  /** Confirme le dépôt physique au service courrier, une fois la demande validée par le RH Congé. */
  confirmerDepotCourrierDecision(id: number): Observable<DemandeDecision> {
    return this.http.post<DemandeDecision>(`${API_BASE}/me/demandes-decision/${id}/confirmer-depot-courrier`, {});
  }

  creerDemandeJouissance(demande: DemandeJouissanceSelfPayload): Observable<DemandeJouissance> {
    return this.http.post<DemandeJouissance>(`${API_BASE}/me/demandes-jouissance`, demande);
  }

  uploadPieceJouissance1(id: number, fichier: File): Observable<DemandeJouissance> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<DemandeJouissance>(`${API_BASE}/me/demandes-jouissance/${id}/piece1`, formData);
  }

  uploadPieceJouissance2(id: number, fichier: File): Observable<DemandeJouissance> {
    const formData = new FormData();
    formData.append('fichier', fichier);
    return this.http.post<DemandeJouissance>(`${API_BASE}/me/demandes-jouissance/${id}/piece2`, formData);
  }

  getMesTickets(): Observable<TicketIncident[]> {
    return this.http.get<TicketIncident[]>(`${API_BASE}/me/tickets-incident`);
  }

  creerTicket(ticket: TicketIncidentSelfPayload): Observable<TicketIncident> {
    return this.http.post<TicketIncident>(`${API_BASE}/me/tickets-incident`, ticket);
  }
}
