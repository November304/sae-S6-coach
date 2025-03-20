import { Component, OnInit } from '@angular/core';
import { ApiService } from '../services/api.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-edit-profile',
  templateUrl: './edit-profile.component.html',
  styleUrls: ['./edit-profile.component.css']
})
export class EditProfileComponent implements OnInit {
  public nom: string = "";
  public prenom: string = "";
  public email: string = "";

  constructor(private apiService: ApiService, private router: Router) {}

  ngOnInit() {
    // Récupérer les infos actuelles du profil
    this.apiService.getSportifMe().subscribe(
      (data) => {
        this.nom = data.nom;
        this.prenom = data.prenom;
        this.email = data.email;
      },
      (error) => {
        console.error("❌ Erreur lors de la récupération du profil :", error);
      }
    );
  }

  updateProfile() {
    const updatedData: any = {};

    if (this.nom.trim() !== "") updatedData.nom = this.nom;
    if (this.prenom.trim() !== "") updatedData.prenom = this.prenom;
    if (this.email.trim() !== "") updatedData.email = this.email;

    if (Object.keys(updatedData).length === 0) {
      alert("Aucune modification détectée.");
      return;
    }

    this.apiService.updateSelf(updatedData).subscribe(
      () => {
        this.router.navigate(['/profile']);
      },
      (error) => {
        console.error("❌ Erreur lors de la mise à jour :", error);
      }
    );
  }
}
