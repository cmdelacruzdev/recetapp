import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

export interface ToastMessage {
  id: number;
  text: string;
  type: 'success' | 'error' | 'info' | 'warning';
}

@Injectable({ providedIn: 'root' })
export class ToastService {
  private counter = 0;
  messages$ = new Subject<ToastMessage>();

  show(text: string, type: ToastMessage['type'] = 'info', duration = 3500) {
    const msg: ToastMessage = { id: ++this.counter, text, type };
    this.messages$.next(msg);
    setTimeout(() => this.dismiss(msg.id), duration);
  }

  success(text: string) { this.show(text, 'success'); }
  error(text: string) { this.show(text, 'error', 5000); }
  info(text: string) { this.show(text, 'info'); }
  warning(text: string) { this.show(text, 'warning', 4500); }

  dismiss(id: number) {
    this.messages$.next({ id, text: '', type: 'info' });
  }
}
