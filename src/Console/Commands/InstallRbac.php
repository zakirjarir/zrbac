<?php

namespace Zakirjarir\RbacAutomator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallRbac extends Command
{
    protected $signature = 'rbac:install
                            {--force : Overwrite existing files without asking}
                            {--fix   : Only fix known issues (routes auth problem) without reinstalling files}';

    protected $description = 'Install the RBAC Automator package scaffold into your Laravel project';

    /** Track stats */
    protected int $created   = 0;
    protected int $skipped   = 0;
    protected int $fixed     = 0;

    public function handle(): int
    {
        $this->info('');
        $this->info('🚀 Starting RBAC Scaffolding Installation...');
        $this->info('');

        // --fix mode: only repair known issues, do not reinstall files
        if ($this->option('fix')) {
            $this->fixExistingInstall();
            return self::SUCCESS;
        }

        $namespace = $this->getAppNamespace();

        // ── 1. Models ────────────────────────────────────────────────────────
        $this->section('Models');
        $this->ensureDirectoryExists(app_path('Models'));
        $this->publishStub('Models/Module.stub',     app_path('Models/Module.php'),     ['namespace' => $namespace]);
        $this->publishStub('Models/Role.stub',       app_path('Models/Role.php'),       ['namespace' => $namespace]);
        $this->publishStub('Models/Permission.stub', app_path('Models/Permission.php'), ['namespace' => $namespace]);

        // ── 2. Middleware ─────────────────────────────────────────────────────
        $this->section('Middleware');
        $this->ensureDirectoryExists(app_path('Http/Middleware'));
        $this->publishStub('Middleware/CheckPermission.stub', app_path('Http/Middleware/CheckPermission.php'), ['namespace' => $namespace]);

        // ── 3. Traits ─────────────────────────────────────────────────────────
        $this->section('Traits');
        $this->ensureDirectoryExists(app_path('Traits'));
        $this->publishStub('Traits/HasRbac.stub', app_path('Traits/HasRbac.php'), ['namespace' => $namespace]);

        // ── 4. Controllers ────────────────────────────────────────────────────
        $this->section('Controllers');
        $this->ensureDirectoryExists(app_path('Http/Controllers/Rbac'));
        $this->publishStub('Controllers/RoleController.stub',   app_path('Http/Controllers/Rbac/RoleController.php'),   ['namespace' => $namespace]);
        $this->publishStub('Controllers/ModuleController.stub', app_path('Http/Controllers/Rbac/ModuleController.php'), ['namespace' => $namespace]);

        // ── 4.5. Auth Scaffolding ─────────────────────────────────────────────
        $this->section('Auth Scaffolding');
        $this->ensureDirectoryExists(app_path('Http/Controllers/Auth'));
        $this->publishStub('Controllers/Auth/AuthController.stub', app_path('Http/Controllers/Auth/AuthController.php'), ['namespace' => $namespace]);
        $this->publishStub('Controllers/Auth/PasswordResetController.stub', app_path('Http/Controllers/Auth/PasswordResetController.php'), ['namespace' => $namespace]);
        
        $this->ensureDirectoryExists(resource_path('views/auth/passwords'));
        $this->publishStub('views/auth/layout.stub', resource_path('views/auth/layout.blade.php'));
        $this->publishStub('views/auth/login.stub', resource_path('views/auth/login.blade.php'));
        $this->publishStub('views/auth/register.stub', resource_path('views/auth/register.blade.php'));
        $this->publishStub('views/auth/passwords/email.stub', resource_path('views/auth/passwords/email.blade.php'));
        $this->publishStub('views/auth/passwords/reset.stub', resource_path('views/auth/passwords/reset.blade.php'));

        // ── 5. Views ──────────────────────────────────────────────────────────
        $this->section('Views');
        $this->ensureDirectoryExists(resource_path('views/rbac/roles'));
        $this->ensureDirectoryExists(resource_path('views/rbac/modules'));
        $this->publishStub('views/layout.stub',        resource_path('views/rbac/layout.blade.php'));
        $this->publishStub('views/dashboard.stub',     resource_path('views/rbac/dashboard.blade.php'), ['namespace' => $namespace]);
        $this->publishStub('views/roles/index.stub',   resource_path('views/rbac/roles/index.blade.php'));
        $this->publishStub('views/roles/create.stub',  resource_path('views/rbac/roles/create.blade.php'));
        $this->publishStub('views/roles/edit.stub',    resource_path('views/rbac/roles/edit.blade.php'));
        $this->publishStub('views/modules/index.stub', resource_path('views/rbac/modules/index.blade.php'));

        // ── 6. Migration ──────────────────────────────────────────────────────
        $this->section('Migration');
        $this->copyMigration();

        // ── 6.5 User Model Update ──────────────────────────────────────────────
        $this->section('User Model Update');
        $this->injectHasRbacTrait($namespace);

        // ── 7. Routes ─────────────────────────────────────────────────────────
        $this->section('Routes');
        $this->appendRoutes();

        // ── Summary ───────────────────────────────────────────────────────────
        $this->info('');
        $this->info("✅  Installation complete — Created: {$this->created} | Skipped: {$this->skipped} | Fixed: {$this->fixed}");
        $this->info('');
        $this->warn('📋  Next steps:');
        $this->line('  1. Run  → php artisan migrate');
        $this->line('  2. Visit → /rbac/dashboard');
        $this->line('  3. Use the "Sync to Seeder" button to generate your seeder file.');
        $this->info('');

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Core publish helper
    // ─────────────────────────────────────────────────────────────────────────

    protected function publishStub(string $stubPath, string $destPath, array $replacements = []): void
    {
        $stubFile = __DIR__ . '/../../../stubs/' . $stubPath;

        if (!File::exists($stubFile)) {
            $this->warn("   ⚠  Stub not found, skipping: {$stubPath}");
            $this->skipped++;
            return;
        }

        // File already exists?
        if (File::exists($destPath)) {
            if (!$this->option('force')) {
                $rel = $this->relativePath($destPath);
                $this->line("   <fg=yellow>⊙  Skipped (already exists):</> {$rel}   <fg=gray>[use --force to overwrite]</>");
                $this->skipped++;
                return;
            }
        }

        $stub = File::get($stubFile);

        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{' . $key . '}}', $value, $stub);
        }

