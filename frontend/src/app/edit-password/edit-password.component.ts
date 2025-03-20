import { Component } from '@angular/core';
import { ApiService } from '../services/api.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-edit-password',
  templateUrl: './edit-password.component.html',
  styleUrls: ['./edit-password.component.css']
})
export class EditPasswordComponent {
  model = {
    password: '',
    confirmPassword: ''
  };

  passwordsMatch: boolean = true;

  constructor(private apiService: ApiService, private router: Router) {}

  checkPasswordMatch() {
    this.passwordsMatch = this.model.password === this.model.confirmPassword;
  }

  onSubmit() {
    if (this.passwordsMatch && this.model.password.trim() !== '') {
      const body = {
        password: this.model.password
      };

      this.apiService.updateSelfPwd(body).subscribe(
        () => {
          this.router.navigate(['/espace-perso']);
        },
        (error) => {
          console.error("❌ Erreur lors de la mise à jour du mot de passe :", error);
        }
      );
    }
  }
}
