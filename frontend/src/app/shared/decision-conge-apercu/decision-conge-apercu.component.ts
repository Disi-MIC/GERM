import { SlicePipe } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { DecisionConge } from '../../core/models/conge.model';
import { Personnel } from '../../core/models/personnel.model';
import { dateFr } from '../date-fr';
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

  get nombreJoursEnLettres(): string {
    const n = this.decision?.nombreJours;
    if (n === null || n === undefined) {
      return '';
    }
    const mots = nombreEnLettresFr(n);
    return mots.charAt(0).toUpperCase() + mots.slice(1);
  }

  /**
   * Préambule complet — texte fixe saisi par le RH Admin (visas des décrets,
   * voir ParametresDecisionConge) suivi des deux visas propres à cette
   * décision (décision antérieure de l'agent, attestation de non
   * jouissance — omis si absents) puis de la clause de clôture, toujours
   * présente. Chaque ligne pré-découpée en préfixe "VU" (mis en gras, comme
   * sur le modèle papier) et reste du texte.
   */
  get visasComplets(): { prefixe: string; reste: string }[] {
    const lignes = (this.decision?.visasDecrets ?? '')
      .split('\n')
      .map((ligne) => ligne.trim())
      .filter((ligne) => ligne.length > 0);

    if (this.decision?.numeroDerniereDecisionReferencee) {
      lignes.push(`VU la décision de congé n°${this.decision.numeroDerniereDecisionReferencee} du ${this.dateFr(this.decision.periodeDebut)} ;`);
    }
    if (this.decision?.numeroAttestationNonJouissance) {
      const dateSuffixe = this.decision.dateAttestationNonJouissance ? ` du ${this.dateFr(this.decision.dateAttestationNonJouissance)}` : '';
      lignes.push(`VU l'attestation de non jouissance de congé n°${this.decision.numeroAttestationNonJouissance}${dateSuffixe} ;`);
    }
    lignes.push('Après avis favorable du supérieur hiérarchique.');

    return lignes.map((ligne) => (ligne.startsWith('VU ') ? { prefixe: 'VU', reste: ligne.slice(2) } : { prefixe: '', reste: ligne }));
  }

  /** Une destination par ligne (voir ParametresDecisionConge::$ampliations). */
  get ampliationsListe(): string[] {
    return (this.decision?.ampliations ?? '')
      .split('\n')
      .map((ligne) => ligne.trim())
      .filter((ligne) => ligne.length > 0);
  }

  readonly dateFr = dateFr;

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
