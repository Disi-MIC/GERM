import { SlicePipe } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { DecisionConge } from '../../core/models/conge.model';
import { Personnel } from '../../core/models/personnel.model';

/**
 * Aperçu du document final de décision de congé — informations déjà
 * disponibles côté API (voir DecisionConge côté serveur), sans texte légal
 * (articles, formule exécutoire...) puisque ce libellé n'a pas encore été
 * fourni : affiche les données, pas un fac-similé du document papier signé.
 * Utilisé à l'identique par l'agent (mes-conges), le RH Congé et le RH Admin
 * (decision-conge-list, demande-decision-traiter) — mêmes informations pour
 * les trois, seule la source des données (déjà chargée par le composant
 * parent) diffère selon le contexte d'accès.
 */
@Component({
  selector: 'app-decision-conge-apercu',
  standalone: true,
  imports: [SlicePipe],
  templateUrl: './decision-conge-apercu.component.html',
  styleUrl: './decision-conge-apercu.component.scss',
})
export class DecisionCongeApercuComponent {
  @Input() decision: DecisionConge | null = null;
  @Output() fermer = new EventEmitter<void>();

  private get personnel(): Personnel | null {
    const p = this.decision?.personnel;
    return p && typeof p !== 'string' ? p : null;
  }

  get agentNomComplet(): string {
    const p = this.personnel;
    return p ? (p.nomComplet ?? `${p.prenom} ${p.nom}`) : '';
  }

  get agentMatricule(): string {
    return this.personnel?.matricule ?? '—';
  }

  get agentFonction(): string {
    return this.personnel?.fonction ?? '—';
  }

  get agentGrade(): string | null {
    return this.personnel?.grade ?? null;
  }

  get agentService(): string {
    const service = this.personnel?.service;
    if (!service) {
      return '—';
    }
    return typeof service === 'string' ? service : service.nom;
  }

  get agentTypeContrat(): string {
    const typeContrat = this.personnel?.typeContrat;
    if (!typeContrat) {
      return '—';
    }
    return typeof typeContrat === 'string' ? typeContrat : typeContrat.libelle;
  }

  /**
   * N'imprime que le contenu du document (.decision-apercu-imprimable, voir
   * styles.scss) plutôt que la page entière (menu, en-tête...) — bascule un
   * marqueur sur <body> le temps de l'impression, seule façon fiable de
   * cibler ce sous-arbre quel que soit l'endroit où ce composant est monté
   * dans l'arbre (impossible à exprimer avec un simple @media print local
   * au composant, portée à l'élément hôte uniquement).
   */
  imprimer(): void {
    document.body.classList.add('decision-apercu-impression');
    const nettoyer = () => {
      document.body.classList.remove('decision-apercu-impression');
      window.removeEventListener('afterprint', nettoyer);
    };
    window.addEventListener('afterprint', nettoyer);
    window.print();
  }
}
