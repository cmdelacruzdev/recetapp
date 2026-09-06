import { Component, Input, Output, EventEmitter } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ImgSrcPipe } from '../../../pipes/img-src.pipe';

@Component({
  selector: 'app-recipes-tab',
  standalone: true,
  imports: [FormsModule, ImgSrcPipe],
  templateUrl: './recipes-tab.html',
  styleUrls: ['./recipes-tab.scss'],
})
export class RecipesTab {
  @Input() recipes: any[] = [];
  @Input() showOwnOnly = false;
  @Output() openRecipe = new EventEmitter<{ id: string | null; viewMode: boolean }>();
  @Output() addToShopping = new EventEmitter<string>();
  @Output() showOwnOnlyChange = new EventEmitter<boolean>();

  searchText = '';

  get filtered(): any[] {
    let list = this.recipes;
    if (this.showOwnOnly) {
      list = list.filter((r) => !r.isPredefined);
    }
    if (!this.searchText.trim()) return list;
    const term = this.searchText.toLowerCase();
    return list.filter((r) => r.nombre.toLowerCase().includes(term));
  }

  get ownCount(): number {
    return this.recipes.filter((r) => !r.isPredefined).length;
  }

  toggleOwn() {
    this.showOwnOnly = !this.showOwnOnly;
    this.showOwnOnlyChange.emit(this.showOwnOnly);
  }
}
