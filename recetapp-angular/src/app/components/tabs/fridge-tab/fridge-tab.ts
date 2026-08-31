import { Component, Input, Output, EventEmitter } from '@angular/core';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-fridge-tab',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './fridge-tab.html',
  styleUrls: ['./fridge-tab.scss'],
})
export class FridgeTab {
  @Input() ingredients: any[] = [];
  @Input() fridgeSelected: string[] = [];
  @Input() matchedRecipes: any[] = [];
  @Output() searchTextChange = new EventEmitter<string>();
  @Output() addIngredient = new EventEmitter<string>();
  @Output() removeIngredient = new EventEmitter<string>();
  @Output() viewRecipe = new EventEmitter<string>();

  searchText = '';

  get filtered(): any[] {
    if (!this.searchText.trim()) return [];
    const term = this.searchText.toLowerCase();
    return this.ingredients.filter(
      (ing) => ing.name.toLowerCase().includes(term) && !this.fridgeSelected.includes(ing.id),
    );
  }

  getIngredientName(id: string): string {
    return this.ingredients?.find((i) => i.id === id)?.name || 'Desconocido';
  }

  onSearch() {
    this.searchTextChange.emit(this.searchText);
  }

  onAdd(id: string) {
    if (!this.fridgeSelected.includes(id)) {
      this.addIngredient.emit(id);
    }
    this.searchText = '';
  }

  onRemove(id: string) {
    this.removeIngredient.emit(id);
  }
}
