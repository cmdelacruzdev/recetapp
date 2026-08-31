import { Component } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [FormsModule, RouterLink],
  templateUrl: './register.html',
  styleUrl: './register.scss',
})
export class Register {
  nombre = '';
  usuario = '';
  password = '';
  nombreCasa = '';
  errorMessage = '';

  constructor(
    private api: ApiService,
    private router: Router,
  ) {}

  handleRegister() {
    if (!this.nombre || !this.usuario || !this.password || !this.nombreCasa) {
      this.errorMessage = 'Todos los campos son requeridos.';
      return;
    }

    this.api
      .register({
        nombre: this.nombre,
        usuario: this.usuario,
        password: this.password,
        nombre_casa: this.nombreCasa,
      })
      .subscribe({
        next: (res) => {
          this.api.setToken(res.token);
          this.router.navigate(['/home']);
        },
        error: (err) => (this.errorMessage = err.error?.error || 'Error desconocido.'),
      });
  }
}
