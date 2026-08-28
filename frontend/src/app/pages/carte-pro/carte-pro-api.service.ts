import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE } from '../../core/api-base';
import { CarteProfessionnelle } from '../../core/models/carte-professionnelle.model';

/**
 * Contrairement à Personnel, cette ressource n'a pas de suffixe `.json` :
 * les opérations GetCollection/Get d'API Platform utilisent un `uriTemplate`
 * explicite (sans `{._format}`) pour partager exactement les mêmes URLs
 * kebab-case que les actions d'écriture personnalisées ci-dessous.
 */
@Injectable({ providedIn: 'root' })
export class CarteProApiService {
  constructor(private readonly http: HttpClient) {}

  getAll(): Observable<CarteProfessionnelle[]> {
    return this.http.get<CarteProfessionnelle[]>(`${API_BASE}/cartes-professionnelles`);
  }

  getEnAttenteValidation(): Observable<CarteProfessionnelle[]> {
    return this.http.get<CarteProfessionnelle[]>(
      `${API_BASE}/cartes-professionnelles?valideeParAdminRh=false`,
    );
  }

  getOne(id: number): Observable<CarteProfessionnelle> {
    return this.http.get<CarteProfessionnelle>(`${API_BASE}/cartes-professionnelles/${id}`);
  }

  create(carte: CarteProfessionnelle): Observable<CarteProfessionnelle> {
    return this.http.post<CarteProfessionnelle>(`${API_BASE}/cartes-professionnelles`, carte);
  }

  update(id: number, carte: CarteProfessionnelle): Observable<CarteProfessionnelle> {
    return this.http.put<CarteProfessionnelle>(`${API_BASE}/cartes-professionnelles/${id}`, carte);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${API_BASE}/cartes-professionnelles/${id}`);
  }

  generer(id: number): Observable<CarteProfessionnelle> {
    return this.http.post<CarteProfessionnelle>(
      `${API_BASE}/cartes-professionnelles/${id}/generer`,
      {},
    );
  }

  valider(id: number): Observable<CarteProfessionnelle> {
    return this.http.post<CarteProfessionnelle>(
      `${API_BASE}/cartes-professionnelles/${id}/valider`,
      {},
    );
  }

  pdfUrl(id: number): string {
    return `${API_BASE}/cartes-professionnelles/${id}/pdf`;
  }

  /**
   * En blob plutôt qu'une simple URL d'iframe : l'API tourne sur une autre
   * origine que le frontend (surtout notable en dev, ports distincts), et un
   * <iframe> cross-origin empêche contentWindow.print() (SecurityError) —
   * voir CarteProPreviewComponent.imprimer(). Un blob: est toujours
   * same-origin avec la page qui l'a créé, quelle que soit l'origine
   * d'où il vient.
   */
  getPdfBlob(id: number): Observable<Blob> {
    return this.http.get(this.pdfUrl(id), { responseType: 'blob' });
  }

  telechargerUrl(id: number): string {
    return `${API_BASE}/cartes-professionnelles/${id}/pdf/telecharger`;
  }
}
