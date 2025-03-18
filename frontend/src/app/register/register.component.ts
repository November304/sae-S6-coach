import { Component } from '@angular/core';

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

  checkPasswordMatch() {
    this.passwordsMatch = this.model.password === this.model.confirmPassword;
  }

  onSubmit() {
    if (this.passwordsMatch) {
      console.log("Inscription soumise :", this.model);
    }
  }
}
