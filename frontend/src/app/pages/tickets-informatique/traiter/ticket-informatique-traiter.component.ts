import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/auth.service';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef, Personnel } from '../../../core/models/personnel.model';
import { TicketIncident } from '../../../core/models/ticket-incident.model';
import { TicketsInformatiqueApiService } from '../tickets-informatique-api.service';

@Component({
  selector: 'app-ticket-informatique-traiter',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './ticket-informatique-traiter.component.html',
})
export class TicketInformatiqueTraiterComponent implements OnInit {
  ticket: TicketIncident | null = null;
  loading = true;
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    commentaire: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: TicketsInformatiqueApiService,
    private readonly route: ActivatedRoute,
    readonly auth: AuthService,
  ) {}

  ngOnInit(): void {
    this.charger();
  }

  private charger(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    this.api.getOne(id).subscribe({
      next: (ticket) => {
        this.ticket = ticket;
        this.loading = false;
        this.saving = false;
        this.form.reset({ commentaire: '' });
      },
      error: () => {
        this.error = 'Impossible de charger ce ticket.';
        this.loading = false;
        this.saving = false;
      },
    });
  }

  agentLabel(): string {
    if (!this.ticket) {
      return '';
    }
    const personnel = this.ticket.personnel as Personnel | string;
    return typeof personnel === 'string' ? personnel : (personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`);
  }

  materielLabel(): string {
    if (!this.ticket) {
      return '';
    }
    const materiel = this.ticket.materiel as MaterielInformatique | string;
    return typeof materiel === 'string' ? materiel : `${materiel.marque} ${materiel.modele} (${materiel.numeroInventaire})`;
  }

  prioriteLabel(): string {
    if (!this.ticket) {
      return '';
    }
    const priorite = this.ticket.priorite as ListeValeurRef | string;
    return typeof priorite === 'string' ? priorite : priorite.libelle;
  }

  assigneLabel(): string {
    const assigne = this.ticket?.assigneA as Personnel | string | null | undefined;
    if (!assigne) {
      return 'Non assigné';
    }
    return typeof assigne === 'string' ? assigne : (assigne.nomComplet ?? `${assigne.prenom} ${assigne.nom}`);
  }

  peutPrendreEnCharge(): boolean {
    return !!this.ticket?.ouvert && this.auth.hasRole('ROLE_IT_TICKETS');
  }

  peutResoudre(): boolean {
    return !!this.ticket?.enCours && this.auth.hasRole('ROLE_IT_TICKETS');
  }

  peutRefuser(): boolean {
    if (!this.ticket) {
      return false;
    }
    if (this.ticket.resolu) {
      return this.auth.hasRole('ROLE_IT_RESPONSABLE');
    }
    return !!(this.ticket.ouvert || this.ticket.enCours) && this.auth.hasRole('ROLE_IT_TICKETS');
  }

  peutValiderOuRouvrir(): boolean {
    return !!this.ticket?.resolu && this.auth.hasRole('ROLE_IT_RESPONSABLE');
  }

  prendreEnCharge(): void {
    if (!this.ticket?.id) {
      return;
    }
    this.saving = true;
    this.api.prendreEnCharge(this.ticket.id).subscribe({
      next: () => this.charger(),
      error: (err) => this.gererErreur(err),
    });
  }

  resoudre(): void {
    if (!this.ticket?.id) {
      return;
    }
    const commentaire = this.form.getRawValue().commentaire.trim();
    if (!commentaire) {
      this.error = 'Merci de décrire la résolution apportée.';
      return;
    }
    this.saving = true;
    this.api.resoudre(this.ticket.id, commentaire).subscribe({
      next: () => this.charger(),
      error: (err) => this.gererErreur(err),
    });
  }

  refuser(): void {
    if (!this.ticket?.id) {
      return;
    }
    const commentaire = this.form.getRawValue().commentaire.trim();
    if (!commentaire) {
      this.error = 'Merci de préciser le motif du refus.';
      return;
    }
    this.saving = true;
    this.api.refuser(this.ticket.id, commentaire).subscribe({
      next: () => this.charger(),
      error: (err) => this.gererErreur(err),
    });
  }

  valider(): void {
    if (!this.ticket?.id) {
      return;
    }
    this.saving = true;
    this.api.valider(this.ticket.id, this.form.getRawValue().commentaire || null).subscribe({
      next: () => this.charger(),
      error: (err) => this.gererErreur(err),
    });
  }

  rouvrir(): void {
    if (!this.ticket?.id) {
      return;
    }
    const commentaire = this.form.getRawValue().commentaire.trim();
    if (!commentaire) {
      this.error = "Merci de préciser pourquoi la résolution proposée ne convient pas.";
      return;
    }
    this.saving = true;
    this.api.rouvrir(this.ticket.id, commentaire).subscribe({
      next: () => this.charger(),
      error: (err) => this.gererErreur(err),
    });
  }

  private gererErreur(err: { error?: { errors?: Record<string, string> } }): void {
    this.saving = false;
    this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Une erreur est survenue.';
  }
}
