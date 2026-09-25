import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { ToastService } from '../../services/toast.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule, RouterLink],
  templateUrl: './login.html',
  styleUrl: './login.scss',
})
export class Login implements OnInit {
  username = '';
  password = '';
  rememberMe = false;

  constructor(
    private api: ApiService,
    private router: Router,
    private route: ActivatedRoute,
    private toast: ToastService,
  ) {}

  ngOnInit() {
    if (typeof window !== 'undefined') {
      const savedUsername = window.localStorage.getItem('remembered_username');
      if (savedUsername) {
        this.username = savedUsername;
        this.rememberMe = true;
      }

      const legacyCredentials = window.localStorage.getItem('remembered_credentials');
      if (legacyCredentials) {
        try {
          const creds = JSON.parse(legacyCredentials);
          this.username = creds.username || this.username;
          this.rememberMe = true;
        } catch {
          window.localStorage.removeItem('remembered_credentials');
        }
        window.localStorage.removeItem('remembered_credentials');
      }

      if (this.api.isAuthenticated()) {
        this.router.navigate(['/home']);
        return;
      }
    }

    const error = this.route.snapshot.queryParamMap.get('error');
    if (error === 'expired_token') {
      this.toast.warning('El enlace de activación ha expirado. Pide al admin de la casa que reenvíe la invitación.');
    } else if (error === 'invalid_token') {
      this.toast.error('El enlace de activación no es válido.');
    }
  }

  handleLogin() {
    this.api.login({ username: this.username, password: this.password }).subscribe({
      next: (res) => {
        this.api.setToken(res.token, this.rememberMe);
        if (typeof window !== 'undefined') {
          if (this.rememberMe) {
            window.localStorage.setItem('remembered_username', this.username);
          } else {
            window.localStorage.removeItem('remembered_username');
          }
          window.localStorage.removeItem('remembered_credentials');
        }
        this.router.navigate(['/home']);
      },
      error: () => this.toast.error('Credenciales incorrectas.'),
    });
  }
}
