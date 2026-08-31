import { ApplicationConfig, provideBrowserGlobalErrorListeners, isDevMode, inject } from '@angular/core';
import { provideRouter, Router } from '@angular/router';
import { routes } from './app.routes';
import { provideServiceWorker } from '@angular/service-worker';
import { catchError, throwError } from 'rxjs';
import {
  provideHttpClient,
  withInterceptors,
  HttpInterceptorFn,
  HttpErrorResponse,
} from '@angular/common/http';

const PUBLIC_AUTH_URLS = ['/api/login', '/api/register', '/api/forgot-password', '/api/reset-password', '/api/activate-account'];

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);
  const token = localStorage.getItem('auth_token');
  if (token) {
    req = req.clone({
      setHeaders: { Authorization: `Bearer ${token}` },
    });
  }
  return next(req).pipe(
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401 && !PUBLIC_AUTH_URLS.some((u) => req.url.includes(u)) && router.url !== '/login') {
        localStorage.removeItem('auth_token');
        router.navigateByUrl('/login');
      }
      return throwError(() => error);
    }),
  );
};

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideRouter(routes),
    provideServiceWorker('ngsw-worker.js', {
      enabled: !isDevMode(),
      registrationStrategy: 'registerWhenStable:30000',
    }),
    provideHttpClient(
      withInterceptors([authInterceptor]),
    ),
  ],
};