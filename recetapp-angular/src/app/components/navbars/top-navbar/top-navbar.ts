import { Component, Input, Output, EventEmitter, inject } from '@angular/core';
import { PwaInstallService } from '../../../core/services/pwa-install.service';

@Component({
  selector: 'app-top-navbar',
  standalone: true,
  templateUrl: './top-navbar.html',
  styleUrls: ['./top-navbar.scss'],
})
export class TopNavbar {
  @Input() user: any = {};
  @Output() openProfile = new EventEmitter<void>();
  public pwaInstallService = inject(PwaInstallService);
}
