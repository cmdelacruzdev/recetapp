import { Component, Input, Output, EventEmitter } from '@angular/core';

@Component({
  selector: 'app-dashboard-tab',
  standalone: true,
  templateUrl: './dashboard-tab.html',
  styleUrls: ['./dashboard-tab.scss'],
})
export class DashboardTab {
  @Input() totalRecipes = 0;
  @Input() ownRecipes = 0;
  @Input() totalIngredients = 0;
  @Input() ownIngredients = 0;
  @Input() shoppingCount = 0;
  @Input() dynamicTip = '';
  @Output() switchTab = new EventEmitter<string>();
  @Output() goToOwnRecipes = new EventEmitter<void>();
  @Output() goToOwnIngredients = new EventEmitter<void>();
}
