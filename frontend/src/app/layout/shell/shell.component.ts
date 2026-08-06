import { Component } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../../core/auth.service';

@Component({
  selector: 'app-shell',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './shell.component.html',
  styleUrl: './shell.component.scss',
})
export class ShellComponent {
  constructor(
    readonly auth: AuthService,
    private readonly router: Router,
  ) {}

  logout(): void {
    this.auth.logout().subscribe(() => this.router.navigateByUrl('/login'));
  }
}
