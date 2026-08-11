import { SlicePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { HistoriqueAffectationMateriel } from '../../../core/models/historique-affectation-materiel.model';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { ListeValeurRef, Personnel, ServiceRef } from '../../../core/models/personnel.model';
import { PersonnelApiService } from '../../personnel/personnel-api.service';
import { MaterielInformatiqueApiService } from '../materiel-informatique-api.service';

@Component({
  selector: 'app-materiel-informatique-detail',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, SlicePipe],
  templateUrl: './materiel-informatique-detail.component.html',
})
export class MaterielInformatiqueDetailComponent implements OnInit {
  materielId: number | null = null;
  services: ServiceRef[] = [];
  typesMateriel: ListeValeurRef[] = [];
  etatsMateriel: ListeValeurRef[] = [];
  personnels: Personnel[] = [];
  historique: HistoriqueAffectationMateriel[] = [];
  loading = true;
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    numeroInventaire: ['', Validators.required],
    type: [null as number | null, Validators.required],
    marque: ['', Validators.required],
    modele: ['', Validators.required],
    numeroSerie: [''],
    specifications: [''],
    dateAcquisition: [''],
    valeurAcquisition: [''],
    fournisseur: [''],
    garantieJusquau: [''],
    etat: [null as number | null, Validators.required],
    service: [null as number | null, Validators.required],
    affecteA: [null as number | null],
    observations: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: MaterielInformatiqueApiService,
    private readonly personnelApi: PersonnelApiService,
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
            specifications: materiel.specifications ?? '',
            dateAcquisition: materiel.dateAcquisition?.substring(0, 10) ?? '',
            valeurAcquisition: materiel.valeurAcquisition ?? '',
            fournisseur: materiel.fournisseur ?? '',
            garantieJusquau: materiel.garantieJusquau?.substring(0, 10) ?? '',
            etat: typeof materiel.etat === 'string' ? null : materiel.etat.id,
            service: typeof materiel.service === 'string' ? null : materiel.service.id,
            affecteA:
              materiel.affecteA && typeof materiel.affecteA !== 'string' ? (materiel.affecteA.id ?? null) : null,
            observations: materiel.observations ?? '',
          });
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
      specifications: raw.specifications || null,
      dateAcquisition: raw.dateAcquisition || null,
      // Doctrine "decimal" est porté côté PHP par une string (pas un float, pour
      // éviter toute perte de précision) : le control number d'Angular renvoie un
      // number natif, à reconvertir explicitement avant l'envoi JSON.
      valeurAcquisition: raw.valeurAcquisition ? String(raw.valeurAcquisition) : null,
      fournisseur: raw.fournisseur || null,
      garantieJusquau: raw.garantieJusquau || null,
      etat: `/api/liste_valeurs/${raw.etat}`,
      service: `/api/services/${raw.service}`,
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
      error: () => {
        this.error = 'Erreur lors de la suppression.';
      },
    });
  }
}
