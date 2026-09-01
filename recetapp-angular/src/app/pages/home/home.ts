import { Component, OnInit, OnDestroy, ViewChild, ChangeDetectorRef } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { ToastService } from '../../services/toast.service';
import { DialogService } from '../../services/dialog.service';
import { TopNavbar } from '../../components/navbars/top-navbar/top-navbar';
import { BottomNavbar } from '../../components/navbars/bottom-navbar/bottom-navbar';
import { DashboardTab } from '../../components/tabs/dashboard-tab/dashboard-tab';
import { RecipesTab } from '../../components/tabs/recipes-tab/recipes-tab';
import { PlanningTab } from '../../components/tabs/planning-tab/planning-tab';
import { IngredientsTab } from '../../components/tabs/ingredients-tab/ingredients-tab';
import { ShoppingTab } from '../../components/tabs/shopping-tab/shopping-tab';
import { FridgeTab } from '../../components/tabs/fridge-tab/fridge-tab';
import { RecipeModal } from '../../components/modals/recipe-modal/recipe-modal';
import { IngredientModal } from '../../components/modals/ingredient-modal/ingredient-modal';
import { ProfileModal } from '../../components/modals/profile-modal/profile-modal';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [
    TopNavbar, BottomNavbar,
    DashboardTab, RecipesTab, PlanningTab, IngredientsTab, ShoppingTab, FridgeTab,
    RecipeModal, IngredientModal, ProfileModal,
  ],
  templateUrl: './home.html',
  styleUrls: ['./home.scss'],
})
export class Home implements OnInit, OnDestroy {
  @ViewChild('recipeModal') recipeModalRef!: RecipeModal;
  @ViewChild('ingredientModal') ingredientModalRef!: IngredientModal;
  @ViewChild('profileModal') profileModalRef!: ProfileModal;

  activeTab = 'dashboard';
  appData: any = {
    ingredients: [],
    recipes: [],
    planning: {},
    shopping: [],
    user: { username: '', nombre: '', foto: '', casa_id: '', nombre_casa: '', role: 'user' },
  };

  dynamicTip = '';
  adminStats: any = null;
  inviteEmail = '';
  editingProfile = { nombre: '', new_password: '', current_password: '', confirm_password: '', foto: '' };

  calendarYear = 0;
  calendarMonth = 0;
  calendarDays: any[] = [];
  calendarPadding: number[] = [];
  currentMonthName = '';
  meals = ['desayuno', 'comida', 'cena'];
  selectedCalendarDay: any = null;
  weekDays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

  mealSearchText: { [key: string]: string } = {};
  mealSearchResults: { [key: string]: any[] } = {};

  fridgeSelected: string[] = [];

  showOwnRecipes = false;
  showOwnIngredients = false;

  planningCleanupInfo: string | null = null;

  private tips: string[] = [];
  private tipIndex = 0;
  private tipInterval: any = null;
  private syncInterval: any = null;
  private onVisibilityChange: any = null;

  constructor(
    private api: ApiService,
    private router: Router,
    private toast: ToastService,
    private dialog: DialogService,
    private cdr: ChangeDetectorRef,
  ) {}

  ngOnInit() {
    const now = new Date();
    this.calendarYear = now.getFullYear();
    this.calendarMonth = now.getMonth();
    this.generateCalendar();
    this.loadData();
    this.loadAdminStats();
    this.loadTips();

    this.syncInterval = setInterval(() => this.loadData(), 15000);
    this.onVisibilityChange = () => {
      if (document.visibilityState === 'visible') {
        this.loadData();
        this.loadAdminStats();
      }
    };
    document.addEventListener('visibilitychange', this.onVisibilityChange);
  }

  ngOnDestroy() {
    if (this.tipInterval) {
      clearInterval(this.tipInterval);
    }
    if (this.syncInterval) {
      clearInterval(this.syncInterval);
    }
    if (this.onVisibilityChange) {
      document.removeEventListener('visibilitychange', this.onVisibilityChange);
    }
  }

