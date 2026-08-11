import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { MaterielInformatique } from '../../../../core/models/materiel-informatique.model';
import { PrioriteTicket } from '../../../../core/models/ticket-incident.model';
import { ProfilApiService } from '../../profil-api.service';

@Component({
  selector: 'app-nouveau-ticket',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './nouveau-ticket.component.html',
})
export class NouveauTicketComponent implements OnInit {
  materiels: MaterielInformatique[] = [];
  loading = true;
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    materiel: [null as number | null, Validators.required],
    titre: ['', Validators.required],
    description: ['', Validators.required],
    priorite: ['normale' as PrioriteTicket, Validators.required],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: ProfilApiService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.api.getMesMateriels().subscribe({
      next: (materiels) => {
        this.materiels = materiels;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger votre parc informatique.';
        this.loading = false;
      },
    });
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();
    this.saving = true;
    this.api
      .creerTicket({
        materielId: raw.materiel!,
        titre: raw.titre,
        description: raw.description,
        priorite: raw.priorite,
      })
      .subscribe({
        next: () => this.router.navigateByUrl('/mon-espace/tickets'),
        error: (err) => {
          this.saving = false;
          this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'envoi du ticket.";
        },
      });
  }
}
