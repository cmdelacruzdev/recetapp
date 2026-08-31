import { Component, OnInit, OnDestroy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Subscription } from 'rxjs';
import { filter } from 'rxjs/operators';
import { ToastService, ToastMessage } from '../../services/toast.service';

@Component({
  selector: 'app-toast',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './toast.html',
  styleUrls: ['./toast.scss'],
})
export class ToastComponent implements OnInit, OnDestroy {
  toasts: ToastMessage[] = [];
  private sub!: Subscription;

  constructor(private toast: ToastService, private cdr: ChangeDetectorRef) {}

  ngOnInit() {
    this.sub = this.toast.messages$.subscribe((msg) => {
      if (!msg.text) {
        this.toasts = this.toasts.filter((t) => t.id !== msg.id);
      } else if (!this.toasts.find((t) => t.id === msg.id)) {
        this.toasts.push(msg);
      }
      this.cdr.detectChanges();
    });
  }

  ngOnDestroy() { this.sub?.unsubscribe(); }

  dismiss(id: number) { this.toast.dismiss(id); }

  icon(type: string): string {
    switch (type) {
      case 'success': return 'bi-check-circle-fill';
      case 'error': return 'bi-x-circle-fill';
      case 'warning': return 'bi-exclamation-triangle-fill';
      default: return 'bi-info-circle-fill';
    }
  }
}
