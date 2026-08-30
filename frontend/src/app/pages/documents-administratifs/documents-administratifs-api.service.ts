import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { DocumentAdministratif } from '../../core/models/document-administratif.model';

export interface DocumentAdministratifPayload {
  personnel: number;
  type: number;
  libelle: string;
  dateDocument?: string | null;
  dateExpiration?: string | null;
  observations?: string | null;
  fichier: File;
}

@Injectable({ providedIn: 'root' })
export class DocumentsAdministratifsApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<DocumentAdministratif[]> {
    return this.http.get<DocumentAdministratif[]>(`${API_BASE}/documents-administratifs`);
  }

  getByPersonnel(personnelId: number): Observable<DocumentAdministratif[]> {
    return this.http.get<DocumentAdministratif[]>(`${API_BASE}/documents-administratifs`, {
      params: { personnel: personnelId },
    });
  }

  /** Un document a toujours un fichier dès sa création : un seul envoi multipart (métadonnées + fichier), pas de JSON. */
  create(payload: DocumentAdministratifPayload): Observable<DocumentAdministratif> {
    const formData = new FormData();
    formData.append('personnel', String(payload.personnel));
    formData.append('type', String(payload.type));
    formData.append('libelle', payload.libelle);
    if (payload.dateDocument) {
      formData.append('dateDocument', payload.dateDocument);
    }
    if (payload.dateExpiration) {
      formData.append('dateExpiration', payload.dateExpiration);
    }
    if (payload.observations) {
      formData.append('observations', payload.observations);
    }
    formData.append('fichier', payload.fichier);

    return this.http.post<DocumentAdministratif>(`${API_BASE}/documents-administratifs`, formData);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/documents-administratifs/${id}`);
  }

  fichierUrl(id: number): string {
    return `${API_BASE}/documents-administratifs/${id}/fichier`;
  }

  exportCsvUrl(): string {
    return `${API_BASE}/documents-administratifs/export.csv`;
  }
}
