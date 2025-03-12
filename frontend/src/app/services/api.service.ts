import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Coach } from '../models/coach';
import { Observable } from 'rxjs';
import { Sportif } from '../models/sportif';
import { Exercice } from '../models/exercice';
import { Seance } from '../models/seance';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private apiUrl = 'https://127.0.0.1:8008/api';

  constructor(private http: HttpClient) { }

  // ------------------- COACH ------------------- //
  getCoachsList(): Observable<Coach[]> {
    return this.http.get<Coach[]>(`${this.apiUrl}/coachs`);
  }

  getCoach(id: number): Observable<Coach> {
    return this.http.get<Coach>(`${this.apiUrl}/coachs/${id}`);
  }

  createCoach(coach: Coach): Observable<Coach> {
    return this.http.post<Coach>(`${this.apiUrl}/coachs`, coach);
  }

  updateCoach(coach: Coach): Observable<Coach> {
    return this.http.put<Coach>(`${this.apiUrl}/coachs/${coach.id}`, coach);
  }

  deleteCoach(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/coachs/${id}`);
  }

  // ------------------- SPORTIF ------------------- //
  getSportList(): Observable<Sportif[]> {
    return this.http.get<Sportif[]>(`${this.apiUrl}/sports`);
  }

  getSport(id: number): Observable<Sportif> {
    return this.http.get<Sportif>(`${this.apiUrl}/sports/${id}`);
  }

  createSport(sport: Sportif): Observable<Sportif> {
    return this.http.post<Sportif>(`${this.apiUrl}/sports`, sport);
  }

  updateSport(sport: Sportif): Observable<Sportif> {
    return this.http.put<Sportif>(`${this.apiUrl}/sports/${sport.id}`, sport);
  }

  deleteSport(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/sports/${id}`);
  }

  // ------------------- EXERCICE ------------------- //
  getExerciceList(): Observable<Exercice[]> {
    return this.http.get<Exercice[]>(`${this.apiUrl}/exercices`);
  }

  getExercice(id: number): Observable<Exercice> {
    return this.http.get<Exercice>(`${this.apiUrl}/exercices/${id}`);
  }

  createExercice(exercice: Exercice): Observable<Exercice> {
    return this.http.post<Exercice>(`${this.apiUrl}/exercices`, exercice);
  }

  updateExercice(exercice: Exercice): Observable<Exercice> {
    return this.http.put<Exercice>(`${this.apiUrl}/exercices/${exercice.id}`, exercice);
  }

  deleteExercice(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/exercices/${id}`);
  }

  // ------------------- SEANCE ------------------- //
  getSeanceList(): Observable<Seance[]> {
    return this.http.get<Seance[]>(`${this.apiUrl}/seances`);
  }

  getSeance(id: number): Observable<Seance> {
    return this.http.get<Seance>(`${this.apiUrl}/seances/${id}`);
  }

  createSeance(seance: Seance): Observable<Seance> {
    return this.http.post<Seance>(`${this.apiUrl}/seances`, seance);
  }

  updateSeance(seance: Seance): Observable<Seance> {
    return this.http.put<Seance>(`${this.apiUrl}/seances/${seance.id}`, seance);
  }

  deleteSeance(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/seances/${id}`);
  }
}
