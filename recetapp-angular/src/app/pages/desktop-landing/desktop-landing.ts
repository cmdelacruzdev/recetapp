import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-desktop-landing',
  standalone: true,
  imports: [],
  templateUrl: './desktop-landing.html',
  styleUrls: ['./desktop-landing.scss'],
})
export class DesktopLanding implements OnInit {
  constructor(
    private router: Router,
    private api: ApiService,
  ) {}

  ngOnInit() {
    if (typeof window !== 'undefined' && window.innerWidth < 768) {
      this.router.navigate([this.api.isAuthenticated() ? '/home' : '/login']);
    }
  }
}
