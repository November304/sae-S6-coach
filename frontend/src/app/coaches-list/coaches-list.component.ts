import { Component } from '@angular/core';
import { ApiService } from '../services/api.service';
import { Coach } from '../models/coach';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-coaches-list',
  templateUrl: './coaches-list.component.html',
  styleUrl: './coaches-list.component.css'
})
export class CoachesListComponent {
  coaches: Coach[] = [];

  constructor(private apiService: ApiService, public authService: AuthService) {}

  ngOnInit() {
    const isLogged = this.authService.currentAuthUserValue.isLogged();

    if (!isLogged) {
      this.apiService.getCoachsListPublic().subscribe((data: Coach[]) => {
        this.coaches = data;
      });
    } else {
      this.apiService.getCoachsList().subscribe((data: Coach[]) => {
        this.coaches = data;
      });
    }
  }
}
