import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { BonEssence } from '../../../core/models/bon-essence.model';
import { HistoriqueVidange } from '../../../core/models/historique-vidange.model';
import { ListeValeurRef, Personnel, ServiceRef } from '../../../core/models/personnel.model';
import { StatutEcheance, Vehicule } from '../../../core/models/vehicule.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { SearchableSelectComponent, SearchableSelectOption } from '../../../shared/searchable-select/searchable-select.component';
import { PersonnelApiService } from '../../personnel/personnel-api.service';
import { VehiculeApiService } from '../vehicule-api.service';

const CARBURANTS: { value: string; label: string }[] = [
  { value: 'essence', label: 'Essence' },
  { value: 'diesel', label: 'Diesel' },
  { value: 'electrique', label: 'Électrique' },
  { value: 'hybride', label: 'Hybride' },
];

@Component({
  selector: 'app-vehicule-detail',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, SlicePipe, PageHeaderComponent, PanelComponent, SearchableSelectComponent],
  templateUrl: './vehicule-detail.component.html',
})
export class VehiculeDetailComponent implements OnInit {
  vehiculeId: number | null = null;
  services: ServiceRef[] = [];
  typesVehicule: ListeValeurRef[] = [];
  etatsVehicule: ListeValeurRef[] = [];
  personnels: Personnel[] = [];
  readonly carburants = CARBURANTS;
  loading = true;
  saving = false;
  error: string | null = null;

  statutAssurance: StatutEcheance | null = null;
  statutVisiteTechnique: StatutEcheance | null = null;
  statutVidange: StatutEcheance | null = null;

  vidanges: HistoriqueVidange[] = [];
  bonsEssence: BonEssence[] = [];

  showAjoutVidange = false;
  vidangeSaving = false;
  vidangeError: string | null = null;
  vidangeForm = this.fb.nonNullable.group({
    date: ['', Validators.required],
    kilometrage: [null as number | null, Validators.required],
    cout: [null as number | null],
    prestataire: [''],
    observations: [''],
  });

  showAjoutBonEssence = false;
  bonEssenceSaving = false;
  bonEssenceError: string | null = null;
  bonEssenceForm = this.fb.nonNullable.group({
    numero: [''],
    date: ['', Validators.required],
    quantiteLitres: [null as number | null],
    montant: [null as number | null],
    kilometrageReleve: [null as number | null],
    chauffeur: [null as number | null],
  });

  form = this.fb.nonNullable.group({
    immatriculation: ['', Validators.required],
    type: [null as number | null, Validators.required],
    marque: ['', Validators.required],
    modele: ['', Validators.required],
    numeroChassis: [''],
    carburant: [null as string | null],
    dateAcquisition: [''],
    valeurAcquisition: [null as number | null],
    kilometrage: [null as number | null],
    assuranceJusquau: [''],
    visiteTechniqueJusquau: [''],
    periodiciteVidangeKm: [null as number | null],
    etat: [null as number | null, Validators.required],
    service: [null as number | null, Validators.required],
    chauffeurAffecte: [null as number | null],
    observations: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: VehiculeApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    this.vehiculeId = idParam ? Number(idParam) : null;

    this.personnelApi.getServices().subscribe((services) => (this.services = services));
    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));
    this.personnelApi.getTypesContrat().subscribe((valeurs) => {
      this.typesVehicule = valeurs.filter((v) => v.categorie === 'type-vehicule');
      this.etatsVehicule = valeurs.filter((v) => v.categorie === 'etat-vehicule');
    });

