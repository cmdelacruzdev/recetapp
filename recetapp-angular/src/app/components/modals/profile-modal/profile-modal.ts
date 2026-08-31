import { Component, Input, Output, EventEmitter, ElementRef, AfterViewInit, OnDestroy, ViewChild, ChangeDetectorRef } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ImageCropModal } from '../image-crop-modal/image-crop-modal';
import { ApiService } from '../../../services/api.service';
import { ToastService } from '../../../services/toast.service';
import { DialogService } from '../../../services/dialog.service';
import { APP_VERSION, APP_VERSION_NEWS } from '../../../core/config/version.config';

declare var bootstrap: any;

@Component({
  selector: 'app-profile-modal',
  standalone: true,
  imports: [FormsModule, ImageCropModal],
  templateUrl: './profile-modal.html',
  styleUrls: ['./profile-modal.scss'],
})
export class ProfileModal implements AfterViewInit, OnDestroy {
  @ViewChild('imageCropModal') imageCropModalRef!: ImageCropModal;

  @Input() user: any = {};
  @Input() adminStats: any = null;
  @Input() editingProfile = { nombre: '', new_password: '', current_password: '', confirm_password: '', foto: '' };
  @Input() inviteEmail = '';
  @Output() save = new EventEmitter<{ profile: any; file?: File }>();
  @Output() close = new EventEmitter<void>();
  @Output() logout = new EventEmitter<void>();
  @Output() inviteUser = new EventEmitter<string>();
  @Output() resendInvitation = new EventEmitter<string>();
  @Output() deleteUser = new EventEmitter<any>();
  @Output() updateInviteEmail = new EventEmitter<string>();
  @Output() predefinedLoaded = new EventEmitter<void>();

  appVersion = APP_VERSION;
  appVersionNews = APP_VERSION_NEWS;
  imagePreview: string | null = null;
  selectedHouseUser: any = null;
  private bsModal: any;

  constructor(private el: ElementRef, private cdr: ChangeDetectorRef, private api: ApiService, private toast: ToastService, private dialog: DialogService) {}

  ngAfterViewInit() {
    if (typeof bootstrap === 'undefined') return;
    const modalEl = this.el.nativeElement.querySelector('#profileModal');
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

  open() {
    this.imagePreview = null;
    this.bsModal?.show();
  }

  async onFileChange(event: any) {
    const file = event.target.files[0] || null;
    if (!file) return;

    const cropped = await this.imageCropModalRef.open(file, 'circle');
    if (cropped) {
      const previewUrl = URL.createObjectURL(cropped);
      this.imagePreview = previewUrl;
      this.cdr.detectChanges();

      // Auto-upload immediately
      try {
        const result: any = await this.api.uploadProfilePhoto(cropped).toPromise();
        if (result?.url) {
          this.user.foto = result.url;
          this.editingProfile.foto = result.url;
          this.imagePreview = result.url;
          this.cdr.detectChanges();
        }
      } catch {
        this.toast.error('No se pudo subir la imagen.');
      }
    }
    event.target.value = '';
  }

  clearPreview() {
    this.imagePreview = null;
    this.editingProfile.foto = '';
  }

  onSave() {
    if (this.editingProfile.new_password) {
      if (!this.editingProfile.confirm_password) {
        this.toast.warning('Repite la nueva contraseña para confirmarla.');
        return;
      }
      if (this.editingProfile.confirm_password !== this.editingProfile.new_password) {
        this.toast.warning('Las contraseñas no coinciden.');
        return;
      }
    }
    this.save.emit({ profile: this.editingProfile });
  }

  onInvite() {
    this.inviteUser.emit(this.inviteEmail);
  }

  onResend(email: string) {
    this.resendInvitation.emit(email);
  }

  formatInvitationTime(hours: number): string {
    if (!hours && hours !== 0) return '';
    if (hours < 1) {
      const minutes = Math.max(1, Math.round(hours * 60));
      return `${minutes} min`;
    }
    return `${hours.toFixed(1)} h`;
  }

  onDeleteUser(user: any) {
    this.deleteUser.emit(user);
  }

  showUserDetail(u: any) {
    this.selectedHouseUser = u;
  }

  closeUserDetail() {
    this.selectedHouseUser = null;
  }

  async onSuperadminLoadPredefined() {
    if (await this.dialog.confirm(
      'Se eliminarán TODOS los datos predefinidos actuales y se recargarán desde los archivos JSON. Los datos de las casas no se verán afectados. ¿Continuar?',
    )) {
      try {
        const res: any = await this.api.superadminLoadPredefined().toPromise();
        this.toast.success(res?.message || 'Datos predefinidos recargados correctamente.');
        this.predefinedLoaded.emit();
      } catch (err: any) {
        this.toast.error(err.error?.error || 'Error al cargar datos predefinidos.');
      }
    }
  }

  async onSuperadminDeletePredefined() {
    if (await this.dialog.confirm(
      'Se eliminarán TODOS los ingredientes y recetas predefinidos (los que no pertenecen a ninguna casa). Los datos de las casas no se verán afectados. ¿Continuar?',
    )) {
      try {
        const res: any = await this.api.superadminDeletePredefined().toPromise();
        this.toast.success(res?.message || 'Datos predefinidos eliminados correctamente.');
      } catch (err: any) {
        this.toast.error(err.error?.error || 'Error al eliminar datos predefinidos.');
      }
    }
  }

  async onSuperadminClearCache() {
    if (await this.dialog.confirm(
      'Se limpiará la caché del servidor (configuración, rutas, optimización). La app puede tardar unos segundos en volver a funcionar. ¿Continuar?',
    )) {
      try {
        const res: any = await this.api.superadminClearCache().toPromise();
        this.toast.success(res?.message || 'Caché limpiada correctamente.');
      } catch (err: any) {
        this.toast.error(err.error?.error || 'Error al limpiar la caché.');
      }
    }
  }

  onLogout() {
    this.logout.emit();
  }

  hide() {
    this.bsModal?.hide();
  }

  get isAdmin(): boolean {
    return this.user?.role === 'admin';
  }

  get isSuperadmin(): boolean {
    return this.adminStats?.is_superadmin;
  }

  get isDefaultFoto(): boolean {
    return !this.user?.foto || this.user.foto.endsWith('.svg');
  }

  async onDeletePhoto() {
    try {
      const result: any = await this.api.deleteProfilePhoto().toPromise();
      if (result?.success) {
        this.user.foto = result.foto;
        this.imagePreview = null;
        this.editingProfile.foto = '';
        this.cdr.detectChanges();
        this.toast.success('Foto eliminada. Se ha restablecido la foto predeterminada.');
      }
    } catch {
      this.toast.error('No se pudo eliminar la foto.');
    }
  }
}
