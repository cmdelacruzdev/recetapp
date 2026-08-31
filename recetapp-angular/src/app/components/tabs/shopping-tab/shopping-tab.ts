import { Component, Input, Output, EventEmitter } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgClass } from '@angular/common';

@Component({
  selector: 'app-shopping-tab',
  standalone: true,
  imports: [FormsModule, NgClass],
  templateUrl: './shopping-tab.html',
  styleUrls: ['./shopping-tab.scss'],
})
export class ShoppingTab {
  @Input() items: any[] = [];
  @Output() addManual = new EventEmitter<string>();
  @Output() toggle = new EventEmitter<number>();
  @Output() remove = new EventEmitter<number>();
  @Output() clear = new EventEmitter<void>();

  manualItem = '';

  onAdd() {
    if (!this.manualItem.trim()) return;
    this.addManual.emit(this.manualItem.trim());
    this.manualItem = '';
  }
}
