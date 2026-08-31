import { Component, Input, Output, EventEmitter } from '@angular/core';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-ingredients-tab',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './ingredients-tab.html',
  styleUrls: ['./ingredients-tab.scss'],
})
export class IngredientsTab {
  @Input() ingredients: any[] = [];
  @Input() showOwnOnly = false;
  @Output() openIngredient = new EventEmitter<{ id: string; name: string }>();
  @Output() deleteIngredient = new EventEmitter<string>();
  @Output() showOwnOnlyChange = new EventEmitter<boolean>();

  searchText = '';

  get filtered(): any[] {
    let list = this.ingredients;
    if (this.showOwnOnly) {
      list = list.filter((i) => !i.isPredefined);
    }
    if (!this.searchText.trim()) return list;
    const term = this.searchText.toLowerCase();
    return list.filter((i) => i.name.toLowerCase().includes(term));
  }

  get ownCount(): number {
    return this.ingredients.filter((i) => !i.isPredefined).length;
  }

  toggleOwn() {
    this.showOwnOnly = !this.showOwnOnly;
    this.showOwnOnlyChange.emit(this.showOwnOnly);
  }
}
