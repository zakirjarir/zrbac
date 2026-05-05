# Z-RBAC (Automated Scaffolding RBAC for Laravel)

Z-RBAC is a powerful, scaffolding-style Role-Based Access Control package for Laravel. Unlike traditional packages, Z-RBAC copies all logic (Models, Controllers, Views) into your project, giving you full control over the code.

## ✨ Features

- **🚀 Scaffolding Mode**: Copies all files to your project so you can modify them easily.
- **📦 Module-based Permissions**: Group your permissions by modules (e.g., Inventory, Sales, HR).
- **🖥️ Premium Admin Dashboard**: A beautiful, vanilla CSS dashboard with sidebar dropdowns and profile sections.
- **🔄 One-Click Seeder**: Manage everything via GUI and click "Sync to Seeder" to automatically generate `RbacSeeder.php`.
- **🛡️ Custom Middleware**: Protect your routes using `permission` or `module` middleware.
- **💎 Clean Design**: Built with modern Inter typography and micro-animations.

## 🛠️ Installation

1. Add the repository to your `composer.json`:
```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/zakirjarir/zrbac"
    }
],
```

2. Install the package via composer:
```bash
composer require zakirjarir/zrbac:dev-main
```

3. Run the scaffolding installer:
```bash
php artisan rbac:install
```

4. Run the database migrations:
```bash
php artisan migrate
```

## 📖 Usage

### 1. Setup User Model
Add the `HasRbac` trait to your `User` model:
```php
use App\Traits\HasRbac;

class User extends Authenticatable
{
    use HasRbac;
}
```

### 2. Access the Dashboard
Navigate to `/rbac/dashboard` in your browser. From here, you can:
- Create Modules and Permissions.
- Create Roles and assign permissions.
- **Sync to Seeder**: Click the green button to generate a seeder for your production setup.

### 3. Protect Routes
Use the middleware in your `routes/web.php`:
```php
Route::middleware(['auth', 'permission:create_post'])->group(function () {
    // Your routes here
});
```

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
