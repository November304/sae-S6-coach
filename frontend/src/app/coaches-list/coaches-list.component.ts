import { Component } from '@angular/core';
import { ApiService } from '../services/api.service';
import { Coach } from '../models/coach';

@Component({
  selector: 'app-coaches-list',
  templateUrl: './coaches-list.component.html',
  styleUrl: './coaches-list.component.css'
})
export class CoachesListComponent {
  coaches: Coach[] = [];

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.apiService.getCoachsList().subscribe((data: Coach[]) => {
      this.coaches = data;
    });
  }
}
