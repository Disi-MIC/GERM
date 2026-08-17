import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { AdminAccessService } from '../../core/admin-access.service';

/**
 * Modale globale de reconfirmation du mot de passe avant d'entrer dans
 * l'Administration — voir AdminAccessService/adminAccessGuard. Rendue une
 * seule fois au niveau racine (AppComponent) plutôt que dans chaque coquille
 * (Shell/MobileShell) : la navigation qui la déclenche peut survenir depuis
 * n'importe quelle route, pas seulement depuis un clic sur le bouton dédié.
 */
@Component({
  selector: 'app-admin-access-modal',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './admin-access-modal.component.html',
  styleUrl: './admin-access-modal.component.scss',
})
export class AdminAccessModalComponent {
  motDePasse = '';
  soumission = false;
  erreur: string | null = null;

  constructor(readonly adminAccess: AdminAccessService) {}

  confirmer(): void {
    if (!this.motDePasse || this.soumission) {
      return;
    }
    this.soumission = true;
    this.erreur = null;
    this.adminAccess.confirmer(this.motDePasse).subscribe({
      next: () => this.reinitialiser(),
      error: () => {
        this.erreur = 'Mot de passe incorrect.';
        this.soumission = false;
      },
    });
  }

  annuler(): void {
    this.adminAccess.annuler();
    this.reinitialiser();
  }

  private reinitialiser(): void {
    this.motDePasse = '';
    this.soumission = false;
    this.erreur = null;
  }
}
