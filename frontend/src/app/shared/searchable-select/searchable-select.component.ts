import { Component, ElementRef, forwardRef, Input, ViewChild } from '@angular/core';
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
export class SearchableSelectComponent implements ControlValueAccessor {
  @Input({ required: true }) options: SearchableSelectOption[] = [];
  @Input() placeholder = 'Rechercher...';
  /** Libellé de l'option "vide" (ex. "Aucune", "Sélectionner...") — omis si non fourni (champ obligatoire sans option vide). */
  @Input() nullLabel: string | null = null;
  disabled = false;

  @ViewChild('champRecherche') private readonly champRecherche?: ElementRef<HTMLInputElement>;

  ouvert = false;
  recherche = '';
  surligne = -1;

  /** Public (pas juste pour l'API interne) : lu par le template pour l'état "active" de l'option sélectionnée. */
  valeur: unknown = null;
  private onChange: (value: unknown) => void = () => {};
  private onTouched: () => void = () => {};

  writeValue(value: unknown): void {
    this.valeur = value;
    this.recherche = this.labelPour(value);
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
    this.champRecherche?.nativeElement.select();
  }

  saisir(texte: string): void {
    this.recherche = texte;
    this.ouvert = true;
    this.surligne = -1;
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
