import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/auth.service';
import { CarteProfessionnelle } from '../../../core/models/carte-professionnelle.model';
import { Personnel } from '../../../core/models/personnel.model';
import { CarteProApiService } from '../carte-pro-api.service';

@Component({
  selector: 'app-carte-pro-preview',
  standalone: true,
  imports: [RouterLink, SlicePipe],
  templateUrl: './carte-pro-preview.component.html',
})
export class CarteProPreviewComponent implements OnInit {
  carte: CarteProfessionnelle | null = null;
  loading = true;
  error: string | null = null;
  pdfUrl: SafeResourceUrl | null = null;
  telechargerUrl = '';

  constructor(
    private readonly api: CarteProApiService,
    private readonly route: ActivatedRoute,
    private readonly sanitizer: DomSanitizer,
    readonly auth: AuthService,
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.api.getOne(id).subscribe({
      next: (carte) => {
        this.carte = carte;
        this.pdfUrl = this.sanitizer.bypassSecurityTrustResourceUrl(this.api.pdfUrl(id));
        this.telechargerUrl = this.api.telechargerUrl(id);
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger cette carte.';
        this.loading = false;
      },
    });
  }

  agentLabel(): string {
    if (!this.carte) {
      return '';
    }
    if (typeof this.carte.personnel === 'string') {
      return this.carte.personnel;
    }
    const personnel: Personnel = this.carte.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  valider(): void {
    if (!this.carte?.id) {
      return;
    }
    this.api.valider(this.carte.id).subscribe({
      next: (carte) => (this.carte = carte),
      error: () => {
        this.error = 'Erreur lors de la validation.';
      },
    });
  }

  regenerer(): void {
    if (!this.carte?.id) {
      return;
    }
    this.api.generer(this.carte.id).subscribe({
      next: (carte) => {
        this.carte = carte;
        this.pdfUrl = this.sanitizer.bypassSecurityTrustResourceUrl(
          `${this.api.pdfUrl(carte.id!)}?t=${Date.now()}`,
        );
      },
      error: () => {
        this.error = 'Erreur lors de la régénération du PDF.';
      },
    });
  }
}
