import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../../core/auth.service';
import { DemandeDecision } from '../../../../core/models/conge.model';
import { ListeValeurRef, Personnel } from '../../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { EtapeTimeline, StatusTimelineComponent } from '../../../../shared/status-timeline/status-timeline.component';
import { PersonnelApiService } from '../../../personnel/personnel-api.service';
import { DemandeDecisionApiService } from '../../demande-decision-api.service';

/**
 * Circuit à quatre étapes, même logique que DemandeCarteProTraiterComponent :
 * chaque rôle (RH Congé puis RH Admin puis à nouveau RH Congé) ne voit les
 * actions qui lui reviennent que si le statut courant le permet.
 */
@Component({
  selector: 'app-demande-decision-traiter',
  standalone: true,
  imports: [ReactiveFormsModule, FormsModule, RouterLink, SlicePipe, PageHeaderComponent, PanelComponent, StatusTimelineComponent],
  templateUrl: './demande-decision-traiter.component.html',
})
export class DemandeDecisionTraiterComponent implements OnInit {
  demande: DemandeDecision | null = null;
  motifsRejet: ListeValeurRef[] = [];
  loading = true;
  saving = false;
  error: string | null = null;

  /** Contrôle de premier niveau du RH Congé, comme pour les cartes pro : coché explicitement avant de pouvoir transmettre. */
  piecesVerifiees = false;

  formTransmission = this.fb.nonNullable.group({
    numero: ['', Validators.required],
    dateDecision: ['', Validators.required],
    dateExpiration: ['', Validators.required],
    nombreJours: [null as number | null, Validators.required],
  });

  formRejet = this.fb.nonNullable.group({
    motifRejet: [null as number | null, Validators.required],
    commentaire: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: DemandeDecisionApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
    readonly auth: AuthService,
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.api.getOne(id).subscribe({
      next: (demande) => {
        this.demande = demande;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger cette demande.';
        this.loading = false;
      },
    });

    this.personnelApi.getTypesContrat().subscribe((valeurs) => {
      this.motifsRejet = valeurs.filter((v) => v.categorie === 'motif-rejet-decision-conge');
    });
  }

  get etapesTimeline(): EtapeTimeline[] {
    const d = this.demande;
    if (!d) {
      return [];
    }
    const creee: EtapeTimeline = { label: 'Créée', sousTitre: this.formatDate(d.createdAt), etat: 'termine' };

    if (d.statut === 'refusee') {
      return [creee, { label: 'Refusée', sousTitre: this.formatDate(d.dateTraitement), etat: 'rejete' }];
    }

    const transmiseFaite = d.statut === 'transmise' || d.statut === 'approuvee' || d.statut === 'transmise_agent';
    const approuveeFaite = d.statut === 'approuvee' || d.statut === 'transmise_agent';

    return [
      creee,
      {
        label: 'Transmise au RH Admin',
        sousTitre: transmiseFaite ? 'Fait' : null,
        etat: transmiseFaite ? 'termine' : 'actuel',
      },
      {
        label: 'Approuvée',
        sousTitre: approuveeFaite ? 'Fait' : null,
        etat: approuveeFaite ? 'termine' : transmiseFaite ? 'actuel' : 'a-venir',
      },
      {
        label: "Transmise à l'agent",
        sousTitre: d.statut === 'transmise_agent' ? this.formatDate(d.dateTraitement) : null,
        etat: d.statut === 'transmise_agent' ? 'termine' : approuveeFaite ? 'actuel' : 'a-venir',
      },
    ];
  }

  private formatDate(iso?: string | null): string | null {
    return iso ? `${iso.slice(0, 10)} · ${iso.slice(11, 16)}` : null;
  }

  agentLabel(): string {
    if (!this.demande) {
      return '';
    }
    if (typeof this.demande.personnel === 'string') {
      return this.demande.personnel;
    }
    const personnel: Personnel = this.demande.personnel;
    return personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`;
  }

  piecesAttendues(): number {
    return this.demande?.nouvellementAffecte ? 1 : 2;
  }

  pieceUrl(pieceId: number): string {
    return this.api.pieceUrl(pieceId);
  }

  /** Transmettre/rejeter depuis "en_attente" : réservé au RH Congé. */
  peutTransmettreOuRejeter(): boolean {
    return !!this.demande?.enAttente && this.auth.hasRole('ROLE_RH_CONGE');
  }

  /** Approuver depuis "transmise" : réservé au RH Admin. */
  peutApprouver(): boolean {
    return !!this.demande?.transmise && this.auth.hasRole('ROLE_ADMIN_RH');
  }

  /** Rejeter depuis "transmise" (filet de sécurité) : aussi réservé au RH Admin. */
  peutRejeterApresTransmission(): boolean {
    return !!this.demande?.transmise && this.auth.hasRole('ROLE_ADMIN_RH');
  }

  /** Confirmer la remise physique depuis "approuvee" : réservé au RH Congé. */
  peutTransmettreAgent(): boolean {
    return this.demande?.statut === 'approuvee' && this.auth.hasRole('ROLE_RH_CONGE');
  }

  transmettre(): void {
    if (!this.demande?.id || this.formTransmission.invalid || !this.piecesVerifiees) {
      this.formTransmission.markAllAsTouched();
      return;
    }
    const raw = this.formTransmission.getRawValue();

    this.saving = true;
    this.error = null;
    this.api.transmettre(this.demande.id, raw.numero.trim(), raw.dateDecision, raw.dateExpiration, raw.nombreJours!).subscribe({
      next: () => this.router.navigateByUrl('/conges/demandes-decision'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors de la transmission de la demande.';
      },
    });
  }

  rejeter(): void {
    if (!this.demande?.id || this.formRejet.invalid) {
      this.formRejet.markAllAsTouched();
      return;
    }
    const raw = this.formRejet.getRawValue();

    this.saving = true;
    this.error = null;
    this.api.rejeter(this.demande.id, raw.motifRejet!, raw.commentaire || null).subscribe({
      next: () => this.router.navigateByUrl('/conges/demandes-decision'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors du rejet de la demande.';
      },
    });
  }

  approuver(): void {
    if (!this.demande?.id) {
      return;
    }
    this.saving = true;
    this.error = null;
    this.api.approuver(this.demande.id).subscribe({
      next: () => this.router.navigateByUrl('/conges/demandes-decision'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'approbation de la demande.";
      },
    });
  }

  transmettreAgent(): void {
    if (!this.demande?.id) {
      return;
    }
    this.saving = true;
    this.error = null;
    this.api.transmettreAgent(this.demande.id).subscribe({
      next: () => this.router.navigateByUrl('/conges/demandes-decision'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors de la confirmation de remise.';
      },
    });
  }
}
