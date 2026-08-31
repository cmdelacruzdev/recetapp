import { Component, Input, Output, EventEmitter } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { TitleCasePipe } from '@angular/common';

@Component({
  selector: 'app-planning-tab',
  standalone: true,
  imports: [FormsModule, TitleCasePipe],
  templateUrl: './planning-tab.html',
  styleUrls: ['./planning-tab.scss'],
})
export class PlanningTab {
  @Input() calendarDays: any[] = [];
  @Input() calendarPadding: number[] = [];
  @Input() currentMonthName = '';
  @Input() weekDays: string[] = [];
  @Input() meals: string[] = [];
  @Input() planning: any = {};
  @Input() recipes: any[] = [];
  @Input() selectedCalendarDay: any = null;
  @Input() mealSearchText: { [key: string]: string } = {};
  @Input() mealSearchResults: { [key: string]: any[] } = {};
  @Input() isCurrentMonth = false;
  @Input() canGoNext = true;
  @Input() canGoPrev = true;
  @Input() planningCleanupInfo: string | null = null;

  @Output() prevMonth = new EventEmitter<void>();
  @Output() nextMonth = new EventEmitter<void>();
  @Output() goToCurrentMonth = new EventEmitter<void>();
  @Output() selectDay = new EventEmitter<any>();
  @Output() searchMeal = new EventEmitter<{ day: string; meal: string; text: string }>();
  @Output() selectMeal = new EventEmitter<{ day: string; meal: string; recipe: any }>();
  @Output() clearMeal = new EventEmitter<{ day: string; meal: string }>();
  @Output() addToShopping = new EventEmitter<string>();

  getRecipeName(id: string): string {
    return this.recipes?.find((r) => r.id === id)?.nombre || '';
  }

  hasPlanning(dateString: string): boolean {
    const p = this.planning[dateString];
    return !!(p && (p.desayuno || p.comida || p.cena));
  }

  isPastDay(dateString: string): boolean {
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getDate().toString().padStart(2, '0')}`;
    return dateString < todayStr;
  }

  getMealResults(key: string): any[] {
    return this.mealSearchResults[key] || [];
  }
}
