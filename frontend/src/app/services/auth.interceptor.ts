import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler } from '@angular/common/http';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  private localStorageToken = 'currentToken';

  intercept(req: HttpRequest<any>, next: HttpHandler) {
    const currentToken = localStorage.getItem(this.localStorageToken);

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
