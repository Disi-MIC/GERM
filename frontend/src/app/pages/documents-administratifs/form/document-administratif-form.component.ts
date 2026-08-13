import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ListeValeurRef, Personnel } from '../../../core/models/personnel.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { PersonnelApiService } from '../../personnel/personnel-api.service';
import { DocumentsAdministratifsApiService } from '../documents-administratifs-api.service';

@Component({
  selector: 'app-document-administratif-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent],
  templateUrl: './document-administratif-form.component.html',
})
export class DocumentAdministratifFormComponent implements OnInit {
  personnels: Personnel[] = [];
  typesDocument: ListeValeurRef[] = [];
  fichier: File | null = null;
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    personnel: [null as number | null, Validators.required],
    type: [null as number | null, Validators.required],
    libelle: ['', Validators.required],
    dateDocument: [''],
    dateExpiration: [''],
    observations: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: DocumentsAdministratifsApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));
    this.personnelApi.getTypesContrat().subscribe((valeurs) => {
      this.typesDocument = valeurs.filter((v) => v.categorie === 'type-document');
    });
  }

  onFichierChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.fichier = input.files?.[0] ?? null;
  }

  submit(): void {
    if (this.form.invalid || !this.fichier) {
      this.form.markAllAsTouched();
      if (!this.fichier) {
        this.error = 'Merci de sélectionner un fichier.';
      }
      return;
    }

    const raw = this.form.getRawValue();

    this.saving = true;
    this.api
      .create({
        personnel: raw.personnel!,
        type: raw.type!,
        libelle: raw.libelle,
        dateDocument: raw.dateDocument || null,
        dateExpiration: raw.dateExpiration || null,
        observations: raw.observations || null,
        fichier: this.fichier,
      })
      .subscribe({
        next: () => this.router.navigateByUrl('/documents-administratifs'),
        error: (err) => {
          this.saving = false;
          this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
        },
      });
  }
}
