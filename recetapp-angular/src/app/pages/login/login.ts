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
    const saved = localStorage.getItem('remembered_credentials');
    if (saved) {
      try {
        const creds = JSON.parse(saved);
        this.username = creds.username || '';
        this.password = creds.password || '';
        this.rememberMe = true;
      } catch {
        localStorage.removeItem('remembered_credentials');
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
        this.api.setToken(res.token);
        if (this.rememberMe) {
          localStorage.setItem('remembered_credentials', JSON.stringify({
            username: this.username,
            password: this.password,
          }));
        } else {
          localStorage.removeItem('remembered_credentials');
        }
        this.router.navigate(['/home']);
      },
      error: () => this.toast.error('Credenciales incorrectas.'),
    });
  }
}
