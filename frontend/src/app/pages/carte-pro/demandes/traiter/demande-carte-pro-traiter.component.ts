import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormsModule, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../../core/auth.service';
import { DemandeCartePro } from '../../../../core/models/demande-carte-pro.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { DemandeCarteProApiService } from '../demande-carte-pro-api.service';

const LABELS_TYPE: Record<string, string> = {
  nouvelle: 'Nouvelle carte',
  renouvellement: 'Renouvellement',
  perte_vol: 'Perte ou vol',
};

@Component({
  selector: 'app-demande-carte-pro-traiter',
  standalone: true,
  imports: [ReactiveFormsModule, FormsModule, RouterLink, PageHeaderComponent, PanelComponent],
  templateUrl: './demande-carte-pro-traiter.component.html',
})
export class DemandeCarteProTraiterComponent implements OnInit {
  demande: DemandeCartePro | null = null;
  loading = true;
  saving = false;
  error: string | null = null;
  readonly labelsType = LABELS_TYPE;

  /**
   * Contrôle de premier niveau du RH Carte Pro avant transmission : il doit
   * cocher explicitement qu'il a vérifié la pièce et les informations avant
   * de pouvoir transmettre, plutôt que de transmettre en un clic sans
   * vérification. Remis à zéro à chaque demande chargée (ngOnInit).
   */
  pieceVerifiee = false;
  informationsVerifiees = false;

  form = this.fb.nonNullable.group({
    numero: [''],
    dateDelivrance: [''],
    commentaire: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: DemandeCarteProApiService,
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

  /** Le RH Admin approuve depuis "transmise" ; le RH Carte Pro transmet/rejette depuis "en_attente". */
  peutApprouver(): boolean {
    return !!this.demande?.transmise && this.auth.hasRole('ROLE_ADMIN_RH');
  }

  /** Transmettre/rejeter depuis "en_attente" : réservé au profil RH Carte Pro, pas au RH Admin. */
  peutTransmettreOuRejeter(): boolean {
    return !!this.demande?.enAttente && this.auth.hasRole('ROLE_RH_CARTE_PRO');
  }

  pieceUrl(): string | null {
    return this.demande?.id ? this.api.pieceUrl(this.demande.id) : null;
  }

  /** Tant qu'une pièce est jointe, sa vérification (ouverture + lecture) est obligatoire avant transmission. */
  verificationComplete(): boolean {
    return (!this.demande?.nomOriginal || this.pieceVerifiee) && this.informationsVerifiees;
  }

  transmettre(): void {
    if (!this.demande?.id || !this.verificationComplete()) {
      return;
    }
    this.saving = true;
    this.api.transmettre(this.demande.id).subscribe({
      next: () => this.router.navigateByUrl('/cartes-professionnelles/demandes'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors?.statut ?? 'Erreur lors de la transmission de la demande.';
      },
    });
  }

  approuver(): void {
    if (!this.demande?.id) {
      return;
    }
    const raw = this.form.getRawValue();
    if (!raw.numero.trim() || !raw.dateDelivrance) {
      this.error = 'Merci de renseigner un numéro et une date de délivrance valides pour approuver.';
      return;
    }

    this.saving = true;
    this.api.approuver(this.demande.id, raw.numero.trim(), raw.dateDelivrance, raw.commentaire || null).subscribe({
      // La demande approuvée porte désormais sa carte (créée ET validée en un
      // seul geste par le serveur, cachet/signature déjà sur le PDF) : on va
      // directement à son aperçu pour l'imprimer/télécharger, plutôt que de
      // renvoyer vers la liste des demandes et faire chercher la carte à part.
      next: (demande) => {
        const carte = demande.carteCreee;
        const carteId = carte && typeof carte !== 'string' ? carte.id : null;
        this.router.navigateByUrl(carteId ? `/cartes-professionnelles/${carteId}` : '/cartes-professionnelles/demandes');
      },
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'approbation de la demande.";
      },
    });
  }

  rejeter(): void {
    if (!this.demande?.id) {
      return;
    }
    const raw = this.form.getRawValue();

    this.saving = true;
    this.api.rejeter(this.demande.id, raw.commentaire || null).subscribe({
      next: () => this.router.navigateByUrl('/cartes-professionnelles/demandes'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors?.statut ?? 'Erreur lors du rejet de la demande.';
      },
    });
  }
}
