import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { ApiService } from '../services/api.service';
import { catchError, map, of } from 'rxjs';

export const authGuard: CanActivateFn = (route, state) => {
  const router = inject(Router);
  const api = inject(ApiService);

  if (!api.isAuthenticated()) {
    router.navigate(['/login']);
    return false;
  }

  return api.me().pipe(
    map(() => true),
    catchError(() => {
      api.clearToken();
      router.navigate(['/login']);
      return of(false);
    }),
  );
};
