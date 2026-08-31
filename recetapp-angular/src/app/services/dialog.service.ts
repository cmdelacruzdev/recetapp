import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

export interface ConfirmDialog {
  id: number;
  title: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  inputType?: 'select';
  selectOptions?: string[];
  resolve: (result: any) => void;
}

@Injectable({ providedIn: 'root' })
export class DialogService {
  private counter = 0;
  dialog$ = new Subject<ConfirmDialog>();

  confirm(message: string, title = 'Confirmar'): Promise<boolean> {
    return new Promise((resolve) => {
      this.dialog$.next({
        id: ++this.counter,
        title,
        message,
        confirmText: 'Aceptar',
        cancelText: 'Cancelar',
        resolve,
      });
    });
  }

  prompt(message: string, options: string[], title = 'Seleccionar'): Promise<string | null> {
    return new Promise((resolve) => {
      this.dialog$.next({
        id: ++this.counter,
        title,
        message,
        confirmText: 'Aceptar',
        cancelText: 'Cancelar',
        inputType: 'select',
        selectOptions: options,
        resolve,
      });
    });
  }
}
