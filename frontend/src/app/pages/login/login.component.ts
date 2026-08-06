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
      next: (user) => {
        this.submitting = false;
        if (user.roles.includes('ROLE_RH_PERSONNEL')) {
          this.router.navigateByUrl('/personnel');
        } else {
          this.router.navigateByUrl('/acces-refuse');
        }
      },
      error: () => {
        this.submitting = false;
        this.error = 'Email ou mot de passe incorrect.';
      },
    });
  }
}
