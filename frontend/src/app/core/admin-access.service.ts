import { HttpErrorResponse } from '@angular/common/http';
import { Injectable, signal } from '@angular/core';
import { Observable, catchError, map, throwError } from 'rxjs';
import { AuthService } from './auth.service';

/**
 * Verrou "Administration" : accéder à cette rubrique dans l'app (web comme
 * mobile) exige de reconfirmer son mot de passe, même déjà connecté — voir
 * adminAccessGuard, posé sur chaque route d'administration. Déverrouillé une
 * fois par session (pas à chaque navigation, sinon changer de rubrique
 * administrative redemanderait le mot de passe en boucle) ; reverrouillé à
 * la déconnexion.
 *
 * La reconfirmation réutilise /api/login (déjà PUBLIC_ACCESS) avec l'email du
 * compte déjà connecté : succès = mot de passe correct, sans exposer de
 * nouvel endpoint dédié à ce seul besoin.
 */
@Injectable({ providedIn: 'root' })
export class AdminAccessService {
  private readonly deverrouilleeSignal = signal(false);
  private readonly demandeEnCoursSignal = signal(false);
  private resolveur: ((autorise: boolean) => void) | null = null;

  readonly deverrouillee = this.deverrouilleeSignal.asReadonly();
  readonly demandeEnCours = this.demandeEnCoursSignal.asReadonly();

  constructor(private readonly auth: AuthService) {}

  /** Utilisé par adminAccessGuard : résout à true immédiatement si déjà déverrouillé, sinon attend la confirmation (ou l'annulation) via la modale. */
  demanderAcces(): Promise<boolean> {
    if (this.deverrouilleeSignal()) {
      return Promise.resolve(true);
    }
    this.demandeEnCoursSignal.set(true);
    return new Promise((resolve) => {
      this.resolveur = resolve;
    });
  }

  confirmer(motDePasse: string): Observable<void> {
    const email = this.auth.currentUser()?.email;
    if (!email) {
      return throwError(() => new Error('Aucune session active.'));
    }
    return this.auth.login(email, motDePasse).pipe(
      map(() => {
        this.deverrouilleeSignal.set(true);
        this.demandeEnCoursSignal.set(false);
        this.resolveur?.(true);
        this.resolveur = null;
      }),
      catchError((err: HttpErrorResponse) => {
        // La demande reste ouverte (l'utilisateur peut réessayer) : seule
        // annuler() ou une confirmation réussie referme la modale.
        return throwError(() => err);
      }),
    );
  }

  annuler(): void {
    this.demandeEnCoursSignal.set(false);
    this.resolveur?.(false);
    this.resolveur = null;
  }

  /** Reverrouille l'accès — appelé à la déconnexion pour qu'une nouvelle session redemande le mot de passe. */
  verrouiller(): void {
    this.deverrouilleeSignal.set(false);
  }
}
