import { Component } from '@angular/core';
import { faDumbbell, faHeartPulse, faUsers } from '@fortawesome/free-solid-svg-icons';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css']
})
export class HomeComponent {
  faDumbbell = faDumbbell;
  faHeartPulse = faHeartPulse;
  faUsers = faUsers;
}
