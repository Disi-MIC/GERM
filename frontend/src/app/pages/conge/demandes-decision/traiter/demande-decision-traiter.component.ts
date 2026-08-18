import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../../core/auth.service';
import { DecisionConge, DemandeDecision } from '../../../../core/models/conge.model';
import { ListeValeurRef, Personnel } from '../../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { EtapeTimeline, StatusTimelineComponent } from '../../../../shared/status-timeline/status-timeline.component';
import { DecisionCongeApercuComponent } from '../../../../shared/decision-conge-apercu/decision-conge-apercu.component';
import { dateFr } from '../../../../shared/date-fr';
import { etapesTimelineDemandeDecision } from '../../../../shared/demande-decision-etapes';
import { PersonnelApiService } from '../../../personnel/personnel-api.service';
import { DemandeDecisionApiService } from '../../demande-decision-api.service';

/**
 * Circuit à cinq étapes — voir le commentaire de classe de DemandeDecision
 * (backend) pour le détail complet. Chaque rôle (RH Congé, puis l'agent
 * lui-même hors de cette page, puis RH Admin, puis à nouveau RH Congé) ne
 * voit les actions qui lui reviennent que si le statut courant le permet.
 */
@Component({
  selector: 'app-demande-decision-traiter',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, SlicePipe, PageHeaderComponent, PanelComponent, StatusTimelineComponent, DecisionCongeApercuComponent],
  templateUrl: './demande-decision-traiter.component.html',
})
export class DemandeDecisionTraiterComponent implements OnInit {
  demande: DemandeDecision | null = null;
  motifsRejet: ListeValeurRef[] = [];
  loading = true;
  saving = false;
  decisionEnApercu: DecisionConge | null = null;
  error: string | null = null;

  fichierRetour: File | null = null;

  formRejet = this.fb.nonNullable.group({
    motifRejet: [null as number | null, Validators.required],
    commentaire: [''],
  });

  formGeneration = this.fb.nonNullable.group({
    numero: ['', Validators.required],
    dateDerniereDecision: [''],
    nombreJours: [null as number | null, [Validators.required, Validators.min(1), Validators.max(90)]],
    numeroAttestationNonJouissance: [''],
    dateAttestationNonJouissance: [''],
  });

  /** Date d'octroi = toujours la date de génération (aujourd'hui) ; expiration = octroi + 3 ans. Non saisies, affichées à titre informatif — voir genererEtTransmettre() côté serveur, seule source de vérité. */
  readonly dateOctroiPrevue = new Date();
  readonly dateExpirationPrevue = new Date(
    this.dateOctroiPrevue.getFullYear() + 3,
    this.dateOctroiPrevue.getMonth(),
    this.dateOctroiPrevue.getDate(),
  );
  readonly dateFr = dateFr;

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
        this.preremplirGeneration();
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
    return this.demande ? etapesTimelineDemandeDecision(this.demande) : [];
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

  /** Labels des deux pièces attendues (toujours 2, voir DemandeDecision côté serveur), dépendants de nouvellementAffecte. */
  get labelsPiecesAttendues(): string {
    return this.demande?.nouvellementAffecte ? "Prise de service + acte d'engagement" : 'Prise de service + ancienne décision';
  }

  pieceUrl(pieceId: number): string {
    return this.api.pieceUrl(pieceId);
  }

  documentRetourUrl(): string {
    return this.demande?.id ? this.api.documentRetourUrl(this.demande.id) : '';
  }

  voirApercuDecision(): void {
    const decision = this.demande?.decisionCreee;
    if (decision && typeof decision !== 'string') {
      this.decisionEnApercu = decision;
    }
  }

  /** Valider/rejeter depuis "en_attente" : réservé au RH Congé. */
  peutValiderOuRejeter(): boolean {
    return !!this.demande?.enAttente && this.auth.hasRole('ROLE_RH_CONGE');
  }

  /** Déposer le retour du circuit depuis "deposee_courrier" : réservé au RH Admin. */
  peutDeposerRetour(): boolean {
    return !!this.demande?.deposeeCourrier && this.auth.hasRole('ROLE_ADMIN_RH');
  }

  /** Générer et transmettre depuis "retournee" : réservé au RH Congé. */
  peutGenererEtTransmettre(): boolean {
    return !!this.demande?.retournee && this.auth.hasRole('ROLE_RH_CONGE');
  }

