import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/auth.service';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef, Personnel } from '../../../core/models/personnel.model';
import { NiveauTicket, TicketEscalade, TicketIncident } from '../../../core/models/ticket-incident.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { EtapeTimeline, StatusTimelineComponent } from '../../../shared/status-timeline/status-timeline.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../shared/searchable-select/searchable-select.component';
import { TicketsInformatiqueApiService } from '../tickets-informatique-api.service';

const LABELS_NIVEAU: Record<NiveauTicket, string> = {
  n1: 'Niveau 1 (support)',
  n2: 'Niveau 2 (technique)',
  n3: 'Niveau 3 (expertise)',
};

@Component({
  selector: 'app-ticket-informatique-traiter',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, SlicePipe, PageHeaderComponent, PanelComponent, StatusTimelineComponent, SearchableSelectComponent],
  templateUrl: './ticket-informatique-traiter.component.html',
})
export class TicketInformatiqueTraiterComponent implements OnInit {
  ticket: TicketIncident | null = null;
  escalades: TicketEscalade[] = [];
  techniciens: Personnel[] = [];
  loading = true;
  saving = false;
  error: string | null = null;
  readonly labelsNiveau = LABELS_NIVEAU;

  form = this.fb.nonNullable.group({
    commentaire: [''],
  });

  formAssignation = this.fb.nonNullable.group({
    personnelId: [null as number | null, Validators.required],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: TicketsInformatiqueApiService,
    private readonly route: ActivatedRoute,
    readonly auth: AuthService,
  ) {}

  ngOnInit(): void {
    this.charger();
    if (this.auth.hasRole('ROLE_IT_RESPONSABLE')) {
      this.api.getTechniciens().subscribe((techniciens) => (this.techniciens = techniciens));
    }
  }

  get technicienOptions(): SearchableSelectOption[] {
    return this.techniciens.map((t) => ({ value: t.id, label: t.nomComplet ?? '' }));
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
    this.api.getEscalades(id).subscribe((escalades) => (this.escalades = escalades));
  }

  /**
   * Reflète toujours le statut courant (pas un historique figé) : un ticket
   * rouvert après résolution repasse par exemple "Résolu" en 'actuel' — même
   * logique que le reste de la page (rechargée à chaque action via charger()).
   * L'escalade de niveau (N1/N2/N3) reste hors de cette frise, déjà tracée
   * par le tableau "Historique d'escalade" plus bas.
   */
  get etapesTimeline(): EtapeTimeline[] {
    const t = this.ticket;
    if (!t) {
      return [];
    }
    const ouvert: EtapeTimeline = { label: 'Ouvert', sousTitre: this.formatDate(t.createdAt), etat: 'termine' };

    if (t.statut === 'refuse') {
      return [ouvert, { label: 'Refusé', sousTitre: this.formatDate(t.dateResolution ?? t.dateCloture), etat: 'rejete' }];
    }

    const priseEnCharge = !!t.datePriseEnCharge;
    const resolu = t.statut === 'resolu' || t.statut === 'cloture';
    const cloture = t.statut === 'cloture';

    return [
      ouvert,
      {
        label: 'En cours',
        sousTitre: this.formatDate(t.datePriseEnCharge),
        etat: priseEnCharge ? 'termine' : 'actuel',
      },
      {
        label: 'Résolu',
        sousTitre: this.formatDate(t.dateResolution),
        etat: resolu ? 'termine' : priseEnCharge ? 'actuel' : 'a-venir',
      },
      {
        label: 'Clôturé',
        sousTitre: this.formatDate(t.dateCloture),
        etat: cloture ? 'termine' : resolu ? 'actuel' : 'a-venir',
      },
    ];
  }

  private formatDate(iso?: string | null): string | null {
    return iso ? `${iso.slice(0, 10)} · ${iso.slice(11, 16)}` : null;
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
    // Réservé au responsable : point d'entrée unique qui reçoit tout
    // nouveau ticket, à lui de le garder pour lui ou de le répartir
    // (assigner()) — un technicien ne se sert plus lui-même dans la file.
    return !!this.ticket?.ouvert && this.auth.hasRole('ROLE_IT_RESPONSABLE');
  }

  peutAssigner(): boolean {
    return !!this.ticket?.ouvert && this.auth.hasRole('ROLE_IT_RESPONSABLE');
  }

  peutResoudre(): boolean {
    // ROLE_IT_RESPONSABLE inclut ROLE_IT_TICKETS via la hiérarchie de rôles
    // Symfony, mais celle-ci n'est jamais répercutée côté Angular
    // (AuthService.hasRole() ne lit que les rôles littéraux de /api/me) — le
    // responsable doit donc être listé explicitement à chaque contrôle
    // "réservé à ROLE_IT_TICKETS", comme déjà fait pour RH Admin (voir
    // ShellComponent) et ROLE_IT_STOCK/ROLE_IT_TICKETS ailleurs dans le menu.
    return !!this.ticket?.enCours && this.auth.hasAnyRole(['ROLE_IT_TICKETS', 'ROLE_IT_RESPONSABLE']);
  }

  peutRefuser(): boolean {
    if (!this.ticket) {
      return false;
    }
    if (this.ticket.resolu) {
      return this.auth.hasRole('ROLE_IT_RESPONSABLE');
    }
    return !!(this.ticket.ouvert || this.ticket.enCours) && this.auth.hasAnyRole(['ROLE_IT_TICKETS', 'ROLE_IT_RESPONSABLE']);
  }

  peutValiderOuRouvrir(): boolean {
    return !!this.ticket?.resolu && this.auth.hasRole('ROLE_IT_RESPONSABLE');
  }

  peutEscalader(): boolean {
    if (!this.ticket || !this.auth.hasAnyRole(['ROLE_IT_TICKETS', 'ROLE_IT_RESPONSABLE'])) {
      return false;
    }
    return !!(this.ticket.ouvert || this.ticket.enCours) && this.ticket.niveau !== 'n3';
  }

  niveauLabel(): string {
    return this.ticket?.niveau ? this.labelsNiveau[this.ticket.niveau] : '';
  }

  slaDepassee(): boolean {
    return !!this.ticket?.echeanceSla && new Date(this.ticket.echeanceSla) < new Date();
  }

  escaladeParLabel(escalade: TicketEscalade): string {
    const par = escalade.par as Personnel | string | null | undefined;
    if (!par) {
      return 'Système';
    }
    return typeof par === 'string' ? par : (par.nomComplet ?? `${par.prenom} ${par.nom}`);
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

  assigner(): void {
    if (!this.ticket?.id) {
      return;
    }
    const personnelId = this.formAssignation.getRawValue().personnelId;
    if (!personnelId) {
      this.error = 'Merci de sélectionner un technicien.';
      return;
    }
    this.saving = true;
    this.api.assigner(this.ticket.id, personnelId).subscribe({
      next: () => this.charger(),
      error: (err) => this.gererErreur(err),
    });
  }

  escalader(): void {
    if (!this.ticket?.id) {
      return;
    }
    const commentaire = this.form.getRawValue().commentaire.trim();
    if (!commentaire) {
      this.error = "Merci de préciser le motif de l'escalade.";
      return;
    }
    this.saving = true;
    this.api.escalader(this.ticket.id, commentaire).subscribe({
      next: () => this.charger(),
      error: (err) => this.gererErreur(err),
    });
  }

  private gererErreur(err: { error?: { errors?: Record<string, string> } }): void {
    this.saving = false;
    this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Une erreur est survenue.';
  }
}
