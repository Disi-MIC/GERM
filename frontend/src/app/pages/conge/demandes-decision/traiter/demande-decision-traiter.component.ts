import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../../core/auth.service';
import { DecisionConge, DemandeDecision, EligibiliteDecisionConge } from '../../../../core/models/conge.model';
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

  /** Éligibilité + nombre de jours accordables — calculés par Symfony (EligibiliteDecisionCongeService), jamais par ce composant. */
  eligibilite: EligibiliteDecisionConge | null = null;
  loadingEligibilite = false;
  erreurEligibilite: string | null = null;

  fichierRetour: File | null = null;

  formRejet = this.fb.nonNullable.group({
    motifRejet: [null as number | null, Validators.required],
    commentaire: [''],
  });

  formGeneration = this.fb.nonNullable.group({
    dateDerniereDecision: [''],
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

  /** Aperçu du numéro qui sera généré côté serveur — "MIC/DAGE/RH/" + initiales de l'opérateur connecté, voir genererEtTransmettre(). */
  get numeroPrevu(): string {
    const u = this.auth.currentUser();
    if (!u) {
      return 'MIC/DAGE/RH/…';
    }
    const initiales = `${u.prenom.charAt(0)}${u.nom.charAt(0)}`.toLowerCase();
    return `MIC/DAGE/RH/${initiales}`;
  }

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
        if (demande.retournee) {
          this.chargerEligibilite(id);
        }
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
    return !!this.demande?.deposeeCourrier && this.auth.hasRole('ROLE_RH_RESPONSABLE');
  }

  /** Rôle + statut, indépendamment de l'éligibilité — distingue "vous n'avez pas le droit" de "l'agent n'est pas éligible" dans le template. */
  aLeRoleGenerer(): boolean {
    return !!this.demande?.retournee && this.auth.hasRole('ROLE_RH_CONGE');
  }

  /** Générer et transmettre depuis "retournee" : réservé au RH Congé, et seulement si Symfony a confirmé l'agent éligible (EligibiliteDecisionCongeService — jamais une supposition côté client). */
  peutGenererEtTransmettre(): boolean {
    return this.aLeRoleGenerer() && !!this.eligibilite?.eligible;
  }

  private chargerEligibilite(demandeId: number): void {
    this.loadingEligibilite = true;
    this.erreurEligibilite = null;
    this.api.eligibilite(demandeId).subscribe({
      next: (eligibilite) => {
        this.eligibilite = eligibilite;
        this.loadingEligibilite = false;
      },
      error: (err) => {
        this.erreurEligibilite = err?.error?.errors
          ? Object.values(err.error.errors).join(' ')
          : "Impossible de calculer l'éligibilité de l'agent.";
        this.loadingEligibilite = false;
      },
    });
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
    if (!this.demande?.id || this.formGeneration.invalid || !this.eligibilite?.eligible) {
      this.formGeneration.markAllAsTouched();
      return;
    }
    const raw = this.formGeneration.getRawValue();

    // L'attestation de non jouissance, comme la décision de congé
    // antérieure, ne concerne que les agents qui en avaient déjà une —
    // voir genererEtTransmettre() côté serveur, même règle.
    if (!this.demande.nouvellementAffecte && (!raw.numeroAttestationNonJouissance.trim() || !raw.dateAttestationNonJouissance)) {
      this.error = "Merci d'indiquer le numéro et la date de l'attestation de non jouissance de congé.";
      return;
    }

    this.saving = true;
    this.error = null;
    this.api
      .genererEtTransmettre(this.demande.id, {
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
  }
}
