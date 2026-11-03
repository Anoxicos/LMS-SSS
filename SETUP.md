# Local setup

This is a Laravel 10 application with Vue 3, Inertia, and Vite. PHP dependencies are managed by Composer; frontend dependencies by npm. The checked-in `.env.example` targets MySQL.

## Requirements

- PHP 8.1 or newer, with Composer
- Node.js and npm
- MySQL, with PHP's `pdo_mysql` extension
- PHP extensions required by Laravel and the installed packages, including `fileinfo`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `zip`, and `gd`

## Install

From the repository root:

```sh
composer install
npm install
```

Create a MySQL database, then create the local environment file:

```sh
cp .env.example .env
```

On Windows PowerShell, use `Copy-Item .env.example .env` instead. Edit `.env` and set `APP_NAME`, `APP_URL`, and the `DB_*` values to match your local database. The sample database name is `velzon_inertia`.

Generate the application key, run migrations, and expose uploaded public files:

```sh
php artisan key:generate
php artisan migrate
php artisan storage:link
```

The session driver in `.env.example` is `database`; the migrations create the session table. If you switch to a non-database session driver, update `SESSION_DRIVER` accordingly.

## Seed baseline data and create an admin

The full `DatabaseSeeder` is currently not safe for a fresh database: it calls `PlanDetailsSeeder` with plan ID `1`, but the repository has no plan seeder and `plans` requires an existing course. It also creates a Super-admin user with a known password (`12345678`). Do not run `php artisan db:seed` on shared or production data.

Seed the independent roles, permissions, and accreditation records individually:

```sh
php artisan db:seed --class=PermissionsSeeder
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=AccreditationSeeder
```

Create an administrator with a private password using Tinker:

```sh
php artisan tinker
```

Then enter:

```php
$user = App\Models\User::create([
    'first_name' => 'Admin',
    'last_name' => 'User',
    'email' => 'admin@example.test',
    'email_verified_at' => now(),
    'password' => Illuminate\Support\Facades\Hash::make('replace-with-a-private-password'),
]);
$user->assignRole('Super-admin');
```

Use an email and password appropriate for your local environment. The app requires verified email for its authenticated routes.

## Run the app

In separate terminals, start Vite and Laravel:

```sh
npm run dev
```

```sh
php artisan serve
```

Open the URL printed by `artisan serve` (normally `http://127.0.0.1:8000`). For a production-style frontend build, run `npm run build` and serve Laravel through a web server with `public/` as its document root.

### Windows preview with SQLite

Use this if MySQL is unavailable and PHP includes `C:\php\ext\php_pdo_sqlite.dll` and `php_sqlite3.dll`. These commands keep the project's `.env` unchanged and create a separate local database. Run them from PowerShell in the project root:

```powershell
if (!(Test-Path database/database.sqlite)) { New-Item database/database.sqlite -ItemType File | Out-Null }
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = (Resolve-Path database/database.sqlite).Path
$env:SESSION_DRIVER = 'file'
$env:APP_URL = 'http://127.0.0.1:8000'
$phpExtensions = @('-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3')
& C:\php\php.exe @phpExtensions artisan migrate --force
& C:\php\php.exe @phpExtensions artisan db:seed --class=PermissionsSeeder --force
& C:\php\php.exe @phpExtensions artisan db:seed --class=RoleSeeder --force
& C:\php\php.exe @phpExtensions artisan db:seed --class=AccreditationSeeder --force
$projectRoot = (Get-Location).Path
Push-Location (Join-Path $projectRoot 'public')
& C:\php\php.exe @phpExtensions -S 127.0.0.1:8000 -t . (Join-Path $projectRoot 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')
Pop-Location
```

In a second terminal, run `npm run dev -- --host 127.0.0.1 --port 5173`, then open `http://127.0.0.1:8000`. Keep both terminal processes running. This is a local preview setup; use a managed MySQL database and a supported, patched PHP runtime for deployment.

## Optional integrations

- Email verification uses Microsoft 365 OAuth mail configuration in `config/services.php`. Configure `MICROSOFT_TENANT_ID`, `MICROSOFT_CLIENT_ID`, `MICROSOFT_CLIENT_SECRET`, `MICROSOFT_MAIL_USERNAME`, and `MICROSOFT_MAIL_FROM` in `.env` to send real mail. Without these credentials, use the log mailer for local development (`MAIL_MAILER=log`) and inspect `storage/logs/laravel.log`.
- Voice-over generation and Pusher broadcasting require their respective external service credentials. They are not needed for the basic local boot.
- Course media and SCORM uploads use Laravel's public storage disk. `php artisan storage:link` makes those uploads available through `public/storage`.

## Useful commands

```sh
php artisan route:list
php artisan migrate:status
php artisan test
npm run build
```

Tests default to the configured database unless you enable SQLite in `phpunit.xml` and provide a test database. Do not point tests at a database containing data you need to keep.

## Project map

- `routes/web.php`: public, learner, and authenticated application routes
- `app/Http/Controllers/`: request handling and LMS workflows
- `app/Models/`: Eloquent entities
- `database/migrations/`: database schema; 45 migrations are currently present
- `database/seeders/`: role, permission, accreditation, and default-user seed logic
- `resources/js/Pages/`: Inertia/Vue screens
- `resources/js/app.js`, `vite.config.js`: Vue/Vite entry and build configuration
- `storage/app/public/`: uploaded course assets and SCORM content
