import { HttpClient } from '@angular/common/http';
import { Injectable, computed, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';
import { API_BASE } from './api-base';
import { CurrentUser } from './models/user.model';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly currentUserSignal = signal<CurrentUser | null>(null);
  private readonly initializedSignal = signal(false);

  readonly currentUser = this.currentUserSignal.asReadonly();
  readonly initialized = this.initializedSignal.asReadonly();
  readonly isLoggedIn = computed(() => this.currentUserSignal() !== null);

  constructor(private readonly http: HttpClient) {}

  login(email: string, password: string): Observable<CurrentUser> {
    return this.http
      .post<CurrentUser>(`${API_BASE}/login`, { email, password })
      .pipe(tap((user) => this.currentUserSignal.set(user)));
  }

  logout(): Observable<void> {
    return this.http
      .post<void>(`${API_BASE}/logout`, {})
      .pipe(tap(() => this.currentUserSignal.set(null)));
  }

  /** Appelé une fois au démarrage de l'app pour savoir si une session existe déjà. */
  fetchMe(): Observable<CurrentUser> {
    return this.http.get<CurrentUser>(`${API_BASE}/me`).pipe(
      tap({
        next: (user) => {
          this.currentUserSignal.set(user);
          this.initializedSignal.set(true);
        },
        error: () => {
          this.currentUserSignal.set(null);
          this.initializedSignal.set(true);
        },
      }),
    );
  }

  hasRole(role: string): boolean {
    return this.currentUserSignal()?.roles.includes(role) ?? false;
  }

  hasAnyRole(roles: string[]): boolean {
    return roles.some((role) => this.hasRole(role));
  }
}
