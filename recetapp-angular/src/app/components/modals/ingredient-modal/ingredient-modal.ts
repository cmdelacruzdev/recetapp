import { Component, Input, Output, EventEmitter, ElementRef, AfterViewInit, OnDestroy } from '@angular/core';
import { FormsModule } from '@angular/forms';

declare var bootstrap: any;

@Component({
  selector: 'app-ingredient-modal',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './ingredient-modal.html',
  styleUrls: ['./ingredient-modal.scss'],
})
export class IngredientModal implements AfterViewInit, OnDestroy {
  @Input() ingredient = { id: '', name: '' };
  @Output() save = new EventEmitter<{ id: string; name: string }>();
  @Output() close = new EventEmitter<void>();

  localIngredient = { id: '', name: '' };
  private bsModal: any;

  constructor(private el: ElementRef) {}

  ngAfterViewInit() {
    if (typeof bootstrap === 'undefined') return;
    const modalEl = this.el.nativeElement.querySelector('#ingredientModal');
    this.bsModal = new bootstrap.Modal(modalEl, { backdrop: false });
    modalEl.addEventListener('hide.bs.modal', () => {
      if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
      }
    });
    modalEl.addEventListener('hidden.bs.modal', () => {
      this.close.emit();
    });
  }

  ngOnDestroy() {
    this.bsModal?.dispose();
  }

  open(ingredient: { id: string; name: string }) {
    this.localIngredient = { ...ingredient };
    this.bsModal?.show();
  }

  onSave() {
    this.save.emit(this.localIngredient);
  }

  hide() {
    this.bsModal?.hide();
  }
}
