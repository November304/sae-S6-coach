import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { HomeComponent } from './home/home.component';
import { CoachesListComponent } from './coaches-list/coaches-list.component';
import { LoginComponent } from './login/login.component';
import { PlanningComponent } from './planning/planning.component';
import { RegisterComponent } from './register/register.component';
import { ProfileComponent } from './profile/profile.component';
import { EspacePersoComponent } from './espace-perso/espace-perso.component';
import { EditProfileComponent } from './edit-profile/edit-profile.component';
import { EditPasswordComponent } from './edit-password/edit-password.component';
import { BilanComponent } from './bilan/bilan.component';

const routes: Routes = [
  { path: '', component: HomeComponent },
  { path: 'coaches', component: CoachesListComponent },
  { path: 'planning', component: PlanningComponent },
  { path: 'login', component: LoginComponent },
  { path: 'register', component: RegisterComponent },
  { path: 'profile', component: ProfileComponent },
  { path: 'espace-perso', component: EspacePersoComponent },
  { path: 'edit-profile', component: EditProfileComponent },
  { path: 'edit-password', component: EditPasswordComponent },
  { path: 'bilan', component: BilanComponent },
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
})
export class AppRoutingModule {}
