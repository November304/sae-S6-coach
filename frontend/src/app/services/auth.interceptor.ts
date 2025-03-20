import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler } from '@angular/common/http';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  private localStorageToken = 'currentToken'; // ✅ Clé du token dans localStorage

  intercept(req: HttpRequest<any>, next: HttpHandler) {
    const currentToken = localStorage.getItem(this.localStorageToken); // ✅ Récupère directement le token

    console.log("🔍 Intercepteur - Token utilisé :", currentToken);

    if (currentToken) {
      req = req.clone({
        setHeaders: {
          Authorization: `Bearer ${currentToken}`
        }
      });
    }
    return next.handle(req);
  }
}
