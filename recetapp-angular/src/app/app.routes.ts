import { Routes } from '@angular/router';
import { Login } from './pages/login/login';
import { Register } from './pages/register/register';
import { Activate } from './pages/activate/activate';
import { Home } from './pages/home/home';
import { ForgotPassword } from './pages/forgot-password/forgot-password';
import { ResetPassword } from './pages/reset-password/reset-password';
import { DesktopLanding } from './pages/desktop-landing/desktop-landing';
import { authGuard } from './guards/auth.guard';
import { desktopGuard } from './guards/desktop.guard';

export const routes: Routes = [
  { path: '', component: DesktopLanding },
  { path: 'desktop', component: DesktopLanding },
  { path: 'login', component: Login, canActivate: [desktopGuard] },
  { path: 'register', component: Register, canActivate: [desktopGuard] },
  { path: 'activate', component: Activate },
  { path: 'forgot-password', component: ForgotPassword, canActivate: [desktopGuard] },
  { path: 'reset-password', component: ResetPassword, canActivate: [desktopGuard] },
  { path: 'home', component: Home, canActivate: [desktopGuard, authGuard] },
  { path: '**', redirectTo: '' }
];
