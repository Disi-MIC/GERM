import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { Personnel } from '../../core/models/personnel.model';
import { TicketEscalade, TicketIncident } from '../../core/models/ticket-incident.model';

@Injectable({ providedIn: 'root' })
export class TicketsInformatiqueApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<TicketIncident[]> {
    return this.http.get<TicketIncident[]>(`${API_BASE}/tickets-incident`);
  }

  getOne(id: number): Observable<TicketIncident> {
    return this.http.get<TicketIncident>(`${API_BASE}/tickets-incident/${id}`);
  }

  prendreEnCharge(id: number): Observable<TicketIncident> {
    return this.http.post<TicketIncident>(`${API_BASE}/tickets-incident/${id}/prendre-en-charge`, {});
  }

  resoudre(id: number, commentaire: string): Observable<TicketIncident> {
    return this.http.post<TicketIncident>(`${API_BASE}/tickets-incident/${id}/resoudre`, { commentaire });
  }

  refuser(id: number, commentaire: string): Observable<TicketIncident> {
    return this.http.post<TicketIncident>(`${API_BASE}/tickets-incident/${id}/refuser`, { commentaire });
  }

  valider(id: number, commentaire?: string | null): Observable<TicketIncident> {
    return this.http.post<TicketIncident>(`${API_BASE}/tickets-incident/${id}/valider`, { commentaire });
  }

  rouvrir(id: number, commentaire: string): Observable<TicketIncident> {
    return this.http.post<TicketIncident>(`${API_BASE}/tickets-incident/${id}/rouvrir`, { commentaire });
  }

  escalader(id: number, commentaire: string): Observable<TicketIncident> {
    return this.http.post<TicketIncident>(`${API_BASE}/tickets-incident/${id}/escalader`, { commentaire });
  }

  getEscalades(ticketId: number): Observable<TicketEscalade[]> {
    return this.http.get<TicketEscalade[]>(`${API_BASE}/tickets-escalade`, { params: { ticket: ticketId } });
  }

  assigner(id: number, personnelId: number): Observable<TicketIncident> {
    return this.http.post<TicketIncident>(`${API_BASE}/tickets-incident/${id}/assigner`, { personnelId });
  }

  getTechniciens(): Observable<Personnel[]> {
    return this.http.get<Personnel[]>(`${API_BASE}/tickets-incident/techniciens`);
  }
}
