import { Component, OnInit } from '@angular/core';
import { Seance } from '../models/seance';
import { Coach } from '../models/coach';
import { ApiService } from '../services/api.service';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-seance-detail',
  templateUrl: './seance-detail.component.html',
  styleUrls: ['./seance-detail.component.css'],
})
export class SeanceDetailComponent implements OnInit {
  seances: Seance[] = [];
  coaches: Coach[] = [];
  expandedSeances: { [key: number]: boolean } = {};

  constructor(private apiService: ApiService, public authService: AuthService) {}

  ngOnInit() {
    this.apiService.getSeanceList().subscribe((data: Seance[]) => {
      this.seances = data;
    });

    this.apiService.getCoachsList().subscribe((data: Coach[]) => {
      this.coaches = data;
    });
  }

  getCoachName(coach_id: number): string {
    const coach = this.coaches.find((c) => c.id === coach_id);
    return coach ? `${coach.nom} ${coach.prenom}` : 'Coach inconnu';
  }

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

  toggleDetails(seanceId: number) {
    this.expandedSeances[seanceId] = !this.expandedSeances[seanceId];
  }
}
