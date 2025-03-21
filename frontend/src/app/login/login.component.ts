import { Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrl: './login.component.css',
})
export class LoginComponent {
  model: any = {};
  errorMessage: string | null = null;

  constructor(private authService: AuthService, private router: Router, public route: ActivatedRoute) {}

  onSubmit() {
    this.errorMessage = null;

    this.authService.login(this.model.email, this.model.password).subscribe({
      next: (response) => {
        this.authService.currentAuthUser.subscribe((user) => {
          if (user.roles.includes('ROLE_SPORTIF')) {
            this.router.navigate(['/']).then(() => {
              window.location.reload();
            });
          } else if (user.roles.includes('ROLE_RESPONSABLE') || user.roles.includes('ROLE_COACH')) {
            this.authService.logout();
            window.location.href = '/admin';
          } else {
            this.authService.logout();
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
