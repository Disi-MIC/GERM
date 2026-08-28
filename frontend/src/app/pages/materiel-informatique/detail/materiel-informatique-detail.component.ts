import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { HistoriqueAffectationMateriel } from '../../../core/models/historique-affectation-materiel.model';
import { LicenceLogiciel } from '../../../core/models/licence-logiciel.model';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef, Personnel, ServiceRef } from '../../../core/models/personnel.model';
import { LicencesLogiciellesApiService } from '../../licences-logicielles/licences-logicielles-api.service';
import { PersonnelApiService } from '../../personnel/personnel-api.service';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../shared/searchable-select/searchable-select.component';
import { MaterielInformatiqueApiService } from '../materiel-informatique-api.service';

@Component({
  selector: 'app-materiel-informatique-detail',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, SlicePipe, PageHeaderComponent, PanelComponent, SearchableSelectComponent],
  templateUrl: './materiel-informatique-detail.component.html',
})
export class MaterielInformatiqueDetailComponent implements OnInit {
  materielId: number | null = null;
  services: ServiceRef[] = [];
  typesMateriel: ListeValeurRef[] = [];
  etatsMateriel: ListeValeurRef[] = [];
  niveauxVulnerabilite: ListeValeurRef[] = [];
  /** Licences disponibles par catégorie de logiciel — voir LicenceLogiciel.logiciel.categorie. */
  licencesOs: LicenceLogiciel[] = [];
  licencesBureautique: LicenceLogiciel[] = [];
  licencesAntivirus: LicenceLogiciel[] = [];
  personnels: Personnel[] = [];
  historique: HistoriqueAffectationMateriel[] = [];
  loading = true;
  saving = false;
  uploadingPhoto = false;
  hasPhoto = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    numeroInventaire: ['', Validators.required],
    type: [null as number | null, Validators.required],
    marque: ['', Validators.required],
    modele: ['', Validators.required],
    numeroSerie: [''],
    numeroTelephone: ['', [Validators.pattern(/^\d{4}$/)]],
    specifications: [''],
    dateAcquisition: [''],
    fournisseur: [''],
    dateMiseEnService: [''],
    periodiciteMois: [null as number | null],
    systemeExploitation: [null as number | null],
    suiteBureautique: [null as number | null],
    antivirus: [null as number | null],
    etat: [null as number | null, Validators.required],
    // Optionnel : ajouté après coup, tout le parc existant n'a pas encore
    // été évalué (voir MaterielInformatique::$niveauVulnerabilite côté serveur).
    niveauVulnerabilite: [null as number | null],
    // Pas de Validators.required : dérivé automatiquement de l'agent affecté
    // (voir MaterielInformatique::getService() côté serveur), et pas
    // nécessaire du tout pour un matériel en stock ou réformé.
    service: [null as number | null],
    affecteA: [null as number | null],
    observations: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: MaterielInformatiqueApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly licencesApi: LicencesLogiciellesApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    this.materielId = idParam ? Number(idParam) : null;

