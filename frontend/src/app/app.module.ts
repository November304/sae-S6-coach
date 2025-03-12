import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { FormsModule } from '@angular/forms';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { HomeComponent } from './home/home.component';
import { authInterceptor } from './services/auth.interceptor';
import { CoachesListComponent } from './coaches-list/coaches-list.component';
import { SeancesListComponent } from './seances-list/seances-list.component';

@NgModule({
  declarations: [
    AppComponent,
    HomeComponent,
    CoachesListComponent,
    SeancesListComponent,
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    FormsModule,
  ],
  providers: [provideHttpClient(withInterceptors([authInterceptor]))],
  bootstrap: [AppComponent]
})
export class AppModule { }