    if (this.vehiculeId) {
      this.chargerVehicule(this.vehiculeId);
      this.chargerJournaux(this.vehiculeId);
    } else {
      this.loading = false;
    }
  }

  private chargerVehicule(id: number): void {
    this.api.getOne(id).subscribe({
      next: (vehicule) => {
        this.form.patchValue({
          immatriculation: vehicule.immatriculation,
          type: typeof vehicule.type === 'string' ? null : vehicule.type.id,
          marque: vehicule.marque,
          modele: vehicule.modele,
          numeroChassis: vehicule.numeroChassis ?? '',
          carburant: vehicule.carburant ?? null,
          dateAcquisition: vehicule.dateAcquisition?.substring(0, 10) ?? '',
          valeurAcquisition: vehicule.valeurAcquisition ? Number(vehicule.valeurAcquisition) : null,
          kilometrage: vehicule.kilometrage ?? null,
          assuranceJusquau: vehicule.assuranceJusquau?.substring(0, 10) ?? '',
          visiteTechniqueJusquau: vehicule.visiteTechniqueJusquau?.substring(0, 10) ?? '',
          periodiciteVidangeKm: vehicule.periodiciteVidangeKm ?? null,
          etat: typeof vehicule.etat === 'string' ? null : vehicule.etat.id,
          service: typeof vehicule.service === 'string' ? null : vehicule.service.id,
          chauffeurAffecte:
            vehicule.chauffeurAffecte && typeof vehicule.chauffeurAffecte !== 'string' ? (vehicule.chauffeurAffecte.id ?? null) : null,
          observations: vehicule.observations ?? '',
        });
        this.statutAssurance = vehicule.statutAssurance ?? null;
        this.statutVisiteTechnique = vehicule.statutVisiteTechnique ?? null;
        this.statutVidange = vehicule.statutVidange ?? null;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger ce véhicule.';
        this.loading = false;
      },
    });
  }

  private chargerJournaux(id: number): void {
    this.api.getHistoriqueVidanges(id).subscribe((vidanges) => (this.vidanges = vidanges));
    this.api.getBonsEssence(id).subscribe((bons) => (this.bonsEssence = bons));
  }

  get personnelOptions(): SearchableSelectOption[] {
    return this.personnels.map((p) => ({ value: p.id, label: p.nomComplet ?? p.matricule ?? '' }));
  }

  chauffeurLabel(entree: BonEssence): string {
    const chauffeur = entree.chauffeur as Personnel | string | null | undefined;
    if (!chauffeur) {
      return '—';
    }
    return typeof chauffeur === 'string' ? chauffeur : (chauffeur.nomComplet ?? `${chauffeur.prenom} ${chauffeur.nom}`);
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();
    const payload: Vehicule = {
      immatriculation: raw.immatriculation,
      type: `/api/liste_valeurs/${raw.type}`,
      marque: raw.marque,
      modele: raw.modele,
      numeroChassis: raw.numeroChassis || null,
      carburant: raw.carburant || null,
      dateAcquisition: raw.dateAcquisition || null,
      valeurAcquisition: raw.valeurAcquisition !== null ? String(raw.valeurAcquisition) : null,
      kilometrage: raw.kilometrage,
      assuranceJusquau: raw.assuranceJusquau || null,
      visiteTechniqueJusquau: raw.visiteTechniqueJusquau || null,
      periodiciteVidangeKm: raw.periodiciteVidangeKm,
      etat: `/api/liste_valeurs/${raw.etat}`,
      service: `/api/services/${raw.service}`,
      chauffeurAffecte: raw.chauffeurAffecte ? `/api/personnels/${raw.chauffeurAffecte}` : null,
      observations: raw.observations || null,
    };

    this.saving = true;
    const request = this.vehiculeId ? this.api.update(this.vehiculeId, payload) : this.api.create(payload);

    request.subscribe({
      next: () => this.router.navigateByUrl('/vehicules'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }

  supprimer(): void {
    if (!this.vehiculeId) {
      return;
    }
    if (!confirm('Supprimer ce véhicule du parc automobile ? Cette action est irréversible.')) {
      return;
    }

    this.api.delete(this.vehiculeId).subscribe({
      next: () => this.router.navigateByUrl('/vehicules'),
      error: (err) => {
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : 'Erreur lors de la suppression.';
      },
    });
  }

  ouvrirAjoutVidange(): void {
    this.vidangeError = null;
    this.vidangeForm.reset({ date: '', kilometrage: this.form.controls.kilometrage.value, cout: null, prestataire: '', observations: '' });
    this.showAjoutVidange = true;
  }

  fermerAjoutVidange(): void {
    this.showAjoutVidange = false;
  }

  soumettreVidange(): void {
    if (this.vidangeForm.invalid || !this.vehiculeId) {
      this.vidangeForm.markAllAsTouched();
      return;
    }

    const raw = this.vidangeForm.getRawValue();
    this.vidangeSaving = true;
    this.vidangeError = null;
    this.api
      .creerVidange({
        vehicule: `/api/vehicules/${this.vehiculeId}`,
        date: raw.date,
        kilometrage: raw.kilometrage!,
        cout: raw.cout !== null ? String(raw.cout) : null,
        prestataire: raw.prestataire || null,
        observations: raw.observations || null,
      })
      .subscribe({
        next: () => {
          this.vidangeSaving = false;
          this.showAjoutVidange = false;
          this.chargerVehicule(this.vehiculeId!);
          this.chargerJournaux(this.vehiculeId!);
        },
        error: (err) => {
          this.vidangeSaving = false;
          this.vidangeError = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
        },
      });
  }

  supprimerVidange(vidange: HistoriqueVidange): void {
    if (!vidange.id || !this.vehiculeId) {
      return;
    }
    if (!confirm('Supprimer cette vidange du journal ?')) {
      return;
    }
    this.api.supprimerVidange(vidange.id).subscribe({
      next: () => {
        this.chargerVehicule(this.vehiculeId!);
        this.chargerJournaux(this.vehiculeId!);
      },
    });
  }

  ouvrirAjoutBonEssence(): void {
    this.bonEssenceError = null;
    this.bonEssenceForm.reset({
      numero: '',
      date: '',
      quantiteLitres: null,
      montant: null,
      kilometrageReleve: this.form.controls.kilometrage.value,
      chauffeur: this.form.controls.chauffeurAffecte.value,
    });
    this.showAjoutBonEssence = true;
  }

  fermerAjoutBonEssence(): void {
    this.showAjoutBonEssence = false;
  }

  soumettreBonEssence(): void {
    if (this.bonEssenceForm.invalid || !this.vehiculeId) {
      this.bonEssenceForm.markAllAsTouched();
      return;
    }

    const raw = this.bonEssenceForm.getRawValue();
    this.bonEssenceSaving = true;
    this.bonEssenceError = null;
    this.api
      .creerBonEssence({
        vehicule: `/api/vehicules/${this.vehiculeId}`,
        numero: raw.numero || null,
        date: raw.date,
        quantiteLitres: raw.quantiteLitres !== null ? String(raw.quantiteLitres) : null,
        montant: raw.montant !== null ? String(raw.montant) : null,
        kilometrageReleve: raw.kilometrageReleve,
        chauffeur: raw.chauffeur ? `/api/personnels/${raw.chauffeur}` : null,
      })
      .subscribe({
        next: () => {
          this.bonEssenceSaving = false;
          this.showAjoutBonEssence = false;
          this.chargerJournaux(this.vehiculeId!);
        },
        error: (err) => {
          this.bonEssenceSaving = false;
          this.bonEssenceError = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
        },
      });
  }

  supprimerBonEssence(bon: BonEssence): void {
    if (!bon.id || !this.vehiculeId) {
      return;
    }
    if (!confirm('Supprimer ce bon d\'essence du journal ?')) {
      return;
    }
    this.api.supprimerBonEssence(bon.id).subscribe({
      next: () => this.chargerJournaux(this.vehiculeId!),
    });
  }
}
