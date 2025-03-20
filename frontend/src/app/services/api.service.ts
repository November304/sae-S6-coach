import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Coach } from '../models/coach';
import { Observable } from 'rxjs';
import { Sportif } from '../models/sportif';
import { Seance } from '../models/seance';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private apiUrl = 'https://127.0.0.1:8008/api';

  constructor(private http: HttpClient) { }

  // ------------------- COACH ------------------- //
  getCoachsListPublic(): Observable<Coach[]> {
    return this.http.get<Coach[]>(`${this.apiUrl}/public/coaches`);
  }

  getCoachsList(): Observable<Coach[]> {
    return this.http.get<Coach[]>(`${this.apiUrl}/coaches`);
  }

  // ------------------- SPORTIF ------------------- //
  getSportifMe(): Observable<Sportif> {
    return this.http.get<Sportif>(`${this.apiUrl}/sportifs/me`);
  }

  getMySeances(): Observable<Sportif> {
    return this.http.get<Sportif>(`${this.apiUrl}/sportifs/seances`);
  }

  createSportif(body: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/public/sportifs`, body);
  }

  updateSelf(body: any): Observable<any> {
    return this.http.put<Sportif>(`${this.apiUrl}/sportifs`, body);
  }

  updateSelfPwd(body: any): Observable<any> {
    return this.http.put<Sportif>(`${this.apiUrl}/sportifs/pwd`, body);
  }

  deleteAccount(): Observable<any> {
    return this.http.delete(`${this.apiUrl}/sportifs`);
  }

  // ------------------- SEANCE ------------------- //
  getSeanceListPublic(): Observable<Seance[]> {
    return this.http.get<Seance[]>(`${this.apiUrl}/public/seances`);
  }

  getSeanceList(): Observable<Seance[]> {
    return this.http.get<Seance[]>(`${this.apiUrl}/seances`);
  }

  ReserveSeance(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/seances/resa/${id}`, {});
  }

  UnreserveSeance(id: number): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}/seances/resa/${id}`);
  }
}
