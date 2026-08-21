import { AfterViewInit, Component, ElementRef, Input, OnDestroy, ViewChild } from '@angular/core';

export type EtatEtapeTimeline = 'termine' | 'actuel' | 'a-venir' | 'rejete';

export interface EtapeTimeline {
  label: string;
  /** Déjà formaté par l'appelant (ex. "16/08/2026 · 14:32") — null/undefined affiche "—", comme une étape pas encore atteinte. */
  sousTitre?: string | null;
  etat: EtatEtapeTimeline;
}

/**
 * Frise de statut (créée → étapes intermédiaires → décision finale), utilisée
 * sur les pages de traitement des demandes/tickets et dans les modals
 * "Statut de ma demande" — même composant en web et en mobile, ces pages
 * étant partagées entre les deux (voir app.routes.ts). Chaque domaine (carte
 * pro, congés, tickets) calcule sa propre liste d'étapes à partir de son
 * statut ; ce composant se contente de l'afficher, sur une seule ligne
 * horizontale quand la largeur disponible le permet, sinon en frise
 * verticale (jamais de défilement horizontal ni de repli à moitié cassé où
 * une seule étape se retrouve seule sur une 2e ligne — voir
 * status-timeline.component.scss).
 */
@Component({
  selector: 'app-status-timeline',
  standalone: true,
  templateUrl: './status-timeline.component.html',
  styleUrl: './status-timeline.component.scss',
})
export class StatusTimelineComponent implements AfterViewInit, OnDestroy {
  @Input({ required: true }) etapes: EtapeTimeline[] = [];

  @ViewChild('timeline') private readonly timelineRef!: ElementRef<HTMLElement>;

  private resizeObserver?: ResizeObserver;

  ngAfterViewInit(): void {
    this.resizeObserver = new ResizeObserver(() => this.recalculerDisposition());
    this.resizeObserver.observe(this.timelineRef.nativeElement);
    this.recalculerDisposition();
  }

  ngOnDestroy(): void {
    this.resizeObserver?.disconnect();
  }

  /**
   * Retire temporairement --verticale pour mesurer la disposition
   * horizontale "candidate" (celle que produirait flex-wrap seul) : si les
   * étapes ne partagent pas toutes le même offsetTop, au moins une a reflué
   * sur une ligne suivante — on bascule alors tout en vertical plutôt que de
   * laisser ce repli partiel. Remesurer à chaque redimensionnement (pas
   * seulement au premier rendu) permet aussi de repasser à l'horizontale si
   * le conteneur s'élargit ensuite (ex. rotation d'écran).
   */
  private recalculerDisposition(): void {
    const el = this.timelineRef.nativeElement;
    el.classList.remove('germ-timeline--verticale');
    const etapesEl = Array.from(el.children) as HTMLElement[];
    const tientSurUneLigne = new Set(etapesEl.map((e) => e.offsetTop)).size <= 1;
    el.classList.toggle('germ-timeline--verticale', !tientSurUneLigne);
  }
}
