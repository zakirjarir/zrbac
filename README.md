# 🛡️ Z-RBAC (Premium Automated Scaffolding RBAC for Laravel)

> [!CAUTION]
> ### ⚠️ DEVELOPMENT STATUS: UNDER HEAVY DEVELOPMENT
> This package is currently in its **Alpha/Beta stage**. APIs, file structures, and features are subject to change. Use it in production environments at your own risk. Feedback and contributions are welcome!

Z-RBAC is not just another RBAC package. It is a **full-featured scaffolding solution** that injects a premium, ready-to-use administrative layer into your Laravel application. Unlike traditional packages that hide logic inside `vendor/`, Z-RBAC publishes all Models, Controllers, and Views directly into your app, giving you 100% control over the implementation.

---

## ✨ Key Features

- **🚀 Scaffolding Architecture**: Injects logic directly into your app. No more black-box vendor code.
- **🎨 Premium Design System**: A stunning, modern dashboard built with Vanilla CSS. Features include:
    - **Glassmorphism** effects and soft-glow aesthetics.
    - **Dark/Light Mode** support out of the box.
    - **Fully Responsive** layout for Mobile, Tablet, and Desktop.
- **🌍 Dynamic Localization (I18n)**:
    - Manage languages directly from the GUI.
    - Automated JSON translation file generation.
    - Middleware-based language switching.
- **📦 Hierarchical Module Management**: Organize permissions into logical modules (e.g., *Inventory > Stock Out*).
- **🔄 GUI-to-Seeder Sync**: Design your roles and permissions in the UI and generate a production-ready `RbacSeeder.php` with one click.
- **🛡️ Custom Middleware**: Simple route protection using `permission` or `module` middlewares.
- **⚡ Supercharged UI**: Built with modern typography (Outfit), FontAwesome 6, and smooth micro-animations.

---

## 🛠️ Installation

### 1. Configure Repository
Add the GitHub repository to your `composer.json`:
```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/zakirjarir/zrbac"
    }
],
```

### 2. Install Package
```bash
composer require zakirjarir/zrbac:dev-main
```

### 3. Run the Scaffolder
This command will publish all necessary controllers, models, traits, and views.
```bash
php artisan rbac:install
```

### 4. Database Setup
```bash
php artisan migrate
```

---

## 📖 Initial Configuration

### 1. Update User Model
Add the `HasRbac` trait to your `App\Models\User`:
```php
namespace App\Models;

use App\Traits\HasRbac; // Import the trait
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRbac; // Use the trait
}
```

### 2. Register Middleware
The installer automatically attempts to inject the `SetLocale` middleware. Ensure your `routes/web.php` or `app/Http/Kernel.php` (for older Laravel versions) includes the necessary RBAC middleware.

---

## 🚀 Usage Guide

### Accessing the Dashboard
Go to your browser and visit: `your-app.test/rbac/dashboard`

### Managing Modules
1. Navigate to **Manage Modules**.
2. Create a "Root Module" (e.g., *Sales*).
3. Add "Sub-Modules" (e.g., *Invoices*).
4. Add specific "Permissions" (e.g., *Create Invoice*, *Delete Invoice*).

### Creating Roles
1. Navigate to **Roles Management**.
2. Create a new Role (e.g., *Manager*).
3. Select the permissions you created earlier.

### Protecting Routes
Use the `permission` middleware in your `routes/web.php`:
```php
// Protect a single route
Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('permission:view_invoices');

// Protect a group
Route::middleware(['auth', 'permission:manage_users'])->group(function () {
    Route::resource('users', UserController::class);
});
```

---

## 🔧 Maintenance & Syncing

### Syncing to Production
Once you have configured your roles and permissions in your local environment, click the **"Sync Seeder"** button in the dashboard or run:
```bash
php artisan rbac:generate-seeder
```
This generates `database/seeders/RbacSeeder.php`, which you can use to deploy your RBAC configuration to production.

### Repairing Installation
If you accidentally delete a published file or want to update to the latest stub design:
```bash
php artisan rbac:install --force
```

---

## 🤝 Contributing
Since this is an active development project, contributions are highly encouraged! Please feel free to open issues or submit pull requests on GitHub.

## 📄 License
The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.
