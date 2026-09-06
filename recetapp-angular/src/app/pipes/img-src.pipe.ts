import { Pipe, PipeTransform } from '@angular/core';
import { environment } from '../../environments/environment';

@Pipe({
  name: 'imgSrc',
  standalone: true,
})
export class ImgSrcPipe implements PipeTransform {
  transform(value: string | null | undefined): string {
    if (!value) {
      return '';
    }

    if (value.startsWith('blob:') || value.startsWith('data:')) {
      return value;
    }

    return this.resolveAbsolute(value);
  }

  // Resuelve rutas relativas (/storage/...) contra el origen de la API.
  private resolveAbsolute(value: string): string {
    if (
      value.startsWith('http://') ||
      value.startsWith('https://') ||
      value.startsWith('//')
    ) {
      return value;
    }
    return `${this.apiOrigin()}${value}`;
  }

  private apiOrigin(): string {
    try {
      const url = new URL(environment.apiUrl, window.location.origin);
      return url.origin;
    } catch {
      const match = /^(https?:\/\/[^/]+)/.exec(environment.apiUrl);
      return match ? match[1] : '';
    }
  }
}