  loadData() {
    this.api.getAllData().subscribe({
      next: (data) => {
        if (data) {
          this.appData = data;
          if (typeof this.appData.planning !== 'object' || Array.isArray(this.appData.planning)) {
            this.appData.planning = {};
          }
          if (data.planning_cleanup) {
            this.planningCleanupInfo = data.planning_cleanup;
          }
          this.cdr.detectChanges();
        }
      },
      error: () => this.toast.error('No se pudieron cargar los datos.'),
    });
  }

  loadAdminStats() {
    this.api.getAdminStats().subscribe({
      next: (stats) => { this.adminStats = stats; this.cdr.detectChanges(); },
      error: () => { this.adminStats = null; },
    });
  }

  loadTips() {
    this.api.getTips().subscribe({
      next: (res) => {
        this.tips = res.tips?.length ? res.tips : ['Gestiona tus recetas y planifica tu semana.'];
        this.tipIndex = Math.floor(Math.random() * this.tips.length);
        this.dynamicTip = this.tips[this.tipIndex];
        this.cdr.detectChanges();
        this.tipInterval = setInterval(() => {
          this.tipIndex = (this.tipIndex + 1) % this.tips.length;
          this.dynamicTip = this.tips[this.tipIndex];
          this.cdr.detectChanges();
        }, 30000);
      },
      error: () => {
        this.tips = ['Gestiona tus recetas y planifica tu semana.'];
        this.dynamicTip = this.tips[0];
      },
    });
  }

  switchTab(tab: string) {
    this.activeTab = tab;
    if (tab === 'dashboard') {
      this.loadTips();
    }
    this.loadData();
  }

  goToOwnRecipes() {
    this.showOwnRecipes = true;
    this.activeTab = 'recipes';
  }

  goToOwnIngredients() {
    this.showOwnIngredients = true;
    this.activeTab = 'ingredients';
  }

  logout() {
    localStorage.removeItem('remembered_credentials');
    this.api.clearToken();
    this.router.navigate(['/login']);
  }

  get ownRecipesCount(): number {
    return this.appData.recipes?.filter((r: any) => !r.isPredefined).length || 0;
  }

  get ownIngredientsCount(): number {
    return this.appData.ingredients?.filter((i: any) => !i.isPredefined).length || 0;
  }

  // ---- CALENDAR ----

  generateCalendar() {
    const year = this.calendarYear;
    const month = this.calendarMonth;
    const date = new Date();

    const monthDate = new Date(year, month, 1);
    this.currentMonthName = monthDate.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const firstDay = new Date(year, month, 1).getDay();
    const mondayOffset = firstDay === 0 ? 6 : firstDay - 1;
    this.calendarPadding = Array.from({ length: mondayOffset }, (_, i) => i);

    this.calendarDays = [];
    for (let i = 1; i <= daysInMonth; i++) {
      const d = new Date(year, month, i);
      const dayOfWeek = d.getDay();
      const adjustedDay = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
      this.calendarDays.push({
        dateString: `${year}-${(month + 1).toString().padStart(2, '0')}-${i.toString().padStart(2, '0')}`,
        dayName: d.toLocaleDateString('es-ES', { weekday: 'short' }),
        dayNum: i,
        dayOfWeek: adjustedDay,
        isToday: i === date.getDate() && month === date.getMonth() && year === date.getFullYear(),
      });
    }
  }

  prevMonth() {
    this.calendarMonth--;
    if (this.calendarMonth < 0) { this.calendarMonth = 11; this.calendarYear--; }
    this.selectedCalendarDay = null;
    this.generateCalendar();
  }

  nextMonth() {
    this.calendarMonth++;
    if (this.calendarMonth > 11) { this.calendarMonth = 0; this.calendarYear++; }
    this.selectedCalendarDay = null;
    this.generateCalendar();
  }

  isCurrentMonth(): boolean {
    const now = new Date();
    return this.calendarMonth === now.getMonth() && this.calendarYear === now.getFullYear();
  }

