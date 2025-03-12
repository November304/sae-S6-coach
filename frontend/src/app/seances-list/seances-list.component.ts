import { Component, OnInit } from '@angular/core';
import { Seance } from '../models/seance';
import { Coach } from '../models/coach';
import { ApiService } from '../services/api.service';

@Component({
  selector: 'app-seances-list',
  templateUrl: './seances-list.component.html',
  styleUrls: ['./seances-list.component.css']
})
export class SeancesListComponent implements OnInit {
  seances: Seance[] = [];
  coaches: Coach[] = [];

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.apiService.getSeanceList().subscribe((data: Seance[]) => {
      this.seances = data;
    });

    this.apiService.getCoachsList().subscribe((data: Coach[]) => {
      this.coaches = data;
    });
  }

  getCoachName(coach_id: number): string {
    const coach = this.coaches.find(c => c.id === coach_id);
    return coach ? `${coach.nom} ${coach.prenom}` : 'Coach inconnu';
  }
}