  valider(): void {
    if (!this.demande?.id) {
      return;
    }
    this.saving = true;
    this.error = null;
    this.api.valider(this.demande.id).subscribe({
      next: () => this.router.navigateByUrl('/conges/demandes-decision'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors de la validation de la demande.';
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

  deposerRetour(): void {
    if (!this.demande?.id || !this.fichierRetour) {
      this.error = 'Merci de joindre le document scanné.';
      return;
    }
    this.saving = true;
    this.error = null;
    this.api.retour(this.demande.id, this.fichierRetour).subscribe({
      next: () => this.router.navigateByUrl('/conges/demandes-decision'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors du dépôt du document de retour.';
      },
    });
  }

  genererEtTransmettre(): void {
    if (!this.demande?.id || this.formGeneration.invalid) {
      this.formGeneration.markAllAsTouched();
      return;
    }
    const raw = this.formGeneration.getRawValue();

    this.saving = true;
    this.error = null;
    this.api
      .genererEtTransmettre(this.demande.id, {
        numero: raw.numero.trim(),
        nombreJours: raw.nombreJours!,
        dateDerniereDecision: raw.dateDerniereDecision || null,
        numeroAttestationNonJouissance: raw.numeroAttestationNonJouissance.trim() || null,
        dateAttestationNonJouissance: raw.dateAttestationNonJouissance || null,
      })
      .subscribe({
        next: () => this.router.navigateByUrl('/conges/demandes-decision'),
        error: (err) => {
          this.saving = false;
          this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors de la génération de la décision.';
        },
      });
  }

  private preremplirGeneration(): void {
    const d = this.demande;
    if (!d || typeof d.personnel === 'string') {
      return;
    }
    const personnel = d.personnel;
    const dateDebutSuggeree = d.dateDerniereDecision ?? d.datePriseDeService ?? personnel.dateEmbauche ?? null;
    if (dateDebutSuggeree) {
      this.formGeneration.patchValue({ dateDerniereDecision: dateDebutSuggeree.substring(0, 10) });
    }

    const nombreJours = this.calculerSuggestionNombreJours();
    if (nombreJours !== null) {
      this.formGeneration.patchValue({ nombreJours });
    }
  }

  /**
   * Suggestion calculée côté client (mois écoulés entre la dernière décision
   * — ou la date de prise de service si l'agent n'en a jamais eu, ou la date
   * d'embauche de sa fiche Personnel en dernier recours si aucune des deux
   * n'est renseignée — et le dépôt de cette demande, × 30 jours / 11 mois
   * pour un fonctionnaire ou / 12 mois sinon) : le RH Congé peut toujours
   * l'ajuster avant de générer, la valeur soumise fait foi côté serveur (voir
   * genererEtTransmettre()), qui rejette toute valeur supérieure à 90.
   * Au-delà de 3 ans d'écart entre les deux demandes, le congé annuel est
   * plafonné à 90 jours directement (le cumul théorique n'est jamais dû) ;
   * en-deçà, la formule est également bornée à 90 par sécurité.
   */
  private calculerSuggestionNombreJours(): number | null {
    const d = this.demande;
    if (!d || typeof d.personnel === 'string') {
      return null;
    }
    const personnel = d.personnel;
    const typeContrat = typeof personnel.typeContrat === 'string' ? null : personnel.typeContrat;
    const diviseur = typeContrat?.code === 'fonctionnaire' ? 11 : 12;

    const dateDebutBrute = d.dateDerniereDecision ?? d.datePriseDeService ?? personnel.dateEmbauche;
    const dateFinBrute = d.createdAt;
    if (!dateDebutBrute || !dateFinBrute) {
      return null;
    }

    const debut = new Date(dateDebutBrute);
    const fin = new Date(dateFinBrute);
    let mois = (fin.getFullYear() - debut.getFullYear()) * 12 + (fin.getMonth() - debut.getMonth());
    const joursDansMoisFin = new Date(fin.getFullYear(), fin.getMonth() + 1, 0).getDate();
    mois += (fin.getDate() - debut.getDate()) / joursDansMoisFin;

    if (mois > 36) {
      return 90;
    }

    return Math.min(Math.round(((mois * 30) / diviseur) * 10) / 10, 90);
  }
}
