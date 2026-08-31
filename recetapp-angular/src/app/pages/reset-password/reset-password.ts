import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-reset-password',
  standalone: true,
  imports: [FormsModule, RouterLink],
  templateUrl: './reset-password.html',
  styleUrl: './reset-password.scss',
})
export class ResetPassword implements OnInit {
  token = '';
  password = '';
  confirmPassword = '';
  submitted = false;
  errorMessage = '';

  constructor(
    private api: ApiService,
    private router: Router,
    private route: ActivatedRoute,
  ) {}

  ngOnInit() {
    this.token = this.route.snapshot.queryParamMap.get('token') || '';
    if (!this.token) {
      this.errorMessage = 'Token no válido.';
    }
  }

  handleSubmit() {
    if (!this.password || !this.confirmPassword) {
      this.errorMessage = 'Introduce la nueva contraseña.';
      return;
    }

    if (this.password !== this.confirmPassword) {
      this.errorMessage = 'Las contraseñas no coinciden.';
      return;
    }

    if (this.password.length < 6) {
      this.errorMessage = 'La contraseña debe tener al menos 6 caracteres.';
      return;
    }

    this.api.resetPassword({ token: this.token, password: this.password }).subscribe({
      next: () => {
        this.submitted = true;
        this.errorMessage = '';
      },
      error: (err) => (this.errorMessage = err.error?.error || 'Error al restablecer la contraseña.'),
    });
  }
}
