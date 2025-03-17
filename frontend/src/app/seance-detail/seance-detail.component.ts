import { Component, Input, Output, EventEmitter } from '@angular/core';
import { Seance } from '../models/seance';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-seance-detail',
  templateUrl: './seance-detail.component.html',
  styleUrls: ['./seance-detail.component.css'],
})
export class SeanceDetailComponent {
    constructor(
      public authService: AuthService,
    ) {}
  @Input() seance!: Seance;
  @Output() close = new EventEmitter<void>();

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
}
