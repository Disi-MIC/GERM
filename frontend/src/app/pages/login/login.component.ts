import { HttpErrorResponse } from '@angular/common/http';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../core/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss',
})
export class LoginComponent {
  email = '';
  password = '';
  error: string | null = null;
  submitting = false;

  constructor(
    private readonly auth: AuthService,
    private readonly router: Router,
  ) {}

  submit(): void {
    this.error = null;
    this.submitting = true;

    this.auth.login(this.email, this.password).subscribe({
      next: () => {
        this.submitting = false;
        this.router.navigateByUrl('/');
      },
      error: (err: HttpErrorResponse) => {
        this.submitting = false;
        // status 0 : la requête n'a même pas atteint de serveur (backend
        // injoignable, mauvaise IP/URL, etc.) — message générique "identifiants
        // incorrects" trompeur ici puisque le serveur n'a rien pu vérifier.
        this.error =
          err.status === 0
            ? "Impossible de contacter le serveur. Vérifiez votre connexion et l'adresse du serveur."
            : 'Email ou mot de passe incorrect.';
      },
    });
  }
}
