import { Component } from '@angular/core';
import { faDumbbell, faHeartPulse, faUsers, faLocationDot, faPhone, faEnvelope } from '@fortawesome/free-solid-svg-icons';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent {
  faDumbbell = faDumbbell;
  faHeartPulse = faHeartPulse;
  faUsers = faUsers;
  faLocationDot = faLocationDot;
  faPhone = faPhone;
  faEnvelope = faEnvelope;
  
  currentYear = new Date().getFullYear();
  
  imagePath: string = 'assets/salle-sport.jpg';

  constructor(
    public authService: AuthService,
  ) {}
}
