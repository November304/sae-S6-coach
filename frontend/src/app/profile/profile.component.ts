import { Component, OnInit } from '@angular/core';
import { ApiService } from '../services/api.service';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.component.html',
  styleUrls: ['./profile.component.css']
})
export class ProfileComponent implements OnInit {
  public nom: string = "";
  public prenom: string = "";
  public email: string = "";
  public niveauSportif: string = "";
  public dateInscription: Date = new Date();

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.apiService.getSportifMe().subscribe(
      (data) => {
        this.nom = data.nom;
        this.prenom = data.prenom;
        this.email = data.email;
        this.niveauSportif = data.niveau_sportif;
        this.dateInscription = data.date_inscription;
      },
      (error) => {
        console.error("❌ Erreur lors de la récupération du profil :", error);
      }
    );
  }

  formatDate(dateString: Date): string {
    const date = new Date(dateString);
    return `${date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    })}`;
  }
}
