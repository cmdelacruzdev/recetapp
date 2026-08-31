import { Component, Input, Output, EventEmitter } from '@angular/core';
import { NgClass } from '@angular/common';

@Component({
  selector: 'app-bottom-navbar',
  standalone: true,
  imports: [NgClass],
  templateUrl: './bottom-navbar.html',
  styleUrls: ['./bottom-navbar.scss'],
})
export class BottomNavbar {
  @Input() activeTab = '';
  @Output() tabChange = new EventEmitter<string>();

  tabs = [
    { key: 'dashboard', label: 'Inicio', icon: 'bi-house-door' },
    { key: 'recipes', label: 'Recetas', icon: 'bi-journal-richtext' },
    { key: 'planning', label: 'Planning', icon: 'bi-calendar-week' },
    { key: 'shopping', label: 'Compra', icon: 'bi-cart3' },
    { key: 'fridge', label: 'Nevera', icon: 'bi-basket' },
  ];

  onTab(key: string) {
    this.tabChange.emit(key);
  }
}
