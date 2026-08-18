import { SlicePipe } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { DecisionConge } from '../../core/models/conge.model';
import { Personnel } from '../../core/models/personnel.model';
import { nombreEnLettresFr } from '../nombre-en-lettres';

/**
 * Aperçu du document final de décision de congé, mis en page pour se
 * rapprocher du modèle papier réel du ministère (visas, article premier,
 * articles 2/3, ampliations) — informations déjà disponibles côté API (voir
 * DecisionConge côté serveur). Le texte légal fixe (visas des décrets,
 * articles 2/3, ampliations) vient de ParametresDecisionConge, copié sur la
 * décision au moment de sa génération ; les deux visas variables (décision
 * antérieure de l'agent, attestation de non jouissance) et la clause "Après
 * avis favorable..." sont assemblés ici, propres à chaque décision. Utilisé
 * à l'identique par l'agent (mes-conges), le RH Congé et le RH Admin
 * (decision-conge-list, demande-decision-traiter).
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

  get agentCivilite(): string {
    return this.personnel?.sexe === 'F' ? 'Madame' : 'Monsieur';
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

  get analyseTexte(): string {
    return `Décision accordant un congé administratif à ${this.agentCivilite} ${this.agentNomComplet}, ${this.agentFonction}, matricule de solde n°${this.agentMatricule}.`;
  }

  get nombreJoursEnLettres(): string {
    const n = this.decision?.nombreJours;
    if (n === null || n === undefined) {
      return '';
    }
    const mots = nombreEnLettresFr(n);
    return mots.charAt(0).toUpperCase() + mots.slice(1);
  }

  /** Une "VU ..." par ligne, texte fixe saisi par le RH Admin (voir ParametresDecisionConge) — vide si aucun réglage renseigné. */
  get visasFixes(): string[] {
    return (this.decision?.visasDecrets ?? '')
      .split('\n')
      .map((ligne) => ligne.trim())
      .filter((ligne) => ligne.length > 0);
  }

  /** Une destination par ligne (voir ParametresDecisionConge::$ampliations). */
  get ampliationsListe(): string[] {
    return (this.decision?.ampliations ?? '')
      .split('\n')
      .map((ligne) => ligne.trim())
      .filter((ligne) => ligne.length > 0);
  }

  private static readonly MOIS_FR = [
    'janvier',
    'février',
    'mars',
    'avril',
    'mai',
    'juin',
    'juillet',
    'août',
    'septembre',
    'octobre',
    'novembre',
    'décembre',
  ];

  /** "2023-03-24" -> "24 mars 2023", convention du document papier (voir le modèle fourni par le ministère). */
  dateFr(iso: string | null | undefined): string {
    if (!iso) {
      return '';
    }
    const [annee, mois, jour] = iso.slice(0, 10).split('-').map(Number);
    if (!annee || !mois || !jour) {
      return iso.slice(0, 10);
    }
    return `${jour} ${DecisionCongeApercuComponent.MOIS_FR[mois - 1]} ${annee}`;
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
