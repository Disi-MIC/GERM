import { Component, ElementRef, HostListener, forwardRef, Input, OnChanges, SimpleChanges, ViewChild } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';

export interface SearchableSelectOption {
  value: unknown;
  label: string;
}

/**
 * Remplace un <select> classique quand la liste peut compter des dizaines
 * voire des centaines d'entrées (agents, matériel...) — taper filtre les
 * options au lieu de dérouler une liste illisible. Implémente
 * ControlValueAccessor : s'utilise indifféremment avec formControlName
 * (formulaires réactifs, la majorité des cas) ou [(ngModel)] (ex. le
 * sélecteur par ligne d'AffectationMaterielComponent), sans code
 * supplémentaire côté composant consommateur au-delà du mapping de ses
 * données en `SearchableSelectOption[]`.
 */
@Component({
  selector: 'app-searchable-select',
  standalone: true,
  templateUrl: './searchable-select.component.html',
  styleUrl: './searchable-select.component.scss',
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => SearchableSelectComponent),
      multi: true,
    },
  ],
})
export class SearchableSelectComponent implements ControlValueAccessor, OnChanges {
  @Input({ required: true }) options: SearchableSelectOption[] = [];
  @Input() placeholder = 'Rechercher...';
  /** Libellé de l'option "vide" (ex. "Aucune", "Sélectionner...") — omis si non fourni (champ obligatoire sans option vide). */
  @Input() nullLabel: string | null = null;
  disabled = false;

  @ViewChild('champRecherche') private readonly champRecherche?: ElementRef<HTMLInputElement>;

  ouvert = false;
  recherche = '';
  surligne = -1;
  /** Coordonnées du menu (position: fixed, voir searchable-select.component.scss) — recalculées à l'ouverture et tant qu'elle reste ouverte. */
  positionMenu = { top: 0, left: 0, width: 0 };

  /** Public (pas juste pour l'API interne) : lu par le template pour l'état "active" de l'option sélectionnée. */
  valeur: unknown = null;
  private onChange: (value: unknown) => void = () => {};
  private onTouched: () => void = () => {};

  writeValue(value: unknown): void {
    this.valeur = value;
    this.recherche = this.labelPour(value);
  }

  /**
   * Si `options` arrive après writeValue() (cas courant : valeur poussée par
   * patchValue() dès ngOnInit — ex. préremplissage par query param — pendant
   * que la liste se charge encore de façon asynchrone), le libellé résolu à
   * ce moment-là était vide. Une fois les options disponibles, on
   * resynchronise l'affichage — jamais pendant que le menu est ouvert, pour
   * ne pas écraser une recherche en cours de saisie.
   */
  ngOnChanges(changes: SimpleChanges): void {
    if (changes['options'] && !this.ouvert) {
      this.recherche = this.labelPour(this.valeur);
    }
  }

  registerOnChange(fn: (value: unknown) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(disabled: boolean): void {
    this.disabled = disabled;
  }

  private labelPour(value: unknown): string {
    if (value === null || value === undefined) {
      return this.nullLabel ?? '';
    }
    return this.options.find((o) => o.value === value)?.label ?? '';
  }

  get optionsFiltrees(): SearchableSelectOption[] {
    const terme = this.recherche.trim().toLowerCase();
    const inchange = terme === this.labelPour(this.valeur).trim().toLowerCase();
    const base = terme && !inchange ? this.options.filter((o) => o.label.toLowerCase().includes(terme)) : this.options;
    return this.nullLabel !== null ? [{ value: null, label: this.nullLabel }, ...base] : base;
  }

  /**
   * Sélectionne tout le texte existant au focus : sans ça, taper alors que
   * le champ affiche déjà un libellé (valeur courante ou nullLabel)
   * l'ajoute à la suite au lieu de le remplacer — piège classique d'un
   * champ texte réutilisé comme select, absent d'un <select> natif.
   */
  ouvrir(): void {
    if (this.disabled) {
      return;
    }
    this.ouvert = true;
    this.surligne = -1;
    this.positionnerMenu();
    this.champRecherche?.nativeElement.select();
  }

  saisir(texte: string): void {
    this.recherche = texte;
    this.ouvert = true;
    this.surligne = -1;
    this.positionnerMenu();
  }

  /** Sous le champ de recherche, en coordonnées viewport (position: fixed) — recalculé au scroll/resize tant que le menu reste ouvert. */
  private positionnerMenu(): void {
    const champ = this.champRecherche?.nativeElement;
    if (!champ) {
      return;
    }
    const rect = champ.getBoundingClientRect();
    this.positionMenu = { top: rect.bottom + 2, left: rect.left, width: rect.width };
  }

  // Repositionne le menu (position: fixed, coordonnées figées à l'ouverture)
  // quand la page défile ou que la fenêtre est redimensionnée — sans ça, le
  // menu resterait figé à ses coordonnées d'ouverture pendant que le champ,
  // lui, se déplace sous le scroll.
  @HostListener('window:scroll')
  @HostListener('window:resize')
  onFenetreBouge(): void {
    if (this.ouvert) {
      this.positionnerMenu();
    }
  }

  choisir(option: SearchableSelectOption): void {
    this.valeur = option.value;
    this.recherche = option.label;
    this.ouvert = false;
    this.onChange(this.valeur);
    this.onTouched();
  }

  /** Délai avant de refermer/réconcilier : laisse le (click) sur une option se déclencher avant que le blur n'agisse. */
  fermer(): void {
    window.setTimeout(() => {
      this.ouvert = false;
      const correspondance = this.options.find((o) => o.label === this.recherche);
      if (!correspondance && this.recherche !== (this.nullLabel ?? '')) {
        this.recherche = this.labelPour(this.valeur);
      }
      this.onTouched();
    }, 150);
  }

  onKeydown(event: KeyboardEvent): void {
    const liste = this.optionsFiltrees;
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      this.ouvert = true;
      this.surligne = Math.min(this.surligne + 1, liste.length - 1);
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      this.surligne = Math.max(this.surligne - 1, 0);
    } else if (event.key === 'Enter') {
      if (this.ouvert && this.surligne >= 0 && liste[this.surligne]) {
        event.preventDefault();
        this.choisir(liste[this.surligne]);
      }
    } else if (event.key === 'Escape') {
      this.ouvert = false;
      (event.target as HTMLInputElement).blur();
    }
  }
}