  isNextMonthAllowed(): boolean {
    const now = new Date();
    const maxMonth = now.getMonth() + 1;
    const maxYear = now.getFullYear() + (maxMonth > 11 ? 1 : 0);
    const adjustedMaxMonth = maxMonth > 11 ? 0 : maxMonth;
    return this.calendarYear < maxYear || (this.calendarYear === maxYear && this.calendarMonth < adjustedMaxMonth);
  }

  isPrevMonthAllowed(): boolean {
    const now = new Date();
    const min = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    return this.calendarYear > min.getFullYear() ||
      (this.calendarYear === min.getFullYear() && this.calendarMonth > min.getMonth());
  }

  goToCurrentMonth() {
    const now = new Date();
    this.calendarYear = now.getFullYear();
    this.calendarMonth = now.getMonth();
    this.selectedCalendarDay = null;
    this.generateCalendar();
  }

  selectCalendarDay(day: any) {
    this.selectedCalendarDay = this.selectedCalendarDay?.dateString === day.dateString ? null : day;
  }

  searchMeal(event: { day: string; meal: string; text: string }) {
    const key = `${event.day}_${event.meal}`;
    this.mealSearchText[key] = event.text;

    if (!event.text.trim()) {
      this.mealSearchResults[key] = [];
      this.updatePlanning(event.day, event.meal, '');
      return;
    }

    const term = event.text.toLowerCase();
    this.mealSearchResults[key] = this.appData.recipes
      .filter((r: any) => r.nombre.toLowerCase().includes(term))
      .slice(0, 5);
  }

  selectMeal(event: { day: string; meal: string; recipe: any }) {
    const key = `${event.day}_${event.meal}`;
    this.mealSearchText[key] = event.recipe.nombre;
    this.mealSearchResults[key] = [];
    this.updatePlanning(event.day, event.meal, event.recipe.id);
  }

  clearMeal(event: { day: string; meal: string }) {
    const key = `${event.day}_${event.meal}`;
    this.mealSearchText[key] = '';
    this.mealSearchResults[key] = [];
    this.updatePlanning(event.day, event.meal, '');
  }

  updatePlanning(day: string, meal: string, recipeId: string) {
    if (!this.appData.planning[day]) this.appData.planning[day] = {};
    this.appData.planning[day][meal] = recipeId;
    this.api.savePlanning(this.appData.planning).subscribe({
      error: () => this.toast.error('No se pudieron guardar los cambios.'),
    });
  }

  isPastDay(dateString: string): boolean {
    const today = new Date();
    const todayStr = `${today.getFullYear()}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getDate().toString().padStart(2, '0')}`;
    return dateString < todayStr;
  }

  getRecipeName(id: string): string {
    return this.appData.recipes?.find((r: any) => r.id === id)?.nombre || '';
  }

  hasPlanning(dateString: string): boolean {
    const p = this.appData.planning[dateString];
    return !!(p && (p.desayuno || p.comida || p.cena));
  }

  getMealResults(key: string): any[] {
    return this.mealSearchResults[key] || [];
  }

  // ---- SHOPPING ----

  addRecipeToShopping(recipeId: string) {
    this.api.addRecipeToShopping({ recipe_id: recipeId }).subscribe({
      next: () => {
        this.toast.success('Ingredientes añadidos a la compra.');
        this.loadData();
        this.cdr.detectChanges();
      },
      error: () => this.toast.error('Error al añadir ingredientes a la compra.'),
    });
  }

  addManualShopping(text: string) {
    const tempId = 'local-' + Date.now();
    this.appData.shopping.push({
      id: tempId,
      text,
      checked: false,
    });
    this.api.addShoppingItem({ text }).subscribe({
      next: (res) => {
        const serverId = res?.item?.id;
        if (serverId) {
          const item = this.appData.shopping.find((s: any) => s.id === tempId);
          if (item) item.id = serverId;
        }
      },
      error: () => {
        this.appData.shopping = this.appData.shopping.filter((s: any) => s.id !== tempId);
        this.toast.error('No se pudo añadir el producto.');
      },
    });
  }

  toggleShopping(idx: number) {
    const item = this.appData.shopping[idx];
    const prev = item.checked;
    item.checked = !prev;
    this.orderShoppingItems();

    this.api.toggleShoppingItem({ id: item.id, checked: item.checked }).subscribe({
      error: () => {
        item.checked = prev;
        this.orderShoppingItems();
        this.toast.error('No se pudo guardar el cambio.');
      },
    });
  }