    this.personnelApi.getServices().subscribe((services) => (this.services = services));
    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));
    this.personnelApi.getTypesContrat().subscribe((valeurs) => {
      this.typesMateriel = valeurs.filter((v) => v.categorie === 'type-materiel');
      this.etatsMateriel = valeurs.filter((v) => v.categorie === 'etat-materiel');
      this.niveauxVulnerabilite = valeurs.filter((v) => v.categorie === 'niveau-vulnerabilite');
    });
    // Une licence n'est proposée que si elle existe déjà dans le registre
    // (voir LicenceLogicielController) : impossible de rattacher un logiciel
    // installé à une licence qui n'a pas encore été enregistrée.
    this.licencesApi.getAll().subscribe((licences) => {
      this.licencesOs = licences.filter((l) => this.logicielCategorie(l) === 'logiciel-os');
      this.licencesBureautique = licences.filter((l) => this.logicielCategorie(l) === 'logiciel-bureautique');
      this.licencesAntivirus = licences.filter((l) => this.logicielCategorie(l) === 'logiciel-antivirus');
    });

    if (this.materielId) {
      this.api.getOne(this.materielId).subscribe({
        next: (materiel) => {
          this.form.patchValue({
            numeroInventaire: materiel.numeroInventaire,
            type: typeof materiel.type === 'string' ? null : materiel.type.id,
            marque: materiel.marque,
            modele: materiel.modele,
            numeroSerie: materiel.numeroSerie ?? '',
            numeroTelephone: materiel.numeroTelephone ?? '',
            specifications: materiel.specifications ?? '',
            dateAcquisition: materiel.dateAcquisition?.substring(0, 10) ?? '',
            fournisseur: materiel.fournisseur ?? '',
            dateMiseEnService: materiel.dateMiseEnService?.substring(0, 10) ?? '',
            periodiciteMois: materiel.periodiciteMois ?? null,
            systemeExploitation:
              materiel.systemeExploitation && typeof materiel.systemeExploitation !== 'string'
                ? materiel.systemeExploitation.id
                : null,
            suiteBureautique:
              materiel.suiteBureautique && typeof materiel.suiteBureautique !== 'string'
                ? materiel.suiteBureautique.id
                : null,
            antivirus: materiel.antivirus && typeof materiel.antivirus !== 'string' ? materiel.antivirus.id : null,
            etat: typeof materiel.etat === 'string' ? null : materiel.etat.id,
            niveauVulnerabilite:
              materiel.niveauVulnerabilite && typeof materiel.niveauVulnerabilite !== 'string'
                ? materiel.niveauVulnerabilite.id
                : null,
            service: materiel.service && typeof materiel.service !== 'string' ? materiel.service.id : null,
            affecteA:
              materiel.affecteA && typeof materiel.affecteA !== 'string' ? (materiel.affecteA.id ?? null) : null,
            observations: materiel.observations ?? '',
          });
          this.hasPhoto = materiel.hasPhoto ?? false;
          this.loading = false;
        },
        error: () => {
          this.error = 'Impossible de charger ce matériel.';
          this.loading = false;
        },
      });
      this.api.getHistoriqueAffectations(this.materielId).subscribe((historique) => (this.historique = historique));
    } else {
      this.loading = false;
    }
  }

  /** Le numéro de poste (voir formulaire) n'a de sens que pour un matériel de type "Téléphone". */
  get estTelephone(): boolean {
    const typeId = this.form.controls.type.value;
    return typeId !== null && this.typesMateriel.some((t) => t.id === typeId && t.code === 'telephone');
  }

  get personnelOptions(): SearchableSelectOption[] {
    return this.personnels.map((p) => ({ value: p.id, label: p.nomComplet ?? p.matricule ?? '' }));
  }

  private logicielCategorie(licence: LicenceLogiciel): string | null {
    const logiciel = licence.logiciel as ListeValeurRef | string;
    return typeof logiciel === 'string' ? null : logiciel.categorie;
  }

  licenceLabel(licence: LicenceLogiciel): string {
    const logiciel = licence.logiciel as ListeValeurRef | string;
    const nomLogiciel = typeof logiciel === 'string' ? logiciel : logiciel.libelle;
    return `${nomLogiciel} — ${licence.numeroLicence || 'sans n°'}`;
  }

  photoUrl(): string {
    return this.materielId ? this.api.photoUrl(this.materielId) : '';
  }

  onPhotoChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const fichier = input.files?.[0];
    if (!fichier || !this.materielId) {
      return;
    }

    this.uploadingPhoto = true;
    this.api.uploadPhoto(this.materielId, fichier).subscribe({
      next: () => {
        this.uploadingPhoto = false;
        this.hasPhoto = true;
      },
      error: (err) => {
        this.uploadingPhoto = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'envoi de la photo.";
      },
    });
  }

  affecteLabel(entree: HistoriqueAffectationMateriel): string {
    const personnel = entree.personnel as Personnel | string | null | undefined;
    if (!personnel) {
      return 'Non affecté (stock)';
    }
    return typeof personnel === 'string' ? personnel : (personnel.nomComplet ?? `${personnel.prenom} ${personnel.nom}`);
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();
    const payload: MaterielInformatique = {
      numeroInventaire: raw.numeroInventaire,
      type: `/api/liste_valeurs/${raw.type}`,
      marque: raw.marque,
      modele: raw.modele,
      numeroSerie: raw.numeroSerie || null,
      numeroTelephone: raw.numeroTelephone || null,
      specifications: raw.specifications || null,
      dateAcquisition: raw.dateAcquisition || null,
      fournisseur: raw.fournisseur || null,
      dateMiseEnService: raw.dateMiseEnService || null,
      periodiciteMois: raw.periodiciteMois,
      systemeExploitation: raw.systemeExploitation ? `/api/licences-logicielles/${raw.systemeExploitation}` : null,
      suiteBureautique: raw.suiteBureautique ? `/api/licences-logicielles/${raw.suiteBureautique}` : null,
      antivirus: raw.antivirus ? `/api/licences-logicielles/${raw.antivirus}` : null,
      etat: `/api/liste_valeurs/${raw.etat}`,
      niveauVulnerabilite: raw.niveauVulnerabilite ? `/api/liste_valeurs/${raw.niveauVulnerabilite}` : null,
      service: raw.service ? `/api/services/${raw.service}` : null,
      affecteA: raw.affecteA ? `/api/personnels/${raw.affecteA}` : null,
      observations: raw.observations || null,
    };

    this.saving = true;
    const request = this.materielId ? this.api.update(this.materielId, payload) : this.api.create(payload);

    request.subscribe({
      next: () => this.router.navigateByUrl('/materiel-informatique'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }

  supprimer(): void {
    if (!this.materielId) {
      return;
    }
    if (!confirm('Supprimer ce matériel du parc informatique ? Cette action est irréversible.')) {
      return;
    }

    this.api.delete(this.materielId).subscribe({
      next: () => this.router.navigateByUrl('/materiel-informatique'),
      error: (err) => {
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors de la suppression.';
      },
    });
  }
}
