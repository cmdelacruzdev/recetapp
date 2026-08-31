import { Injectable, signal, inject, PLATFORM_ID } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';

@Injectable({
  providedIn: 'root',
})
export class PwaInstallService {
  private deferredPrompt: any = null;
  private readonly platformId = inject(PLATFORM_ID);

  isInstalled = signal<boolean>(false);
  isIOS = signal<boolean>(false);
  showIOSModal = signal<boolean>(false);
  canInstall = signal<boolean>(false);

  constructor() {
    if (!isPlatformBrowser(this.platformId)) {
      return;
    }

    this.checkIfInstalled();
    this.checkIfIOS();

    if (this.isIOS() && !this.isInstalled()) {
      this.canInstall.set(true);
    }

    if ((window as any).gpDeferredPrompt) {
      this.deferredPrompt = (window as any).gpDeferredPrompt;
      this.canInstall.set(true);
    }

    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      this.deferredPrompt = e;
      this.canInstall.set(true);
    });

    window.addEventListener('appinstalled', () => {
      this.isInstalled.set(true);
      this.canInstall.set(false);
      this.deferredPrompt = null;
      this.showIOSModal.set(false);
    });
  }

  private checkIfInstalled() {
    const isStandalone =
      window.matchMedia('(display-mode: standalone)').matches ||
      (window.navigator as any).standalone === true;
    this.isInstalled.set(isStandalone);
  }

  private checkIfIOS() {
    const userAgent = window.navigator.userAgent.toLowerCase();
    const isIosDevice =
      /iphone|ipad|ipod/.test(userAgent) ||
      (userAgent.includes('mac') && 'ontouchend' in document);
    this.isIOS.set(isIosDevice);
  }

  async installPwa() {
    if (this.isIOS()) {
      this.showIOSModal.set(true);
      return;
    }

    if (this.deferredPrompt) {
      this.deferredPrompt.prompt();
      const { outcome } = await this.deferredPrompt.userChoice;
      if (outcome === 'accepted') {
        this.isInstalled.set(true);
        this.canInstall.set(false);
      }
      this.deferredPrompt = null;
    } else {
      alert(
        'Para instalar la aplicación, busca la opción "Instalar aplicación" en el menú de tu navegador o barra de direcciones.',
      );
    }
  }

  closeModal() {
    this.showIOSModal.set(false);
  }
}
