import { NgTemplateOutlet } from '@angular/common';
import { Component, ContentChildren, Input, OnChanges, QueryList, TemplateRef } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { PanelComponent } from '../panel/panel.component';
import { DataTableCellDirective } from './data-table-cell.directive';
import { DataTableColumn } from './data-table-column.model';

type SortDirection = 'asc' | 'desc';

/**
 * Tableau générique (tri par colonne, recherche texte, pagination, colonnes
 * masquables) qui remplace le `<app-panel><table>...</table></app-panel>`
 * dupliqué dans chaque page de liste. Le rendu de chaque cellule reste
 * entièrement à la charge de la page consommatrice, projeté via
 * <ng-template appDataTableCell="key"> — ce composant ne connaît que les
 * valeurs de tri/recherche déclarées sur DataTableColumn.value, jamais la
 * structure réelle de T.
 */
@Component({
  selector: 'app-data-table',
  standalone: true,
  imports: [FormsModule, NgTemplateOutlet, PanelComponent],
  templateUrl: './data-table.component.html',
  styleUrl: './data-table.component.scss',
})
export class DataTableComponent<T> implements OnChanges {
  @Input() columns: DataTableColumn<T>[] = [];
  @Input() rows: T[] = [];
  @Input() trackByFn: (index: number, row: T) => unknown = (_, row: unknown) => (row as { id?: unknown })?.id ?? row;
  @Input() emptyMessage = 'Aucun résultat.';
  @Input() searchPlaceholder = 'Rechercher...';
  @Input() pageSize = 20;
  /** Transmis tel quel à <app-panel> — utile quand plusieurs tableaux se partagent une page (voir MesCongesComponent). */
  @Input() title?: string;
  @Input() icon?: string;

  @ContentChildren(DataTableCellDirective) cellTemplates!: QueryList<DataTableCellDirective>;

  query = '';
  sortKey: string | null = null;
  sortDir: SortDirection = 'asc';
  page = 1;
  private hiddenKeys = new Set<string>();
  private seenColumnKeys = new Set<string>();

  ngOnChanges(): void {
    // Colonnes décochées par défaut (hiddenByDefault) — appliqué une seule
    // fois via une clé jamais revue plutôt qu'à chaque changement, pour ne
    // pas écraser un choix déjà fait par l'utilisateur pendant la session.
    for (const column of this.columns) {
      if (column.hiddenByDefault && !this.hiddenKeys.has(column.key) && !this.seenColumnKeys.has(column.key)) {
        this.hiddenKeys.add(column.key);
      }
      this.seenColumnKeys.add(column.key);
    }
    this.page = Math.min(this.page, this.totalPages);
  }

  get toggleableColumns(): DataTableColumn<T>[] {
    return this.columns.filter((c) => !c.alwaysVisible);
  }

  get visibleColumns(): DataTableColumn<T>[] {
    return this.columns.filter((c) => c.alwaysVisible || !this.hiddenKeys.has(c.key));
  }

  isColumnVisible(column: DataTableColumn<T>): boolean {
    return !this.hiddenKeys.has(column.key);
  }

  toggleColumn(column: DataTableColumn<T>): void {
    if (this.hiddenKeys.has(column.key)) {
      this.hiddenKeys.delete(column.key);
    } else {
      this.hiddenKeys.add(column.key);
    }
  }

  cellTemplateFor(key: string): TemplateRef<{ $implicit: T }> | null {
    const found = this.cellTemplates?.find((t) => t.columnKey === key);
    return (found?.templateRef as TemplateRef<{ $implicit: T }>) ?? null;
  }

  sortBy(column: DataTableColumn<T>): void {
    if (!column.sortable) {
      return;
    }
    if (this.sortKey !== column.key) {
      this.sortKey = column.key;
      this.sortDir = 'asc';
    } else if (this.sortDir === 'asc') {
      this.sortDir = 'desc';
    } else {
      this.sortKey = null;
    }
    this.page = 1;
  }

  onQueryChange(): void {
    this.page = 1;
  }

  private get filteredRows(): T[] {
    const q = this.query.trim().toLowerCase();
    if (!q) {
      return this.rows;
    }

    const searchableColumns = this.columns.filter((c) => c.value && c.searchable !== false);

    return this.rows.filter((row) =>
      searchableColumns.some((c) => String(c.value!(row) ?? '').toLowerCase().includes(q)),
    );
  }

  private get sortedRows(): T[] {
    if (!this.sortKey) {
      return this.filteredRows;
    }
    const column = this.columns.find((c) => c.key === this.sortKey);
    if (!column?.value) {
      return this.filteredRows;
    }

    const direction = this.sortDir === 'asc' ? 1 : -1;
    return [...this.filteredRows].sort((a, b) => direction * this.compare(column.value!(a), column.value!(b)));
  }

  private compare(a: string | number | Date | null | undefined, b: string | number | Date | null | undefined): number {
    if (a == null && b == null) {
      return 0;
    }
    if (a == null) {
      return -1;
    }
    if (b == null) {
      return 1;
    }
    if (a instanceof Date && b instanceof Date) {
      return a.getTime() - b.getTime();
    }
    if (typeof a === 'number' && typeof b === 'number') {
      return a - b;
    }
    return String(a).localeCompare(String(b), 'fr', { sensitivity: 'base' });
  }

  get totalPages(): number {
    return Math.max(1, Math.ceil(this.sortedRows.length / this.pageSize));
  }

  get pagedRows(): T[] {
    const start = (this.page - 1) * this.pageSize;
    return this.sortedRows.slice(start, start + this.pageSize);
  }

  get totalCount(): number {
    return this.sortedRows.length;
  }

  /** Échelle de pages restée volontairement simple (pas d'ellipse "…") : les
   * listes de ce parc restent de taille modeste, quelques pages tout au plus. */
  get pageNumbers(): number[] {
    return Array.from({ length: this.totalPages }, (_, i) => i + 1);
  }

  goToPage(page: number): void {
    this.page = Math.min(Math.max(1, page), this.totalPages);
  }
}
