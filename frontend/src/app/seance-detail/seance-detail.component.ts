import { Component, Input, Output, EventEmitter } from '@angular/core';
import { Seance } from '../models/seance';
import { AuthService } from '../services/auth.service';
import { ApiService } from '../services/api.service';

@Component({
  selector: 'app-seance-detail',
  templateUrl: './seance-detail.component.html',
  styleUrls: ['./seance-detail.component.css'],
})
export class SeanceDetailComponent {
  @Input() seance!: Seance;
  @Output() close = new EventEmitter<void>();
  reservationMessage: string | null = null;
  reservationError: string | null = null;

  constructor(
    public authService: AuthService,
    private apiService: ApiService
  ) {}

  getPlacesMax(type_seance: string): number {
    switch (type_seance.toLowerCase()) {
      case 'solo':
        return 1;
      case 'duo':
        return 2;
      case 'trio':
        return 3;
      default:
        return 0;
    } 
  }

  reserveSeance() {
    if (!this.authService.currentAuthUserValue.isLogged()) {
      return;
    }

    this.reservationMessage = null;
    this.reservationError = null;

    this.apiService.ReserveSeance(this.seance.id).subscribe({
      next: (response) => {
        this.reservationMessage = response.message;
      },
      error: (err) => {
        console.error("❌ Erreur lors de la réservation :", err);
        
        this.reservationError = err.error?.error;
      }
    });
  }
}