        File::put($destPath, $stub);
        $this->line("   <fg=green>✔  Created:</> " . $this->relativePath($destPath));
        $this->created++;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Directory helper
    // ─────────────────────────────────────────────────────────────────────────

    protected function ensureDirectoryExists(string $path): void
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
            $this->line("   <fg=cyan>✔  Directory created:</> " . $this->relativePath($path));
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Namespace detection
    // ─────────────────────────────────────────────────────────────────────────

    protected function getAppNamespace(): string
    {
        try {
            $composer = json_decode(File::get(base_path('composer.json')), true);

            foreach ($composer['autoload']['psr-4'] ?? [] as $namespace => $path) {
                if ($path === 'app/' || $path === 'app') {
                    // Ensure trailing backslash
                    return rtrim($namespace, '\\') . '\\';
                }
            }
        } catch (\Throwable $e) {
            $this->warn('   ⚠  Could not read composer.json, defaulting namespace to App\\');
        }

        return 'App\\';
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Migration
    // ─────────────────────────────────────────────────────────────────────────

    protected function copyMigration(): void
    {
        $migrationFile = '2024_01_01_000001_create_rbac_tables.php';
        $destPath      = database_path('migrations/' . $migrationFile);
        $stubFile      = __DIR__ . '/../../../stubs/migrations/2024_01_01_000001_create_rbac_tables.stub';

        if (!File::exists($stubFile)) {
            $this->warn('   ⚠  Migration stub not found, skipping.');
            return;
        }

        if (File::exists($destPath)) {
            $this->line("   <fg=yellow>⊙  Skipped (already exists):</> database/migrations/{$migrationFile}");
            $this->skipped++;
            return;
        }

        File::put($destPath, File::get($stubFile));
        $this->line("   <fg=green>✔  Created:</> database/migrations/{$migrationFile}");
        $this->created++;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Routes
    // ─────────────────────────────────────────────────────────────────────────

    protected function appendRoutes(): void
    {
        $routeFile    = base_path('routes/web.php');
        $routeContent = File::get($routeFile);
        $updated = false;

        // ── Case 1: Routes exist but were written with 'auth' (old bug) ──────
        if (Str::contains($routeContent, "'middleware' => ['web', 'auth']") && Str::contains($routeContent, 'RBAC Routes')) {
            $routeContent = str_replace(
                "'middleware' => ['web', 'auth']",
                "'middleware' => ['web']",
                $routeContent
            );
            $this->line("   <fg=green>✔  Fixed:</> Removed 'auth' from RBAC route middleware in routes/web.php");
            $this->fixed++;
            $updated = true;
        }

        // ── Append Auth Routes if missing ────────────────────────────────────
        if (!Str::contains($routeContent, 'Authentication Routes')) {
            $authRoutes = <<<'ROUTES'


// ============================================================
// Authentication Routes (added by zakirjarir/rbac-automator)
// ============================================================
Route::get('login', 'App\Http\Controllers\Auth\AuthController@showLoginForm')->name('login');
Route::post('login', 'App\Http\Controllers\Auth\AuthController@login');
Route::get('register', 'App\Http\Controllers\Auth\AuthController@showRegistrationForm')->name('register');
Route::post('register', 'App\Http\Controllers\Auth\AuthController@register');
Route::post('logout', 'App\Http\Controllers\Auth\AuthController@logout')->name('logout');
Route::get('password/reset', 'App\Http\Controllers\Auth\PasswordResetController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'App\Http\Controllers\Auth\PasswordResetController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'App\Http\Controllers\Auth\PasswordResetController@showResetForm')->name('password.reset');
Route::post('password/reset', 'App\Http\Controllers\Auth\PasswordResetController@reset')->name('password.update');
ROUTES;
            $routeContent .= $authRoutes;
            $this->line("   <fg=green>✔  Updated:</> Added Auth routes to routes/web.php");
            $this->created++;
            $updated = true;
        } else {
            $this->line("   <fg=yellow>⊙  Skipped:</> Auth routes already present");
            $this->skipped++;
        }

        // ── Append RBAC Routes if missing ────────────────────────────────────
        if (!Str::contains($routeContent, 'RBAC Routes')) {
            $rbacRoutes = <<<'ROUTES'


// ============================================================
// RBAC Routes  (added by zakirjarir/rbac-automator)
// TIP: Add 'auth' to the middleware array if you want to
//      require authentication to access the RBAC dashboard.
// ============================================================
Route::group([
    'prefix'     => 'rbac',
    'as'         => 'rbac.',
    'namespace'  => 'App\Http\Controllers\Rbac',
    'middleware' => ['web'],
], function () {
    Route::get('/dashboard', function () { return view('rbac.dashboard'); })->name('dashboard');
    Route::resource('roles', 'RoleController');
    Route::get('/modules',                        'ModuleController@index')->name('modules.index');
    Route::post('/modules',                       'ModuleController@store')->name('modules.store');
    Route::delete('/modules/{module}',            'ModuleController@destroy')->name('modules.destroy');
    Route::post('/modules/{module}/permissions',  'ModuleController@addPermission')->name('modules.permissions.store');
    Route::delete('/permissions/{permission}',    'ModuleController@destroyPermission')->name('permissions.destroy');
    Route::post('/generate-seeder',               'ModuleController@generateSeeder')->name('generate-seeder');
});
ROUTES;
            $routeContent .= $rbacRoutes;
            $this->line("   <fg=green>✔  Updated:</> Added RBAC routes to routes/web.php");
            $this->created++;
            $updated = true;
        } else {
            $this->line("   <fg=yellow>⊙  Skipped:</> RBAC routes already present");
            $this->skipped++;
        }

        if ($updated) {
            File::put($routeFile, $routeContent);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Trait Injection
    // ─────────────────────────────────────────────────────────────────────────

    protected function injectHasRbacTrait(string $namespace): void
    {
        $userModelPath = app_path('Models/User.php');
        
        if (!File::exists($userModelPath)) {
            $this->warn("   ⚠  User model not found at app/Models/User.php. Please add HasRbac trait manually.");
            $this->skipped++;
            return;
        }

        $content = File::get($userModelPath);

        if (Str::contains($content, 'use HasRbac;')) {
            $this->line("   <fg=yellow>⊙  Skipped:</> HasRbac trait already exists in User model");
            $this->skipped++;
            return;
        }

        // Add the use statement at the top if not exists
        $traitImport = "use {$namespace}Traits\HasRbac;";
        if (!Str::contains($content, $traitImport)) {
            $content = preg_replace('/(namespace .*?;)/', "$1\n\n{$traitImport}", $content);
        }

        // Add HasRbac inside the class use statement
        $content = preg_replace('/(use HasApiTokens, HasFactory, Notifiable;)/', "$1\n    use HasRbac;", $content);
        
        // Fallback if standard Laravel traits are missing or modified
        if (!Str::contains($content, 'use HasRbac;')) {
            $content = preg_replace('/(class User extends Authenticatable\s*\{)/', "$1\n    use HasRbac;\n", $content);
        }

        File::put($userModelPath, $content);
        $this->line("   <fg=green>✔  Updated:</> Injected HasRbac trait into User model");
        $this->fixed++;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Fix-only mode  (php artisan rbac:install --fix)
    // ─────────────────────────────────────────────────────────────────────────

    protected function fixExistingInstall(): void
    {
        $this->info('🔧 Running fix mode...');
        $this->info('');

        $routeFile    = base_path('routes/web.php');
        $routeContent = File::get($routeFile);

        if (Str::contains($routeContent, "'middleware' => ['web', 'auth']")) {
            $fixed = str_replace(
                "'middleware' => ['web', 'auth']",
                "'middleware' => ['web']",
                $routeContent
            );
            File::put($routeFile, $fixed);
            $this->line("   <fg=green>✔  Fixed:</> Removed 'auth' from RBAC route middleware.");
        } else {
            $this->line("   <fg=yellow>⊙  Nothing to fix:</> routes/web.php looks clean.");
        }

        $this->info('');
        $this->info('✅  Fix complete. Try visiting /rbac/dashboard again.');
        $this->info('');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Utilities
    // ─────────────────────────────────────────────────────────────────────────

    protected function relativePath(string $absolutePath): string
    {
        return str_replace(base_path() . '/', '', $absolutePath);
    }

    protected function section(string $title): void
    {
        $this->line("  <fg=blue;options=bold>── {$title}</>");
    }
}
