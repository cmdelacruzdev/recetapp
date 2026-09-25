import { Injectable, PLATFORM_ID, inject } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root',
})
export class ApiService {
  private apiUrl = environment.apiUrl;
  private readonly platformId = inject(PLATFORM_ID);

  constructor(private http: HttpClient) {}

  // Token management
  getToken(): string | null {
    if (!isPlatformBrowser(this.platformId)) {
      return null;
    }

    return window.localStorage.getItem('auth_token') || window.sessionStorage.getItem('auth_token');
  }

  setToken(token: string, remember = true): void {
    if (!isPlatformBrowser(this.platformId)) {
      return;
    }

    if (remember) {
      window.localStorage.setItem('auth_token', token);
      window.sessionStorage.removeItem('auth_token');
      return;
    }

    window.sessionStorage.setItem('auth_token', token);
    window.localStorage.removeItem('auth_token');
  }

  clearToken(): void {
    if (!isPlatformBrowser(this.platformId)) {
      return;
    }

    window.localStorage.removeItem('auth_token');
    window.sessionStorage.removeItem('auth_token');
  }

  isAuthenticated(): boolean {
    return !!this.getToken();
  }

  // Auth
  login(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/login`, data);
  }

  register(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/register`, data);
  }

  logout(): Observable<any> {
    return this.http.post(`${this.apiUrl}/logout`, {});
  }

  me(): Observable<any> {
    return this.http.get(`${this.apiUrl}/me`);
  }

  forgotPassword(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/forgot-password`, data);
  }

  resetPassword(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/reset-password`, data);
  }

  activateAccount(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/activate-account`, data);
  }

  // Datos principales
  getAllData(): Observable<any> {
    return this.http.get(`${this.apiUrl}/get_all`);
  }

  getTips(): Observable<any> {
    return this.http.get(`${this.apiUrl}/tips`);
  }

  updateProfile(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/update_profile`, data);
  }

  getAdminStats(): Observable<any> {
    return this.http.get(`${this.apiUrl}/admin_stats`);
  }

  inviteUser(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/invite_user`, data);
  }

  resendInvitation(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/resend_invitation`, data);
  }

  deleteUser(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/delete_user`, data);
  }

  loadPredefinedData(): Observable<any> {
    return this.http.post(`${this.apiUrl}/load_predefined`, {});
  }

  deleteRecipe(data: { id: string }): Observable<any> {
    return this.http.post(`${this.apiUrl}/delete_recipe`, data);
  }

  // SuperAdmin
  superadminLoadPredefined(): Observable<any> {
    return this.http.post(`${this.apiUrl}/admin/load-predefined`, {});
  }

  superadminDeletePredefined(): Observable<any> {
    return this.http.post(`${this.apiUrl}/admin/delete-predefined`, {});
  }

  superadminClearCache(): Observable<any> {
    return this.http.post(`${this.apiUrl}/admin/clear-cache`, {});
  }

  // CRUD
  saveRecipe(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/save_recipe`, data);
  }

  saveIngredient(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/save_ingredient`, data);
  }

  deleteIngredient(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/delete_ingredient`, data);
  }

  savePlanning(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/save_planning`, data);
  }

  updateShopping(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/update_shopping`, data);
  }

  toggleShoppingItem(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/toggle_shopping_item`, data);
  }

  deleteShoppingItem(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/delete_shopping_item`, data);
  }

  addShoppingItem(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/add_shopping_item`, data);
  }

  addRecipeToShopping(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/add_recipe_to_shopping`, data);
  }

  // Uploads
  uploadProfilePhoto(file: File): Observable<any> {
    const formData = new FormData();
    formData.append('photo', file);
    return this.http.post(`${this.apiUrl}/upload/profile-photo`, formData);
  }

  uploadRecipeImage(file: File, recipeId?: string): Observable<any> {
    const formData = new FormData();
    formData.append('image', file);
    if (recipeId) {
      formData.append('recipe_id', recipeId);
    }
    return this.http.post(`${this.apiUrl}/upload/recipe-image`, formData);
  }

  deleteProfilePhoto(): Observable<any> {
    return this.http.post(`${this.apiUrl}/delete_profile_photo`, {});
  }

  deleteRecipeImage(data: { recipe_id: string }): Observable<any> {
    return this.http.post(`${this.apiUrl}/delete_recipe_image`, data);
  }
}
