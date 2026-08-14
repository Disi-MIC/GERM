import { AfterViewInit, Component, ElementRef, Input, OnChanges, OnDestroy, SimpleChanges, ViewChild } from '@angular/core';
import {
  ArcElement,
  BarController,
  BarElement,
  CategoryScale,
  Chart,
  ChartData,
  ChartOptions,
  ChartType,
  DoughnutController,
  Legend,
  LinearScale,
  PieController,
  Tooltip,
} from 'chart.js';

// Enregistrement ciblé plutôt que `...registerables` (tout Chart.js, tous
// types de graphique confondus) : seuls bar/doughnut/pie sont utilisés par
// les tableaux de bord, l'enregistrement complet alourdissait le bundle
// initial de ~200 ko pour des contrôleurs jamais rendus (radar, bulle...).
Chart.register(BarController, DoughnutController, PieController, ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

/**
 * Fine encapsulation de Chart.js pour un rendu déclaratif façon Angular —
 * les tableaux de bord n'ont besoin que de passer `type`/`data`/`options`,
 * sans manipuler l'instance Chart.js ni son cycle de vie (canvas, destroy).
 */
@Component({
  selector: 'app-chart',
  standalone: true,
  template: `
    <div [style.height.px]="height">
      <canvas #canvas></canvas>
    </div>
  `,
  styles: [
    `
      :host {
        display: block;
      }
    `,
  ],
})
export class ChartComponent implements AfterViewInit, OnChanges, OnDestroy {
  @Input({ required: true }) type!: ChartType;
  @Input({ required: true }) data!: ChartData;
  @Input() options?: ChartOptions;
  @Input() height = 240;

  @ViewChild('canvas') private readonly canvasRef!: ElementRef<HTMLCanvasElement>;
  private chart?: Chart;

  ngAfterViewInit(): void {
    this.chart = new Chart(this.canvasRef.nativeElement, {
      type: this.type,
      data: this.data,
      options: { responsive: true, maintainAspectRatio: false, ...this.options },
    });
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (!this.chart) {
      return;
    }
    if (changes['data']) {
      this.chart.data = this.data;
    }
    if (changes['options'] && this.options) {
      this.chart.options = { responsive: true, maintainAspectRatio: false, ...this.options };
    }
    this.chart.update();
  }

  ngOnDestroy(): void {
    this.chart?.destroy();
  }
}
