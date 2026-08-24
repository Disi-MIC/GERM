import { Component, EventEmitter, OnInit, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Personnel, ServiceRef } from '../../core/models/personnel.model';
import { PersonnelApiService } from '../../pages/personnel/personnel-api.service';
import { SearchableSelectComponent, SearchableSelectOption } from '../searchable-select/searchable-select.component';

type Mode = 'aucun' | 'agent' | 'service';

/**
 * Filtre "par agent ou par service" réutilisé partout où l'on veut réduire une
 * liste de matériel à un périmètre précis (ex. cartouches-informatique) —
 * MaterielInformatique::$service est déjà le service effectif (dérivé de
 * l'agent affecté quand il y en a un, sinon propre au matériel, voir son
 * commentaire de champ), donc filtrer par agent revient toujours à résoudre
 * le service de cet agent puis à filtrer par ce service : un seul événement
 * de sortie (l'id de service, ou null si aucun filtre) suffit, quel que soit
 * le mode choisi par l'utilisateur.
 */
@Component({
  selector: 'app-agent-ou-service-filtre',
  standalone: true,
  imports: [FormsModule, SearchableSelectComponent],
  templateUrl: './agent-ou-service-filtre.component.html',
  styleUrl: './agent-ou-service-filtre.component.scss',
})
export class AgentOuServiceFiltreComponent implements OnInit {
  @Output() serviceIdChange = new EventEmitter<number | null>();

  mode: Mode = 'aucun';
  personnels: Personnel[] = [];
  services: ServiceRef[] = [];
  agentSelectionne: number | null = null;
  serviceSelectionne: number | null = null;

  constructor(private readonly personnelApi: PersonnelApiService) {}

  ngOnInit(): void {
    this.personnelApi.getAll().subscribe((personnels) => (this.personnels = personnels));
    this.personnelApi.getServices().subscribe((services) => (this.services = services));
  }

  get agentOptions(): SearchableSelectOption[] {
    return this.personnels.map((p) => ({ value: p.id, label: p.nomComplet ?? p.matricule ?? '' }));
  }

  get serviceOptions(): SearchableSelectOption[] {
    return this.services.map((s) => ({
      value: s.id,
      label: s.responsableNom ? `${s.nom} — ${s.responsableNom}` : s.nom,
    }));
  }

  changerMode(mode: Mode): void {
    this.mode = mode;
    this.agentSelectionne = null;
    this.serviceSelectionne = null;
    this.serviceIdChange.emit(null);
  }

  agentChange(agentId: number | null): void {
    this.agentSelectionne = agentId;
    const agent = this.personnels.find((p) => p.id === agentId);
    const service = agent?.service;
    const serviceId = service && typeof service !== 'string' ? service.id : null;
    this.serviceIdChange.emit(serviceId);
  }

  serviceChange(serviceId: number | null): void {
    this.serviceSelectionne = serviceId;
    this.serviceIdChange.emit(serviceId);
  }
}
