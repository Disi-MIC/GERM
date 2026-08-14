import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { FileGridColor, FileGridItem } from './file-grid-item.model';

const COLOR_VARS: Record<FileGridColor, { fg: string; bg: string }> = {
  primary: { fg: 'var(--bs-primary)', bg: 'var(--accent-primary-lt)' },
  secondary: { fg: 'var(--bs-secondary)', bg: 'var(--accent-secondary-lt)' },
  red: { fg: 'var(--red)', bg: 'var(--red-lt)' },
  blue: { fg: 'var(--blue)', bg: 'var(--blue-lt)' },
  green: { fg: 'var(--green)', bg: 'var(--green-lt)' },
  yellow: { fg: 'var(--yellow)', bg: 'var(--yellow-lt)' },
  purple: { fg: 'var(--purple)', bg: 'var(--purple-lt)' },
  orange: { fg: 'var(--orange)', bg: 'var(--orange-lt)' },
};

/**
 * Grille de vignettes façon "file manager" Gentelella v4 (voir file_manager.html) :
 * alternative à <app-data-table> pour les listes où une icône par type/extension
 * parle plus qu'une ligne de tableau (parcs matériel/véhicule, documents).
 */
@Component({
  selector: 'app-file-grid',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './file-grid.component.html',
  styleUrl: './file-grid.component.scss',
})
export class FileGridComponent<T> {
  @Input({ required: true }) items: FileGridItem<T>[] = [];
  @Input() emptyMessage = 'Aucun élément.';
  @Input() searchPlaceholder = 'Rechercher...';
  @Output() readonly itemClick = new EventEmitter<T>();

  query = '';

  get filtered(): FileGridItem<T>[] {
    const q = this.query.trim().toLowerCase();
    if (!q) {
      return this.items;
    }
    return this.items.filter((item) => `${item.name} ${item.meta ?? ''}`.toLowerCase().includes(q));
  }

  colorFg(color: FileGridColor): string {
    return COLOR_VARS[color].fg;
  }

  colorBg(color: FileGridColor): string {
    return COLOR_VARS[color].bg;
  }

  onClick(item: FileGridItem<T>): void {
    this.itemClick.emit(item.row);
  }
}
