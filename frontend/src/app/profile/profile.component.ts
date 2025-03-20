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
  public password: string = "";
  public niveauSportif: string = "";
  public message: string = "";

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.apiService.getSportifMe().subscribe(
      (data) => {
        this.nom = data.nom;
        this.prenom = data.prenom;
        this.email = data.email;
        this.niveauSportif = data.niveau_sportif;
      },
      (error) => {
        console.error("Erreur lors de la récupération du profil :", error);
      }
    );
  }

  updateProfile() {
    let updateData: any = {
      nom: this.nom,
      prenom: this.prenom,
      email: this.email,
      niveau_sportif: this.niveauSportif
    };

    if (this.password) {
      updateData.password = this.password;
    }

    this.apiService.updateSelf(updateData).subscribe(
      () => {
        this.message = "Profil mis à jour avec succès !";
        this.password = "";
      },
      (error) => {
        console.error("Erreur lors de la mise à jour du profil :", error);
      }
    );
  }
}
