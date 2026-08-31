import { Component } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-forgot-password',
  standalone: true,
  imports: [FormsModule, RouterLink],
  templateUrl: './forgot-password.html',
  styleUrl: './forgot-password.scss',
})
export class ForgotPassword {
  email = '';
  submitted = false;
  errorMessage = '';

  constructor(private api: ApiService, private router: Router) {}

  handleSubmit() {
    if (!this.email.trim()) {
      this.errorMessage = 'Introduce tu email.';
      return;
    }

    this.api.forgotPassword({ email: this.email }).subscribe({
      next: () => {
        this.submitted = true;
        this.errorMessage = '';
      },
      error: (err) => (this.errorMessage = err.error?.error || 'Error al enviar el email.'),
    });
  }
}
