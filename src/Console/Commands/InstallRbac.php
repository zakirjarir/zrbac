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

        // ── 5. Views ──────────────────────────────────────────────────────────
        $this->section('Views');
        $this->ensureDirectoryExists(resource_path('views/rbac/roles'));
        $this->ensureDirectoryExists(resource_path('views/rbac/modules'));
        $this->publishStub('views/layout.stub',        resource_path('views/rbac/layout.blade.php'));
        $this->publishStub('views/dashboard.stub',     resource_path('views/rbac/dashboard.blade.php'));
        $this->publishStub('views/roles/index.stub',   resource_path('views/rbac/roles/index.blade.php'));
        $this->publishStub('views/roles/create.stub',  resource_path('views/rbac/roles/create.blade.php'));
        $this->publishStub('views/roles/edit.stub',    resource_path('views/rbac/roles/edit.blade.php'));
        $this->publishStub('views/modules/index.stub', resource_path('views/rbac/modules/index.blade.php'));

        // ── 6. Migration ──────────────────────────────────────────────────────
        $this->section('Migration');
        $this->copyMigration();

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

        // ── Case 1: Routes exist but were written with 'auth' (old bug) ──────
        if (Str::contains($routeContent, 'RBAC Routes')) {
            if (Str::contains($routeContent, "'middleware' => ['web', 'auth']")) {
                $fixed = str_replace(
                    "'middleware' => ['web', 'auth']",
                    "'middleware' => ['web']",
                    $routeContent
                );
                File::put($routeFile, $fixed);
                $this->line("   <fg=green>✔  Fixed:</> Removed 'auth' from RBAC route middleware in routes/web.php");
                $this->fixed++;
            } else {
                $this->line("   <fg=yellow>⊙  Skipped:</> RBAC routes already present in routes/web.php");
                $this->skipped++;
            }
            return;
        }

        // ── Case 2: Routes not present yet — append them ─────────────────────
        $routes = <<<'ROUTES'


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

        File::append($routeFile, $routes);
        $this->line("   <fg=green>✔  Updated:</> routes/web.php");
        $this->created++;
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