  removeShopping(idx: number) {
    const item = this.appData.shopping[idx];
    this.appData.shopping.splice(idx, 1);
    this.api.deleteShoppingItem({ id: item.id }).subscribe({
      error: () => {
        this.appData.shopping.splice(idx, 0, item);
        this.orderShoppingItems();
        this.toast.error('No se pudo eliminar el producto.');
      },
    });
  }

  clearShopping() {
    this.appData.shopping = [];
    this.api.updateShopping(this.appData.shopping).subscribe({
      error: () => this.toast.error('No se pudieron guardar los cambios.'),
    });
  }

  private orderShoppingItems() {
    this.appData.shopping.sort((a: any, b: any) => Number(a.checked) - Number(b.checked));
  }

  // ---- FRIDGE ----

  getMatchedRecipes(): any[] {
    if (!this.fridgeSelected.length) return [];
    return this.appData.recipes.filter((r: any) => {
      if (!r.ingredientes || r.ingredientes.length === 0) return false;
      return this.fridgeSelected.every((fridgeId) =>
        r.ingredientes.some((ri: any) => ri.ingredient_id === fridgeId),
      );
    });
  }

  addFridgeIng(id: string) {
    if (!this.fridgeSelected.includes(id)) {
      this.fridgeSelected.push(id);
    }
  }

  removeFridgeIng(id: string) {
    this.fridgeSelected = this.fridgeSelected.filter((i) => i !== id);
  }

  // ---- INGREDIENTS CRUD ----

  openIngredientModal(event: { id: string; name: string }) {
    this.ingredientModalRef.open(event);
  }

  saveIngredient(ingredient: { id: string; name: string }) {
    this.api.saveIngredient(ingredient).subscribe({
      next: () => {
        this.ingredientModalRef.hide();
        this.loadData();
      },
      error: (err) => this.toast.error(err.error?.error || 'Error al guardar el ingrediente.'),
    });
  }

  async deleteIngredient(id: string) {
    if (await this.dialog.confirm('¿Seguro que deseas eliminar este ingrediente?')) {
      this.api.deleteIngredient({ id }).subscribe(() => this.loadData());
    }
  }

  // ---- RECIPES CRUD ----

  openRecipeModal(event: { id: string | null; viewMode: boolean }) {
    let recipe;
    if (event.id) {
      recipe = this.appData.recipes.find((r: any) => r.id === event.id);
    } else {
      recipe = { id: '', nombre: '', pasos: '', imagen: '', ingredientes: [] };
    }
    this.recipeModalRef.open(recipe, event.viewMode);
  }

  async saveRecipe(event: { recipe: any; file?: File }) {
    if (this.recipeModalRef.isViewMode) return;

    this.api.saveRecipe(event.recipe).subscribe({
      next: () => {
        const idx = this.appData.recipes.findIndex((r: any) => r.id === event.recipe.id);
        if (idx >= 0) {
          this.appData.recipes[idx] = { ...this.appData.recipes[idx], ...event.recipe };
        } else if (event.recipe.id) {
          this.appData.recipes.push(event.recipe);
        }
        this.recipeModalRef.hide();
        this.cdr.detectChanges();
        this.loadData();
      },
      error: (err) => this.toast.error(err.error?.error || 'Error al guardar la receta.'),
    });
  }

  async deleteRecipe(id: string) {
    if (await this.dialog.confirm('¿Seguro que deseas eliminar esta receta?')) {
      this.api.deleteRecipe({ id }).subscribe({
        next: () => {
          this.recipeModalRef.hide();
          this.loadData();
          this.toast.success('Receta eliminada.');
        },
        error: (err) => this.toast.error(err.error?.error || 'Error al eliminar la receta.'),
      });
    }
  }

  // ---- PROFILE ----

