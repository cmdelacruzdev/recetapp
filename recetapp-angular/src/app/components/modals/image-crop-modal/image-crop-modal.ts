import { Component, ElementRef, AfterViewInit, OnDestroy, ViewChild } from '@angular/core';

declare var bootstrap: any;

@Component({
  selector: 'app-image-crop-modal',
  standalone: true,
  templateUrl: './image-crop-modal.html',
  styleUrls: ['./image-crop-modal.scss'],
})
export class ImageCropModal implements AfterViewInit, OnDestroy {
  @ViewChild('cropCanvas') canvasRef!: any;

  imageSrc = '';
  fileName = '';
  fileType = '';
  zoom = 1;
  offsetX = 0;
  offsetY = 0;
  mode: 'circle' | 'landscape' = 'circle';
  private bsModal: any;
  private isDragging = false;
  private lastX = 0;
  private lastY = 0;
  private img = new Image();
  private canvas: HTMLCanvasElement | null = null;
  private ctx: CanvasRenderingContext2D | null = null;
  private resolvePromise: ((file: File | null) => void) | null = null;

  private displayW = 0;
  private displayH = 0;

  private cropSize = 512;

  constructor(private el: ElementRef) {}

  ngAfterViewInit() {
    if (typeof bootstrap === 'undefined') return;
    const modalEl = this.el.nativeElement.querySelector('#imageCropModal');
    this.bsModal = new bootstrap.Modal(modalEl, { backdrop: false, keyboard: false });
    modalEl.addEventListener('hide.bs.modal', () => {
      if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
      }
    });
    modalEl.addEventListener('hidden.bs.modal', () => {
      this.resumeParentModals();
    });
  }

  ngOnDestroy() {
    this.bsModal?.dispose();
  }

  open(file: File, mode: 'circle' | 'landscape' = 'circle'): Promise<File | null> {
    return new Promise((resolve) => {
      this.resolvePromise = resolve;
      this.mode = mode;
      this.fileName = file.name;
      this.fileType = file.type || 'image/jpeg';
      this.zoom = 1;
      this.offsetX = 0;
      this.offsetY = 0;

      const reader = new FileReader();
      reader.onload = () => {
        this.imageSrc = reader.result as string;
        this.img.onload = () => {
          this.pauseParentModals();
          this.bsModal?.show();
          setTimeout(() => this.initCanvas(), 100);
        };
        this.img.src = this.imageSrc;
      };
      reader.readAsDataURL(file);
    });
  }

  private initCanvas() {
    this.canvas = this.el.nativeElement.querySelector('#cropCanvas');
    if (!this.canvas) return;

    this.displayW = this.mode === 'landscape' ? 320 : 256;
    this.displayH = this.mode === 'landscape' ? 180 : 256;

    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    this.canvas.width = Math.round(this.displayW * dpr);
    this.canvas.height = Math.round(this.displayH * dpr);
    this.ctx = this.canvas.getContext('2d');
    this.draw();
  }

  private draw() {
    if (!this.ctx || !this.canvas || !this.displayW) return;

    const { width: cw, height: ch } = this.canvas;
    this.ctx.clearRect(0, 0, cw, ch);

    const dpr = cw / this.displayW;
    const iw = this.img.naturalWidth;
    const ih = this.img.naturalHeight;
    const scale = Math.max(cw / iw, ch / ih) * this.zoom;
    const dw = iw * scale;
    const dh = ih * scale;
    const dx = (cw - dw) / 2 + this.offsetX * dpr;
    const dy = (ch - dh) / 2 + this.offsetY * dpr;

    this.ctx.drawImage(this.img, dx, dy, dw, dh);
  }

  onZoomChange(value: number) {
    this.zoom = value;
    this.draw();
  }

  onCanvasMouseDown(e: MouseEvent) {
    this.isDragging = true;
    this.lastX = e.clientX;
    this.lastY = e.clientY;
    e.preventDefault();
  }

  onCanvasMouseMove(e: MouseEvent) {
    if (!this.isDragging) return;
    this.offsetX += e.clientX - this.lastX;
    this.offsetY += e.clientY - this.lastY;
    this.lastX = e.clientX;
    this.lastY = e.clientY;
    this.draw();
  }

  onCanvasMouseUp() {
    this.isDragging = false;
  }

  onTouchStart(e: TouchEvent) {
    if (e.touches.length === 1) {
      this.isDragging = true;
      this.lastX = e.touches[0].clientX;
      this.lastY = e.touches[0].clientY;
      e.preventDefault();
    }
  }

  onTouchMove(e: TouchEvent) {
    if (!this.isDragging || e.touches.length !== 1) return;
    this.offsetX += e.touches[0].clientX - this.lastX;
    this.offsetY += e.touches[0].clientY - this.lastY;
    this.lastX = e.touches[0].clientX;
    this.lastY = e.touches[0].clientY;
    this.draw();
    e.preventDefault();
  }

  onTouchEnd() {
    this.isDragging = false;
  }

  private pauseParentModals() {
    if (document.activeElement instanceof HTMLElement) {
      document.activeElement.blur();
    }
    document.querySelectorAll('.modal.show').forEach((el) => {
      if (el.id !== 'imageCropModal') {
        el.setAttribute('aria-hidden', 'true');
        (el as HTMLElement).inert = true;
      }
    });
  }

  private resumeParentModals() {
    document.querySelectorAll('.modal[aria-hidden="true"]').forEach((el) => {
      el.removeAttribute('aria-hidden');
      (el as HTMLElement).inert = false;
    });
  }

  onConfirm() {
    if (!this.displayW) {
      this.resolvePromise?.(null);
      this.bsModal?.hide();
      return;
    }

    const outW = this.cropSize;
    const outH = this.mode === 'landscape' ? Math.round(this.cropSize * 9 / 16) : this.cropSize;

    const out = document.createElement('canvas');
    out.width = outW;
    out.height = outH;
    const octx = out.getContext('2d');
    if (!octx) {
      this.resolvePromise?.(null);
      this.bsModal?.hide();
      return;
    }

    const iw = this.img.naturalWidth;
    const ih = this.img.naturalHeight;
    const scale = Math.max(outW / iw, outH / ih) * this.zoom;
    const dw = iw * scale;
    const dh = ih * scale;
    const factor = outW / this.displayW;
    const dx = (outW - dw) / 2 + this.offsetX * factor;
    const dy = (outH - dh) / 2 + this.offsetY * factor;

    octx.save();
    if (this.mode === 'circle') {
      octx.beginPath();
      octx.arc(outW / 2, outH / 2, outW / 2, 0, Math.PI * 2);
      octx.clip();
    }
    octx.drawImage(this.img, dx, dy, dw, dh);
    octx.restore();

    out.toBlob((blob) => {
      if (blob) {
        const ext = this.fileType.split('/')[1] || 'jpeg';
        const file = new File([blob], this.fileName || `crop.${ext}`, { type: this.fileType });
        this.resolvePromise?.(file);
      } else {
        this.resolvePromise?.(null);
      }
      this.bsModal?.hide();
    }, this.fileType, 0.95);
  }

  onCancel() {
    this.resolvePromise?.(null);
    this.bsModal?.hide();
  }
}
