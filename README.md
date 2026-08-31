# RecetAPP

Aplicación **Progressive Web App (PWA)** para la gestión de recetas, planificación semanal de comidas y lista de la compra. Está pensada para un uso diario en dispositivos móviles, pero también ofrece una experiencia de escritorio.

Compuesta por dos partes independientes del mismo repositorio:

| Carpeta | Capa | Tecnología |
| ------- | ---- | ---------- |
| `api-recetapp/` | Backend (API REST) | Laravel 13, PHP 8.3+, MySQL 8.0+ |
| `recetapp-angular/` | Frontend (SPA) | Angular 21, Bootstrap 5, PWA |

---

## Índice

1. [Stack tecnológico](#stack-tecnológico)
2. [Requisitos previos](#requisitos-previos)
3. [Estructura del proyecto](#estructura-del-proyecto)
4. [Desarrollo local](#desarrollo-local)
5. [Variables de entorno](#variables-de-entorno)
6. [Base de datos](#base-de-datos)
7. [Endpoints de la API](#endpoints-de-la-api)
8. [Datos predefinidos](#datos-predefinidos)
9. [Despliegue en producción](#despliegue-en-producción)
10. [Autoría](#autoría)

---

## Stack tecnológico

| Capa | Tecnología |
| ---- | ---------- |
| Backend | Laravel 13, PHP 8.3+, MySQL 8.0+ |
| Frontend | Angular 21 (standalone), Bootstrap 5, bootstrap-icons |
| Autenticación | Laravel Sanctum (tokens Bearer) |
| PWA | Angular Service Worker, manifest, instalable en Android/iOS/Windows |
| Archivos | Storage de Laravel (fotos de perfil e imágenes de recetas en `storage/app/public/`) |
| Despliegue frontend | SPA estática servida por Apache/Nginx |

> **Nota sobre SSR:** aunque la dependencia `@angular/ssr` está presente, la aplicación se construye como **SPA estática** (sin renderizado de servidor). El build de producción genera los archivos en `dist/recetapp-angular/browser/`.

---

## Requisitos previos

### API (Laravel)

- PHP **8.3+** con extensiones: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`.
- MySQL **8.0+**.
- Composer **2.x**.
- Git.

### Frontend (Angular)

- Node.js **20+** y npm (incluido con Node).
- Angular CLI **21** (opcional, ya que los scripts de `package.json` delegan en él).

---

## Estructura del proyecto

```
recetapp/
├── api-recetapp/              # Backend - Laravel 13
│   ├── app/
│   │   ├── Http/Controllers/  # AuthController, ApiController, SuperAdminController
│   │   ├── Models/            # User, House, Recipe, Ingredient, Planning, ShoppingItem
│   │   ├── Mail/              # WelcomeMail, InvitationMail, ResetPasswordMail
│   │   ├── Services/          # JsonDatabase
│   │   └── Traits/            # IsSuperAdmin
│   ├── routes/
│   │   ├── api.php            # Rutas de la API (públicas + Sanctum + superadmin)
│   │   └── web.php            # Ruta de utilidad (info de la API)
│   ├── config/
│   │   └── recetapp.php       # Configuración custom (límites, URL frontend, superadmin)
│   ├── database/
│   │   ├── migrations/        # Esquema completo (migración única)
│   │   └── seeders/           # Carga de datos desde JSON
│   └── public/                # Document root de la API
│
├── recetapp-angular/          # Frontend - Angular 21 (SPA + PWA)
│   └── src/
│       ├── app/
│       │   ├── pages/         # Login, Register, Home, ForgotPassword, ResetPassword, Attivación, DesktopLanding
│       │   ├── components/    # Navbars, tabs, modales, confirm-dialog, toast
│       │   ├── services/      # ApiService, ToastService, DialogService
│       │   ├── guards/        # authGuard, desktopGuard
│       │   └── core/          # pwa-install.service, update.service, version.config
│       └── environments/      # environment.ts (dev) / environment.prod.ts (prod)
```

---

## Desarrollo local

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd recetapp
```

### 2. Configurar la API (Laravel)

```bash
cd api-recetapp

# Instalar dependencias PHP
composer install

# Copiar y configurar .env
cp .env.example .env
php artisan key:generate

# Editar .env con tus datos de MySQL:
#   DB_CONNECTION=mysql
#   DB_HOST=127.0.0.1
#   DB_PORT=3306
#   DB_DATABASE=recetapp
#   DB_USERNAME=root
#   DB_PASSWORD=

# Crear la base de datos en MySQL
mysql -u root -e "CREATE DATABASE recetapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Ejecutar migraciones (crea todo el esquema)
php artisan migrate:fresh

# Insertar ingredientes y recetas predefinidos
php artisan db:seed

# Crear symlink para imágenes públicas
php artisan storage:link

# Iniciar servidor de desarrollo
php artisan serve
# API disponible en: http://127.0.0.1:8000
```

### 3. Configurar el Frontend (Angular)

```bash
cd ../recetapp-angular

# Instalar dependencias Node
npm install

# Iniciar servidor de desarrollo
npm start        # equivalente a ng serve
# App disponible en: http://localhost:4200
```

En modo desarrollo la app se conecta a `http://127.0.0.1:8000` (definido en `src/environments/environment.ts`).

---

## Variables de entorno

### API (`api-recetapp/.env`)

Archivo de referencia: [`.env.example`](api-recetapp/.env.example). Copia el ejemplo y ajusta los valores:

| Variable | Descripción | Ejemplo local |
| -------- | ----------- | ------------- |
| `APP_NAME` | Nombre de la aplicación | `RecetAPP` |
| `APP_ENV` | Entorno (`local`, `production`) | `local` |
| `APP_KEY` | Clave de encriptación (generar con `key:generate`) | `base64:...` |
| `APP_DEBUG` | Mostrar errores detallados (`true` en local, `false` en prod) | `true` |
| `APP_URL` | URL pública de la API | `http://localhost:8000` |
| `FRONTEND_URL` | URL del frontend (usada para CORS y emails) | `http://localhost:4200` |
| `SUPERADMIN_EMAIL` | Email del usuario superadmin global | `admin@example.com` |
| `DB_CONNECTION` | Motor de BD | `mysql` |
| `DB_HOST` | Host de MySQL | `127.0.0.1` |
| `DB_PORT` | Puerto de MySQL | `3306` |
| `DB_DATABASE` | Nombre de la BD | `recetapp` |
| `DB_USERNAME` | Usuario de MySQL | `root` |
| `DB_PASSWORD` | Contraseña de MySQL | `(vacío)` |
| `SESSION_DRIVER` | Driver de sesión | `database` |
| `SANCTUM_STATEFUL_DOMAINS` | Dominios con cookies Sanctum | `localhost,localhost:4200,127.0.0.1` |
| `MAIL_MAILER` | Mailer (`log` en local, `smtp` en prod) | `log` |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` | Config SMTP para enviar emails | — |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | Remitente de los emails | — |

**Otras variables relevantes del framework** (mantener por defecto a menos que se requiera cambio): `BCRYPT_ROUNDS`, `LOG_CHANNEL`, `QUEUE_CONNECTION`, `CACHE_STORE`, `CACHE_PREFIX`, `BROADCAST_CONNECTION`, `FILESYSTEM_DISK`.

> **Configuración custom (`config/recetapp.php`):**
> - `frontend_url` → toma el valor de `FRONTEND_URL`.
> - `superadmin_email` → toma el valor de `SUPERADMIN_EMAIL`.
> - `invitation_token_hours` → horas de validez del token de invitación (48 por defecto).
> - `limits` → límites por casa: `ingredients` (750), `recipes` (250), `shopping` (250), `users_per_house` (5), `total_users` (100).

### Frontend (`recetapp-angular/src/environments/`)

La configuración de entorno se inyecta en el build mediante `fileReplacements` en `angular.json`. Se utiliza el archivo de desarrollo o el de producción según el configuration elegido.

**`environment.ts`** (desarrollo):

```ts
export const environment = {
  production: false,
  apiUrl: 'http://127.0.0.1:8000/api',
  assetsUrl: 'http://127.0.0.1:8000',
};
```

**`environment.prod.ts`** (producción):

```ts
export const environment = {
  production: true,
  apiUrl: 'https://<dominio-api>/api',
  assetsUrl: 'https://<dominio-api>',
};
```

| Archivo | Variable | Descripción | Ejemplo |
| ------- | -------- | ----------- | ------- |
| `environment.ts` | `apiUrl` | URL base de la API (desarrollo) | `http://127.0.0.1:8000/api` |
| `environment.ts` | `assetsUrl` | URL base de archivos (desarrollo) | `http://127.0.0.1:8000` |
| `environment.prod.ts` | `apiUrl` | URL base de la API (producción) | `https://<dominio-api>/api` |
| `environment.prod.ts` | `assetsUrl` | URL base de archivos (producción) | `https://<dominio-api>` |

> `apiUrl` apunta a la raíz de la API (`/api` incluido). `assetsUrl` es la raíz del host de la API (usada para resolver rutas de imágenes y archivos).

---

## Base de datos

El esquema completo se crea con una **migración única** (`database/migrations/2026_08_25_000000_create_recetapp_full_schema.php`).

Tablas de negocio:

| Tabla | Descripción |
| ----- | ----------- |
| `houses` | Casas/familias (agrupa usuarios). Tenante central. |
| `users` | Usuarios de la app (`username` = email, `foto` = URL, `casa_id`). |
| `recipes` | Recetas (`imagen` = URL en storage). |
| `ingredients` | Ingredientes globales y por casa. |
| `recipe_ingredient` | Relación receta-ingrediente (con cantidad). |
| `plannings` | Planificación semanal por casa. |
| `shopping_items` | Lista de la compra por casa. |
| `activation_tokens` | Tokens de activación de cuenta / invitaciones. |
| `password_reset_tokens` | Tokens de restablecimiento de contraseña. |
| `personal_access_tokens` | Tokens de autenticación Sanctum. |

Tablas del framework (se crean automáticamente con la migración y no requieren configuración): `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`.

**Roles de usuario:**
- `admin` / `user` → por casa.
- `superadmin` → global (identificado por `SUPERADMIN_EMAIL`).

---

## Endpoints de la API

Formato de respuesta JSON estandarizado (`{ success: true, ... }` o `{ error: '...' }`). Todas las rutas protegidas requieren `Authorization: Bearer <token>`.

### Públicos

| Método | Ruta | Descripción |
| ------ | ---- | ----------- |
| POST | `/api/login` | Iniciar sesión (retorna token). |
| POST | `/api/register` | Registrar usuario + casa. |
| GET | `/api/activate/{token}` | Activar cuenta (redirige al frontend). |
| POST | `/api/activate-account` | Activar cuenta mediante formulario. |
| POST | `/api/forgot-password` | Solicitar restablecimiento de contraseña. |
| POST | `/api/reset-password` | Restablecer contraseña con token. |

### Protegidos (requieren token)

**Auth**

| Método | Ruta | Descripción |
| ------ | ---- | ----------- |
| POST | `/api/logout` | Cerrar sesión (revoca token). |
| GET | `/api/me` | Obtener usuario autenticado. |

**Datos y perfil**

| Método | Ruta | Descripción |
| ------ | ---- | ----------- |
| GET | `/api/get_all` | Obtener todos los datos del usuario (recetas, ingredientes, planning, compra). |
| POST | `/api/update_profile` | Actualizar perfil. |
| GET | `/api/admin_stats` | Estadísticas de administrador. |
| GET | `/api/tips` | Obtener consejos rotativos. |

**Usuarios**

| Método | Ruta | Descripción |
| ------ | ---- | ----------- |
| POST | `/api/invite_user` | Invitar usuario por email. |
| POST | `/api/resend_invitation` | Reenviar invitación pendiente. |
| POST | `/api/delete_user` | Eliminar usuario. |

**Recetas y planning**

| Método | Ruta | Descripción |
| ------ | ---- | ----------- |
| POST | `/api/save_recipe` | Crear/editar receta. |
| POST | `/api/delete_recipe` | Eliminar receta. |
| POST | `/api/save_planning` | Guardar planificación semanal. |

**Ingredientes**

| Método | Ruta | Descripción |
| ------ | ---- | ----------- |
| POST | `/api/save_ingredient` | Crear/editar ingrediente. |
| POST | `/api/delete_ingredient` | Eliminar ingrediente. |

**Compras**

| Método | Ruta | Descripción |
| ------ | ---- | ----------- |
| POST | `/api/update_shopping` | Actualizar lista de la compra. |
| POST | `/api/toggle_shopping_item` | Marcar/desmarcar elemento de la compra. |
| POST | `/api/delete_shopping_item` | Eliminar elemento de la compra. |
| POST | `/api/add_shopping_item` | Añadir elemento de la compra. |
| POST | `/api/add_recipe_to_shopping` | Añadir ingredientes de una receta a la compra. |

**Uploads**

| Método | Ruta | Descripción |
| ------ | ---- | ----------- |
| POST | `/api/upload/profile-photo` | Subir foto de perfil. |
| POST | `/api/upload/recipe-image` | Subir imagen de receta. |
| POST | `/api/delete_profile_photo` | Eliminar foto de perfil. |
| POST | `/api/delete_recipe_image` | Eliminar imagen de receta. |

### Solo SuperAdmin (prefijo `/api/admin`, token + rol superadmin)

| Método | Ruta | Descripción |
| ------ | ---- | ----------- |
| POST | `/api/admin/load-predefined` | Cargar recetas/ingredientes base en una casa. |
| POST | `/api/admin/delete-predefined` | Eliminar datos predefinidos. |
| POST | `/api/admin/clear-cache` | Limpiar caché del servidor. |

---

## Rutas de utilidad (`routes/web.php`)

| Ruta | Descripción |
| ---- | ----------- |
| `GET /` | Info de la API (nombre, entorno, descripción, autor, app, debug). |

---

## Datos predefinidos

La app incluye ingredientes y recetas base en `storage/app/private/data/` (JSON). Para insertarlos:

```bash
php artisan db:seed
```

Los datos se copian a la casa del usuario cuando este ejecuta "Cargar Recetas Base" desde el panel de administrador (superadmin → `/api/admin/load-predefined`).

---

## Despliegue en producción

### API (Laravel)

```bash
# Clonar en el servidor
cd /ruta/al/proyecto
git clone <url> api-recetapp
cd api-recetapp

# Instalar dependencias (sin dev)
composer install --optimize-autoloader --no-dev

# Configurar .env de producción
cp .env.example .env
# Ajusta: APP_ENV=production, APP_DEBUG=false, APP_URL, FRONTEND_URL,
#         SUPERADMIN_EMAIL, DB_*, SANCTUM_STATEFUL_DOMAINS, MAIL_*

# Generar clave (si no existe)
php artisan key:generate

# Ejecutar migraciones
php artisan migrate --force

# Insertar datos predefinidos
php artisan db:seed --force

# Crear symlink de storage
php artisan storage:link

# Optimizar Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configurar permisos
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

> **Importante:** en producción `APP_DEBUG` debe ser `false`. Asegúrate de que `FRONTEND_URL` apunta a tu dominio del frontend y que los correos (invitaciones, restablecimiento de contraseña) funcionan configurando correctamente `MAIL_*`.

### Frontend (Angular)

```bash
cd recetapp-angular

# Instalar dependencias
npm install

# Ajustar src/environments/environment.prod.ts con tus dominios

# Build de producción (usa environment.prod.ts y activa el service worker)
npm run build
# o: ng build --configuration=production

# Los archivos generados quedan en dist/recetapp-angular/browser/
# Copia el contenido de esa carpeta al servidor web (Apache/Nginx)
```

> El build de producción genera además el manifest PWA y los archivos del service worker, por lo que la app es instalable y funciona offline una vez el servidor web la sirva por HTTPS.

---

## Autoría

- **Desarrollo**: Carlos de la Cruz Romero — [LinkedIn](https://www.linkedin.com/in/carlos-de-la-cruz-romero/) · <hola@cmdelacruzdev.es>
