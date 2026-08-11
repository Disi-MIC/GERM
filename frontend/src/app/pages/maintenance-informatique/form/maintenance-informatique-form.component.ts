import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { Maintenance, TypeMaintenance } from '../../../core/models/maintenance.model';
import { MaterielInformatique } from '../../../core/models/materiel-informatique.model';
import { Personnel } from '../../../core/models/personnel.model';
import { TicketIncident } from '../../../core/models/ticket-incident.model';
import { MaterielInformatiqueApiService } from '../../materiel-informatique/materiel-informatique-api.service';
import { PersonnelApiService } from '../../personnel/personnel-api.service';
import { TicketsInformatiqueApiService } from '../../tickets-informatique/tickets-informatique-api.service';
import { MaintenanceInformatiqueApiService } from '../maintenance-informatique-api.service';

@Component({
  selector: 'app-maintenance-informatique-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './maintenance-informatique-form.component.html',
})
export class MaintenanceInformatiqueFormComponent implements OnInit {
  materiels: MaterielInformatique[] = [];
  personnels: Personnel[] = [];
  tickets: TicketIncident[] = [];
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    materiel: [null as number | null, Validators.required],
    type: ['corrective' as TypeMaintenance, Validators.required],
    description: ['', Validators.required],
    dateRealisation: ['', Validators.required],
    realisePar: [null as number | null],
    prestataireExterne: [''],
    ticketOrigine: [null as number | null],
    cout: [''],
    observations: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: MaintenanceInformatiqueApiService,
    private readonly materielApi: MaterielInformatiqueApiService,
    private readonly personnelApi: PersonnelApiService,
    private readonly ticketsApi: TicketsInformatiqueApiService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.materielApi.getAll().subscribe((materiels) => (this.materiels = materiels));
    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));
    this.ticketsApi.getAll().subscribe((tickets) => (this.tickets = tickets));
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();
    const payload: Maintenance = {
      materiel: `/api/materiels-informatiques/${raw.materiel}`,
      type: raw.type,
      description: raw.description,
      dateRealisation: raw.dateRealisation,
      realisePar: raw.realisePar ? `/api/personnels/${raw.realisePar}` : null,
      prestataireExterne: raw.prestataireExterne || null,
      ticketOrigine: raw.ticketOrigine ? `/api/tickets-incident/${raw.ticketOrigine}` : null,
      cout: raw.cout ? String(raw.cout) : null,
      observations: raw.observations || null,
    };

    this.saving = true;
    this.api.create(payload).subscribe({
      next: () => this.router.navigateByUrl('/maintenance-informatique'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }
}
