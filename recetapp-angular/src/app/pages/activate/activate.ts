import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-activate',
  standalone: true,
  imports: [FormsModule, RouterLink],
  templateUrl: './activate.html',
  styleUrl: './activate.scss',
})
export class Activate implements OnInit {
  token = '';
  email = '';
  nombre = '';
  tempPassword = '';
  newPassword = '';
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
    this.email = this.route.snapshot.queryParamMap.get('email') || '';
    if (!this.token || !this.email) {
      this.errorMessage = 'Enlace de activación no válido.';
    }
  }

  handleActivate() {
    if (!this.token || !this.email) {
      this.errorMessage = 'Enlace de activación no válido.';
      return;
    }
    if (!this.nombre.trim()) {
      this.errorMessage = 'Escribe tu nombre.';
      return;
    }
    if (!this.tempPassword) {
      this.errorMessage = 'Introduce la contraseña temporal del correo.';
      return;
    }
    if (!this.newPassword || !this.confirmPassword) {
      this.errorMessage = 'Introduce y repite la nueva contraseña.';
      return;
    }
    if (this.newPassword !== this.confirmPassword) {
      this.errorMessage = 'Las contraseñas no coinciden.';
      return;
    }
    if (this.newPassword.length < 6) {
      this.errorMessage = 'La contraseña debe tener al menos 6 caracteres.';
      return;
    }

    this.errorMessage = '';

    this.api
      .activateAccount({
        token: this.token,
        email: this.email,
        nombre: this.nombre.trim(),
        temp_password: this.tempPassword,
        new_password: this.newPassword,
      })
      .subscribe({
        next: (res) => {
          this.api.setToken(res.token);
          this.router.navigate(['/home']);
        },
        error: (err) => (this.errorMessage = err.error?.error || 'Error al activar la cuenta.'),
      });
  }
}