  openProfileModal() {
    this.editingProfile.nombre = this.appData.user?.nombre || '';
    this.editingProfile.new_password = '';
    this.editingProfile.current_password = '';
    this.editingProfile.confirm_password = '';
    this.editingProfile.foto = '';
    this.loadAdminStats();
    this.profileModalRef.open();
  }

  onUserFotoUpdated(foto: string) {
    this.appData.user = { ...this.appData.user, foto };
    this.cdr.detectChanges();
  }

  async saveProfile(event: { profile: any; file?: File }) {
    const { confirm_password, ...profileData } = event.profile;
    const payload = { ...profileData, nombre_casa: this.appData.user.nombre_casa };

    this.api.updateProfile(payload).subscribe({
      next: () => {
        if (event.profile.nombre) this.appData.user.nombre = event.profile.nombre;
        if (event.profile.foto) this.appData.user.foto = event.profile.foto;
        this.profileModalRef.hide();
        this.cdr.detectChanges();
        this.toast.success('Perfil actualizado con éxito.');
        this.loadData();
      },
      error: (err) => this.toast.error(err.error?.error || 'Error al actualizar el perfil.'),
    });
  }

  async inviteUserToHouse(email: string) {
    if (!email.trim()) {
      this.toast.warning('Por favor, escribe un email válido.');
      return;
    }

    const payload = { email, nombre_casa: this.appData.user.nombre_casa };

    this.api.inviteUser(payload).subscribe({
      next: (res: any) => {
        if (res.warning) {
          this.toast.warning('Usuario añadido, pero no se pudo enviar el email. Contacta manualmente.');
        } else {
          this.toast.success('Invitación enviada correctamente.');
        }
        this.inviteEmail = '';
        this.loadAdminStats();
      },
      error: (err) => this.toast.error(err.error?.error || 'Error al invitar al usuario.'),
    });
  }

  async resendInvitationToHouse(email: string) {
    const payload = { email, nombre_casa: this.appData.user.nombre_casa };

    this.api.resendInvitation(payload).subscribe({
      next: (res: any) => {
        if (res.warning) {
          this.toast.warning('Invitación regenerada, pero no se pudo enviar el email. Contacta manualmente.');
        } else {
          this.toast.success('Invitación reenviada correctamente.');
        }
        this.loadAdminStats();
      },
      error: (err) => this.toast.error(err.error?.error || 'Error al reenviar la invitación.'),
    });
  }

  async deleteHouseUser(targetUser: any) {
    const isMe = targetUser.username === this.appData.user.username;
    const isOnlyUser = this.adminStats.house_users.length === 1;

    const payload: any = { target_username: targetUser.username };

    if (isMe) {
      if (isOnlyUser) {
        if (!(await this.dialog.confirm(
          'Eres el único en la casa. Si te eliminas, se borrará tu usuario, la casa y TODOS los datos. ¿Continuar?',
        ))) return;
      } else if (targetUser.role === 'admin') {
        const otherUsers = this.adminStats.house_users.filter(
          (u: any) => u.username !== targetUser.username,
        );
        const options = otherUsers.map((u: any) => `${u.nombre} (${u.username})`);

        const selection = await this.dialog.prompt(
          'Como admin, debes transferir tu rol. Elige a quién:',
          options,
          'Transferir admin',
        );
        if (!selection) return;

        const index = options.indexOf(selection);
        payload.transfer_to = otherUsers[index].username;
        if (!(await this.dialog.confirm(
          `Se transferirá el admin a ${otherUsers[index].nombre} y tu cuenta se borrará. ¿Seguro?`,
        ))) return;
      } else {
        if (!(await this.dialog.confirm('¿Seguro que deseas eliminar tu cuenta?'))) return;
      }
    } else {
      if (!(await this.dialog.confirm(`¿Seguro que deseas eliminar al usuario ${targetUser.nombre}?`))) return;
    }

    this.api.deleteUser(payload).subscribe({
      next: (res: any) => {
        if (res.action === 'logout') {
          this.logout();
        } else {
          this.toast.success('Usuario eliminado.');
          this.loadAdminStats();
        }
      },
      error: (err) => this.toast.error(err.error?.error || 'Error al eliminar usuario.'),
    });
  }
}
