import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { ApiService } from '../services/api.service';
import { HttpErrorResponse } from '@angular/common/http';
import { catchError, map, of } from 'rxjs';

export const authGuard: CanActivateFn = () => {
  const router = inject(Router);
  const api = inject(ApiService);

  if (!api.isAuthenticated()) {
    return router.createUrlTree(['/login']);
  }

  return api.me().pipe(
    map(() => true),
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401) {
        api.clearToken();
        return of(router.createUrlTree(['/login']));
      }
      return of(true);
    }),
  );
};
