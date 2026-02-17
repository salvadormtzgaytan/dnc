# 🛠️ Sistema DNC - Documentación Completa

Este proyecto está desarrollado con **Laravel 10**, **Filament 3** y **Spatie Laravel Settings** para facilitar la gestión de necesidades de capacitación.

---

## 📋 Tabla de Contenidos

- [📦 Requisitos del Sistema](#-requisitos-del-sistema)
- [🚀 Instalación Desarrollo](#-instalación-desarrollo)
- [🌐 Despliegue Producción](#-despliegue-producción)
- [⚙️ Comandos Artisan](#️-comandos-artisan)
- [🎨 Comandos Filament](#-comandos-filament)
- [📘 Crear Controlador con Vista](#-crear-controlador-con-vista)
- [🔐 Acceso Inicial](#-acceso-inicial)
- [📁 Estructura del Proyecto](#-estructura-del-proyecto)
- [👥 Contacto](#-contacto)

---

## 📦 Requisitos del Sistema

### Software necesario:

| Componente        | Versión mínima  |
|-------------------|-----------------|
| PHP               | 8.2+            |
| Laravel Framework | ^10.10          |
| Filament          | ^3.3            |
| MariaDB / MySQL   | 10.x            |
| Composer          | 2.x             |
| Node.js           | 18+             |
| Redis             | 6+              |

### Extensiones PHP requeridas:
`pdo`, `mbstring`, `openssl`, `redis`, `tokenizer`, `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `xml`

---

## 🚀 Instalación Desarrollo

### 1. Clonar el repositorio

```bash
git clone https://github.com/salvadormtzgaytan/dnc.git
cd dnc
```

### 2. Instalar dependencias

```bash
composer install
npm install && npm run build
```

### 3. Configurar entorno

```bash
cp .env.example .env
php artisan key:generate
```

Edita `.env` con tus datos locales:

```env
APP_NAME="DNC COMEX"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dnc_comex
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Configuración automática del sistema

```bash
php artisan app:setup-system
```

O para reiniciar desde cero:

```bash
php artisan app:setup-system --fresh --seo-permissions
```

---

## 🌐 Despliegue Producción

### 1. Preparar servidor

- Ubuntu 20.04+ o equivalente
- Certificado SSL configurado
- Nginx o Apache
- Redis configurado

### 2. Clonar e instalar

```bash
git clone https://github.com/salvadormtzgaytan/dnc.git /var/www/dnc
cd /var/www/dnc
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 3. Configurar entorno productivo

```bash
cp .env.example .env
```

Configura `.env` para producción:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dnc.e-360.com.mx
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
FORCE_HTTPS=true
```

### 4. Configuración automática

```bash
php artisan app:setup-production --seed
```

---

## ⚙️ Comandos Artisan

### 📁 Generación de estructura

```bash
# Controlador
php artisan make:controller NombreController

# Modelo completo (migración, factory, seeder, controlador)
php artisan make:model NombreModelo -mfsc

# Migración
php artisan make:migration create_tabla_ejemplo
```

### 🧰 Migraciones y base de datos

```bash
# Ejecutar migraciones
php artisan migrate

# Revertir última migración
php artisan migrate:rollback

# Refrescar (elimina todo, migra y siembra)
php artisan migrate:refresh --seed
```

### 🌱 Seeders

```bash
# Crear seeder
php artisan make:seeder NombreSeeder

# Ejecutar todos los seeders
php artisan db:seed

# Ejecutar seeder específico
php artisan db:seed --class=NombreSeeder
```

### 🧼 Cache y configuración

```bash
# Limpiar cachés
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generar caché de configuración
php artisan config:cache
```

### 📍 Rutas y otros

```bash
# Ver todas las rutas
php artisan route:list

# Ver versión de Laravel
php artisan --version

# Crear política
php artisan make:policy NombrePolicy --model=Modelo

# Crear request con validaciones
php artisan make:request StoreRequest
```

---

## 🎨 Comandos Filament

### 🚀 Instalación

```bash
# Instalar Filament v3
composer require filament/filament:"^3.0"

# Publicar archivos
php artisan filament:install
```

### 👤 Usuarios

```bash
# Crear usuario admin
php artisan make:filament-user
```

### 📁 Recursos (CRUD)

```bash
# Crear Resource completo
php artisan make:filament-resource NombreModelo

# Ejemplo
php artisan make:filament-resource User
```

### 🔗 Relaciones

```bash
# Crear Relation Manager
php artisan make:filament-relation-manager NombreRelacion --resource=NombreResource
```

### 🧩 Widgets

```bash
# Widget personalizado
php artisan make:filament-widget NombreWidget

# Widget de estadísticas
php artisan make:filament-widget StatsOverview --type=stats-overview
```

### 🛡️ Shield (Permisos)

```bash
# Instalar Shield
composer require bezhansalleh/filament-shield
php artisan shield:install

# Generar permisos
php artisan shield:generate
```

### 📦 Importadores/Exportadores

```bash
# Crear importador
php artisan make:filament-import NombreImportador

# Crear exportador
php artisan make:filament-export NombreExportador --generate
```

---

## 📘 Crear Controlador con Vista

### 1. Crear el controlador

```bash
php artisan make:controller UserController
```

### 2. Crear la vista

Crea el archivo `resources/views/users/index.blade.php`:

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Usuarios</title>
</head>
<body>
    <h1>Bienvenido a la lista de usuarios</h1>
</body>
</html>
```

### 3. Editar el controlador

En `app/Http/Controllers/UserController.php`:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index');
    }
}
```

### 4. Registrar la ruta

En `routes/web.php`:

```php
use App\Http\Controllers\UserController;

Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
```

### 5. Probar

Visita: `http://localhost/tu_proyecto/public/usuarios`

---

## 🔐 Acceso Inicial

| Rol         | Email               | Contraseña  |
|-------------|---------------------|-------------|
| Super Admin | admin@example.com   | admin123!   |

Modificable en: `database/seeders/UsersTableSeeder.php`

---

## 📁 Estructura del Proyecto

```
app/
├── Console/Commands/
│   ├── SetupSystem.php          # Instalador desarrollo
│   └── SetupProductionSystem.php # Instalador producción
├── Filament/
│   ├── Resources/               # Recursos CRUD
│   ├── Pages/
│   │   └── ManageSeo.php       # Panel SEO
│   └── Widgets/                # Widgets dashboard
├── Http/Controllers/           # Controladores
├── Models/                     # Modelos Eloquent
├── Settings/
│   └── SeoSettings.php         # Configuraciones SEO dinámicas
└── Utils/                      # Utilidades

database/
├── migrations/                 # Migraciones
└── seeders/                   # Seeders iniciales

resources/
├── views/                     # Vistas Blade
└── js/                        # Assets JavaScript

routes/
├── web.php                    # Rutas web
└── api.php                    # Rutas API
```

### Archivos clave:

- `app/Settings/SeoSettings.php` → Configuraciones dinámicas SEO
- `app/Console/Commands/SetupSystem.php` → Instalador desarrollo
- `app/Console/Commands/SetupProductionSystem.php` → Instalador producción
- `app/Filament/Pages/ManageSeo.php` → Panel de administración SEO
- `database/seeders/` → Seeders iniciales de usuarios, roles y permisos

---

## 🧼 Comandos útiles para mantenimiento

```bash
# Modo mantenimiento
php artisan down
php artisan up

# Colas
php artisan queue:restart
php artisan queue:work

# Optimización
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar estado
php artisan tinker
>>> cache()->put('test', 'ok', 60);
>>> cache()->get('test'); // debe devolver "ok"
```

---


## 📄 Licencia

Este proyecto está bajo la licencia MIT. Ver el archivo `LICENSE` para más detalles.
