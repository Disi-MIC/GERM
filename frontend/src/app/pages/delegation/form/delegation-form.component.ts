import { Component, OnInit } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/auth.service';
import { Delegation, RoleDelegable } from '../../../core/models/delegation.model';
import { UserRef } from '../../../core/models/user.model';
import { PageHeaderComponent } from '../../../shared/page-header/page-header.component';
import { PanelComponent } from '../../../shared/panel/panel.component';
import { DelegationApiService } from '../delegation-api.service';

@Component({
  selector: 'app-delegation-form',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, PageHeaderComponent, PanelComponent],
  templateUrl: './delegation-form.component.html',
})
export class DelegationFormComponent implements OnInit {
  users: UserRef[] = [];
  saving = false;
  error: string | null = null;

  form = this.fb.nonNullable.group({
    delegataire: [null as number | null, Validators.required],
    roleDelegue: ['ROLE_RH_PERSONNEL' as RoleDelegable, Validators.required],
    dateDebut: ['', Validators.required],
    dateFin: ['', Validators.required],
    motif: [''],
  });

  constructor(
    private readonly fb: FormBuilder,
    private readonly api: DelegationApiService,
    private readonly auth: AuthService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.api.getUsers().subscribe((users) => {
      const moi = this.auth.currentUser()?.id;
      // Tri côté client : l'API ne trie plus par nom (voir User::getNom(),
      // qui délègue à la fiche agent liée — un ORDER BY côté serveur sur la
      // colonne brute ne serait plus fiable), nomComplet lui reste correct.
      this.users = users.filter((u) => u.id !== moi).sort((a, b) => a.nomComplet.localeCompare(b.nomComplet));
    });
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();
    const payload: Delegation = {
      delegataire: `/api/users/${raw.delegataire}`,
      roleDelegue: raw.roleDelegue,
      dateDebut: raw.dateDebut,
      dateFin: raw.dateFin,
      motif: raw.motif || null,
    };

    this.saving = true;
    this.api.create(payload).subscribe({
      next: () => this.router.navigateByUrl('/delegations'),
      error: (err) => {
        this.saving = false;
        this.error = err?.error?.errors ? Object.values(err.error.errors).join(' ') : "Erreur lors de l'enregistrement.";
      },
    });
  }
}
