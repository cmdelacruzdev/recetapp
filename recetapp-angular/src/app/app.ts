import { Component, signal, afterNextRender, OnDestroy, inject } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { ToastComponent } from './components/toast/toast';
import { ConfirmDialogComponent } from './components/confirm-dialog/confirm-dialog';
import { UpdateService } from './core/services/update.service';
import { PwaInstallService } from './core/services/pwa-install.service';
import { APP_VERSION } from './core/config/version.config';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, ToastComponent, ConfirmDialogComponent],
  templateUrl: './app.html',
  styleUrl: './app.scss',
})
export class App implements OnDestroy {
  protected readonly title = signal('recetapp');
  isMobile = signal(true);
  private resizeObserver: ResizeObserver | null = null;
  public pwaInstallService = inject(PwaInstallService);
  private readonly updateService = inject(UpdateService);

  constructor() {
    afterNextRender(() => {
      console.info(`RecetAPP ${APP_VERSION}`);

      this.applyStandaloneClass();

      const mq = window.matchMedia('(max-width: 767.98px)');
      this.isMobile.set(mq.matches);

      const handler = () => this.isMobile.set(mq.matches);
      mq.addEventListener('change', handler);

      this.resizeObserver = new ResizeObserver(() => {
        this.isMobile.set(window.innerWidth < 768);
      });
      this.resizeObserver.observe(document.body);
    });
  }

  private applyStandaloneClass() {
    const isStandalone =
      window.matchMedia('(display-mode: standalone)').matches ||
      (window.navigator as any).standalone === true;
    document.body.classList.toggle('pwa-standalone', isStandalone);
  }

  ngOnDestroy() {
    this.resizeObserver?.disconnect();
  }
}
