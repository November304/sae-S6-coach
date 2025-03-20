import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, map } from 'rxjs';

export class AuthUser {
  constructor(
    public email: string = "",
    public roles: string[] = []
  ) {}

  isAdmin(): boolean {
    return this.roles.includes("ROLE_ADMIN");
  }

  isLogged(): boolean {
    return this.email.length > 0;
  }
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrlLogin    = 'https://127.0.0.1:8008/api/login';
  private apiUrlUserInfo = 'https://127.0.0.1:8008/api/user/me';

  private localStorageToken = 'currentToken';

  private currentTokenSubject: BehaviorSubject<string | null>;
  public currentToken: Observable<string | null>;
  public get currentTokenValue(): string | null { 
    return this.currentTokenSubject.value; 
  }

  private currentAuthUserSubject: BehaviorSubject<AuthUser>;
  public currentAuthUser: Observable<AuthUser>;
  public get currentAuthUserValue(): AuthUser { 
    return this.currentAuthUserSubject.value; 
  }

  constructor(private http: HttpClient) {
    const storedToken: string | null = localStorage.getItem(this.localStorageToken);

    console.log("🔍 Token récupéré depuis localStorage :", storedToken);

    this.currentTokenSubject = new BehaviorSubject<string | null>(storedToken);
    this.currentToken = this.currentTokenSubject.asObservable();

    this.currentAuthUserSubject = new BehaviorSubject(new AuthUser());
    this.currentAuthUser = this.currentAuthUserSubject.asObservable();

    if (storedToken) {
      this.currentTokenSubject.next(storedToken); // ✅ Force la mise à jour immédiate du token
      this.updateUserInfo(storedToken);
    }
  }

  private updateUserInfo(token: string | null) {
    if (!token) {
      this.clearSession();
      return;
    }

    console.log("🔄 Mise à jour des infos utilisateur avec token :", token);

    const headers = new HttpHeaders({ 'Authorization': `Bearer ${token}` });

    this.http.get<AuthUser>(this.apiUrlUserInfo, { headers }).subscribe({
      next: data => {
        if (data.email) {
          localStorage.setItem(this.localStorageToken, token); // ✅ Stocke immédiatement le token
          this.currentTokenSubject.next(token);
          this.currentAuthUserSubject.next(new AuthUser(data.email, data.roles));
          console.log("✅ Utilisateur rechargé :", data);
        } else {
          this.clearSession();
        }
      },
      error: err => {
        console.error("❌ Erreur lors de la récupération de l'utilisateur :", err);
        this.clearSession();
      }
    });
  }

  public login(email: string, password: string): Observable<boolean> {
    return this.http.post<any>(this.apiUrlLogin, { email, password }).pipe(
      map(response => {
        if (response.token) {
          localStorage.setItem(this.localStorageToken, response.token); // ✅ Sauvegarde immédiate du token
          this.updateUserInfo(response.token);
          return true;
        }
        return false;
      })
    );
  }

  public logout() {
    this.clearSession();
  }

  private clearSession() {
    localStorage.removeItem(this.localStorageToken);
    this.currentTokenSubject.next(null);
    this.currentAuthUserSubject.next(new AuthUser());
  }
}