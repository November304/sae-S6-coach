import { Component, OnInit } from '@angular/core';
import { AuthService } from './services/auth.service';
import { ApiService } from './services/api.service';
import { Router } from '@angular/router';
import { faUserCircle } from '@fortawesome/free-solid-svg-icons';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrl: './app.component.css',
})
export class AppComponent implements OnInit {
  faUserCircle = faUserCircle;
  nom: string = '';
  prenom: string = '';

  constructor(
    public authService: AuthService,
    private apiService: ApiService,
    private router: Router
  ) {}

  ngOnInit() {
    this.apiService.getSportifMe().subscribe(
      (data) => {
        console.log('Données du sportif :', data); // Debug

        if (data && data.nom && data.prenom) {
          this.nom = data.nom;
          this.prenom = data.prenom;
        } else {
          console.warn('Les données du sportif sont incomplètes !');
        }
      },
      (error) => {
        console.error('Erreur lors de la récupération du profil :', error);
      }
    );
  }

  logout() {
    this.authService.logout();
    this.router.navigateByUrl('/');
  }
}
