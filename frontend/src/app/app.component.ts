import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { AdminAccessModalComponent } from './shared/admin-access-modal/admin-access-modal.component';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, AdminAccessModalComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss'
})
export class AppComponent {
  title = 'frontend';
}
