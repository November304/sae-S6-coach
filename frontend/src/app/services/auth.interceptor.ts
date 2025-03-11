import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { AuthService } from './auth.service';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const authService = inject(AuthService);

  if (!req.headers.has('skip-token')) {
    const currentToken = authService.currentTokenValue;
    if (currentToken) {
      req = req.clone({
        setHeaders: {
          Authorization: `Bearer ${currentToken}`
        }
      });
    }
  } else {
    req = req.clone({
      headers: req.headers.delete('skip-token')
    });
  }

  return next(req);
};