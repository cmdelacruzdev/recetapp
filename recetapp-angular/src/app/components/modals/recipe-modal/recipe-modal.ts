import { Component, Input, Output, EventEmitter, ElementRef, AfterViewInit, OnDestroy, ViewChild, ChangeDetectorRef } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ImageCropModal } from '../image-crop-modal/image-crop-modal';
import { ApiService } from '../../../services/api.service';
import { ToastService } from '../../../services/toast.service';

declare var bootstrap: any;

@Component({
  selector: 'app-recipe-modal',
  standalone: true,
  imports: [FormsModule, ImageCropModal],
  templateUrl: './recipe-modal.html',
  styleUrls: ['./recipe-modal.scss'],
})
export class RecipeModal implements AfterViewInit, OnDestroy {
  @ViewChild('imageCropModal') imageCropModalRef!: ImageCropModal;

  @Input() editingRecipe: any = { id: '', nombre: '', pasos: '', imagen: '', ingredientes: [] };
  @Input() isViewMode = false;
  @Input() ingredients: any[] = [];
  @Output() save = new EventEmitter<{ recipe: any; file?: File }>();
  @Output() close = new EventEmitter<void>();
  @Output() delete = new EventEmitter<string>();

  recipeIngSearchText = '';
  recipeIngResults: any[] = [];
  showIngDropdown = -1;
  activeIngredientIndex = -1;
  imagePreview: string | null = null;
  uploadedUrl: string | null = null;

  private bsModal: any;

  constructor(private el: ElementRef, private cdr: ChangeDetectorRef, private api: ApiService, private toast: ToastService) {}

  ngAfterViewInit() {
    if (typeof bootstrap === 'undefined') return;
    const modalEl = this.el.nativeElement.querySelector('#recipeModal');
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

  open(recipe: any, viewMode: boolean) {
    this.editingRecipe = JSON.parse(JSON.stringify(recipe));
    this.isViewMode = viewMode;
    this.imagePreview = null;
    this.uploadedUrl = null;
    this.recipeIngSearchText = '';
    this.recipeIngResults = [];
    this.showIngDropdown = -1;
    this.activeIngredientIndex = -1;

    if (this.editingRecipe.imagen) {
      this.imagePreview = this.editingRecipe.imagen;
    }

    if (this.editingRecipe.ingredientes.length === 0 && !viewMode) {
      this.addIngredientRow();
    }

    this.bsModal?.show();
  }

  addIngredientRow() {
    this.editingRecipe.ingredientes.push({ nombre: '', quantity: '', ingredient_id: '' });
  }

  removeIngredientRow(index: number) {
    this.editingRecipe.ingredientes.splice(index, 1);
  }

  searchIngredient(index: number, text: string) {
    this.recipeIngSearchText = text;
    this.showIngDropdown = index;
    this.activeIngredientIndex = index;

    if (!text.trim()) {
      this.recipeIngResults = [];
      return;
    }

    const term = text.toLowerCase();
    this.recipeIngResults = this.ingredients
      .filter((i) => i.name.toLowerCase().includes(term))
      .slice(0, 5);
  }

  selectIngredient(ing: any) {
    const index = this.activeIngredientIndex;
    if (index < 0 || !this.editingRecipe.ingredientes[index]) return;
    this.editingRecipe.ingredientes[index].nombre = ing.name;
    this.editingRecipe.ingredientes[index].ingredient_id = ing.id;
    this.recipeIngResults = [];
    this.showIngDropdown = -1;
    this.activeIngredientIndex = -1;
  }

  async onFileChange(event: any) {
    const file = event.target.files[0] || null;
    if (!file) return;

    const cropped = await this.imageCropModalRef.open(file, 'landscape');
    if (cropped) {
      this.imagePreview = URL.createObjectURL(cropped);
      this.cdr.detectChanges();

      // Auto-upload immediately
      try {
        const result: any = await this.api.uploadRecipeImage(cropped, this.editingRecipe.id).toPromise();
        if (result?.url) {
          this.uploadedUrl = result.url;
          this.editingRecipe.imagen = result.url;
        }
      } catch {
        this.toast.error('No se pudo subir la imagen.');
      }
    }
    event.target.value = '';
  }

  clearPreview() {
    this.imagePreview = null;
    this.uploadedUrl = null;
    this.editingRecipe.imagen = '';
  }

  onSave() {
    this.editingRecipe.imagen = this.uploadedUrl || this.editingRecipe.imagen;
    this.save.emit({ recipe: this.editingRecipe });
  }

  onDelete() {
    this.delete.emit(this.editingRecipe.id);
  }

  hide() {
    this.bsModal?.hide();
  }

  get isDefaultImage(): boolean {
    return !this.editingRecipe?.imagen || this.editingRecipe.imagen.endsWith('.svg');
  }

  async onDeleteImage() {
    if (!this.editingRecipe?.id) return;
    try {
      const result: any = await this.api.deleteRecipeImage({ recipe_id: this.editingRecipe.id }).toPromise();
      if (result?.success) {
        this.editingRecipe.imagen = result.imagen;
        this.imagePreview = result.imagen;
        this.uploadedUrl = null;
        this.cdr.detectChanges();
        this.toast.success('Foto eliminada. Se ha restablecido la imagen predeterminada.');
      }
    } catch {
      this.toast.error('No se pudo eliminar la imagen.');
    }
  }
}
