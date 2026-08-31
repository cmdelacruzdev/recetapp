import { RenderMode, ServerRoute } from '@angular/ssr';

export const serverRoutes: ServerRoute[] = [
  {
    // Rutas privadas con datos de sesión: siempre cliente, nunca prerender/SSR
    path: '**',
    renderMode: RenderMode.Client
  }
];
