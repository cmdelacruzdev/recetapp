import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const desktopGuard: CanActivateFn = () => {
  const router = inject(Router);

  if (typeof window !== 'undefined' && window.innerWidth >= 768) {
    router.navigate(['/']);
    return false;
  }

  return true;
};
