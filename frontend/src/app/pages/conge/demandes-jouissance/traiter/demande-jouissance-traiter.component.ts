import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { DemandeJouissance } from '../../../../core/models/conge.model';
import { Personnel } from '../../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../../shared/panel/panel.component';
import { EtapeTimeline, StatusTimelineComponent } from '../../../../shared/status-timeline/status-timeline.component';
import { DemandeJouissanceApiService } from '../../demande-jouissance-api.service';

const LABELS_TYPE: Record<string, string> = {
  annuel: 'Congé annuel',
  maladie: 'Congé maladie',
  maternite_paternite: 'Congé maternité / paternité',
  sans_solde: 'Congé sans solde',
  autre: 'Autre',
};

@Component({
  selector: 'app-demande-jouissance-traiter',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent, StatusTimelineComponent],
  templateUrl: './demande-jouissance-traiter.component.html',
})
export class DemandeJouissanceTraiterComponent implements OnInit {
  demande: DemandeJouissance | null = null;
  loading = true;
  saving = false;
  error: string | null = null;
  readonly labelsType = LABELS_TYPE;

  form = this.fb.nonNullable.group({
    commentaire: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: DemandeJouissanceApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
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

  get etapesTimeline(): EtapeTimeline[] {
    const d = this.demande;
    if (!d) {
      return [];
    }
    const creee: EtapeTimeline = { label: 'Créée', sousTitre: this.formatDate(d.createdAt), etat: 'termine' };

    if (d.statut === 'refusee') {
      return [creee, { label: 'Refusée', sousTitre: this.formatDate(d.dateTraitement), etat: 'rejete' }];
    }

    return [
      creee,
      {
        label: 'Approuvée',
        sousTitre: d.statut === 'approuvee' ? this.formatDate(d.dateTraitement) : null,
        etat: d.statut === 'approuvee' ? 'termine' : 'actuel',
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

  approuver(): void {
    if (!this.demande?.id) {
      return;
    }
    this.saving = true;
    this.api
      .traiter(this.demande.id, { decision: 'approuver', commentaire: this.form.getRawValue().commentaire || null })
      .subscribe({
        next: () => this.router.navigateByUrl('/conges/demandes-jouissance'),
        error: () => {
          this.saving = false;
          this.error = "Erreur lors de l'approbation.";
        },
      });
  }

  refuser(): void {
    if (!this.demande?.id) {
      return;
    }
    this.saving = true;
    this.api
      .traiter(this.demande.id, { decision: 'refuser', commentaire: this.form.getRawValue().commentaire || null })
      .subscribe({
        next: () => this.router.navigateByUrl('/conges/demandes-jouissance'),
        error: () => {
          this.saving = false;
          this.error = 'Erreur lors du refus de la demande.';
        },
      });
  }
}
