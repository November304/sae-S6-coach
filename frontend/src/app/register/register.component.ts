import { Component } from '@angular/core';
import { ApiService } from '../services/api.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-register',
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css']
})
export class RegisterComponent {
  model = {
    nom: '',
    prenom: '',
    email: '',
    password: '',
    confirmPassword: '',
    niveau: ''
  };

  passwordsMatch: boolean = true;
  emailValid: boolean = true;

  constructor(
    private apiService: ApiService,
    private router: Router
  ) {}

  checkPasswordMatch() {
    this.passwordsMatch = this.model.password === this.model.confirmPassword;
  }

  checkEmailFormat() {
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    this.emailValid = emailRegex.test(this.model.email);
  }

  onSubmit() {
    if (this.passwordsMatch && this.emailValid) {
      const body = {
        "nom": this.model.nom,
        "prenom" : this.model.prenom,
        "email": this.model.email,
        "password": this.model.password,
        "niveau_sportif": this.model.niveau
      };
      this.apiService.createSportif(body).subscribe(response => {
        if (response.code == 201) {
          this.router.navigate(['/']);
        }
      });
    }
  }
}
