import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrl: './login.component.css',
})
export class LoginComponent {
  model: any = {};
  errorMessage: string | null = null;

  constructor(private authService: AuthService, private router: Router) {}

  onSubmit() {
    this.errorMessage = null;

    this.authService.login(this.model.email, this.model.password).subscribe({
      next: (response) => {
        this.authService.currentAuthUser.subscribe((user) => {
          console.log(user);
          if (user.roles.includes('ROLE_SPORTIF')) {
            this.router.navigate(['/']).then(() => {
              window.location.reload();
            });
          } else {
            this.errorMessage = 'Identifiants incorrects';
          }
        });
      },
      error: (err) => {
        if (err.status === 401) {
          this.errorMessage = err.error.message || 'Identifiants incorrects';
        } else {
          this.errorMessage = "Une erreur inattendue s'est produite";
        }
      },
    });
  }
}
