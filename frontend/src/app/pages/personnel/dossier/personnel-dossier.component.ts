import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { CarriereApiService } from '../../carriere/carriere-api.service';
import { CarteProApiService } from '../../carte-pro/carte-pro-api.service';
import { CarteProfessionnelle } from '../../../core/models/carte-professionnelle.model';
import { Conge, DecisionConge } from '../../../core/models/conge.model';
import { DocumentAdministratif } from '../../../core/models/document-administratif.model';
import { HistoriqueAffectation } from '../../../core/models/historique-affectation.model';
import { HistoriqueChangementPersonnel } from '../../../core/models/historique-changement-personnel.model';
import { Personnel } from '../../../core/models/personnel.model';
import { CongeApiService } from '../../conge/conge-api.service';
import { DecisionCongeApiService } from '../../conge/decision-conge-api.service';
import { DocumentsAdministratifsApiService } from '../../documents-administratifs/documents-administratifs-api.service';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { PersonnelApiService } from '../personnel-api.service';

const LABELS_TYPE_MOUVEMENT: Record<string, string> = {
  nomination: 'Nomination',
  mutation: 'Mutation',
  promotion: 'Promotion',
  autre: 'Autre',
};

const LABELS_TYPE_CONGE: Record<string, string> = {
  annuel: 'Congé annuel',
  maladie: 'Congé maladie',
  maternite_paternite: 'Congé maternité / paternité',
  sans_solde: 'Congé sans solde',
  autre: 'Autre',
};

const LABELS_STATUT: Record<string, string> = {
  actif: 'Actif',
  en_conge: 'En congé',
  suspendu: 'Suspendu',
  retraite: 'Retraité',
  demissionnaire: 'Démissionnaire',
};

/**
 * Dossier agent 360° : consolide en une seule page ce qui, jusqu'ici,
 * n'était consultable qu'en filtrant manuellement chaque rubrique RH
 * (carrière, cartes professionnelles, documents, congés) par agent — même
 * esprit qu'ApercuOrganisationController côté organigramme, appliqué ici à
 * une seule fiche. Purement en lecture : chaque section renvoie vers sa
 * page de gestion habituelle pour toute action (créer/modifier/supprimer).
 */
@Component({
  selector: 'app-personnel-dossier',
  standalone: true,
  imports: [RouterLink, SlicePipe, PageHeaderComponent, PanelComponent],
  templateUrl: './personnel-dossier.component.html',
})
export class PersonnelDossierComponent implements OnInit {
  personnelId!: number;
  personnel: Personnel | null = null;
  loading = true;
  error: string | null = null;

  carriere: HistoriqueAffectation[] = [];
  cartesProfessionnelles: CarteProfessionnelle[] = [];
  documents: DocumentAdministratif[] = [];
  conges: Conge[] = [];
  decisionsConge: DecisionConge[] = [];
  historiqueChangements: HistoriqueChangementPersonnel[] = [];

  readonly labelsTypeMouvement = LABELS_TYPE_MOUVEMENT;
  readonly labelsTypeConge = LABELS_TYPE_CONGE;
  readonly labelsStatut = LABELS_STATUT;

  constructor(
    private readonly route: ActivatedRoute,
    private readonly personnelApi: PersonnelApiService,
    private readonly carriereApi: CarriereApiService,
    private readonly carteProApi: CarteProApiService,
    private readonly documentsApi: DocumentsAdministratifsApiService,
    private readonly congeApi: CongeApiService,
    private readonly decisionCongeApi: DecisionCongeApiService,
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    this.personnelId = idParam ? Number(idParam) : 0;
    if (!this.personnelId) {
      this.error = 'Agent introuvable.';
      this.loading = false;
      return;
    }

    this.personnelApi.getOne(this.personnelId).subscribe({
      next: (personnel) => {
        this.personnel = personnel;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger la fiche agent.';
        this.loading = false;
      },
    });

    this.carriereApi.getByPersonnel(this.personnelId).subscribe((c) => (this.carriere = c));
    this.carteProApi.getByPersonnel(this.personnelId).subscribe((c) => (this.cartesProfessionnelles = c));
    this.documentsApi.getByPersonnel(this.personnelId).subscribe((d) => (this.documents = d));
    this.congeApi.getByPersonnel(this.personnelId).subscribe((c) => (this.conges = c));
    this.decisionCongeApi.getByPersonnel(this.personnelId).subscribe((d) => (this.decisionsConge = d));
    this.personnelApi.getHistoriqueChangements(this.personnelId).subscribe((h) => (this.historiqueChangements = h));
  }

  serviceLabel(entree: HistoriqueAffectation): string {
    return typeof entree.service === 'string' ? entree.service : entree.service.nom;
  }

  serviceLabelPersonnel(): string {
    if (!this.personnel) {
      return '';
    }
    return typeof this.personnel.service === 'string' ? this.personnel.service : this.personnel.service.nom;
  }

  typeDocumentLabel(document: DocumentAdministratif): string {
    return typeof document.type === 'string' ? document.type : document.type.libelle;
  }

  documentUrl(document: DocumentAdministratif): string {
    return document.id ? this.documentsApi.fichierUrl(document.id) : '';
  }
}
