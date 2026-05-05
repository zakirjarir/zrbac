<?php

namespace Zakirjarir\RbacAutomator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallRbac extends Command
{
    protected $signature = 'rbac:install';
    protected $description = 'Install the RBAC Automator package as a scaffold';

    public function handle()
    {
        $this->info('🚀 Starting RBAC Scaffolding Installation...');

        $namespace = $this->getAppNamespace();

        // 1. Copy Models
        $this->publishStub('Models/Module.stub', app_path('Models/Module.php'), ['namespace' => $namespace]);
        $this->publishStub('Models/Role.stub', app_path('Models/Role.php'), ['namespace' => $namespace]);
        $this->publishStub('Models/Permission.stub', app_path('Models/Permission.php'), ['namespace' => $namespace]);

        // 2. Copy Middleware
        $this->ensureDirectoryExists(app_path('Http/Middleware'));
        $this->publishStub('Middleware/CheckPermission.stub', app_path('Http/Middleware/CheckPermission.php'), ['namespace' => $namespace]);

        // 3. Copy Trait
        $this->ensureDirectoryExists(app_path('Traits'));
        $this->publishStub('Traits/HasRbac.stub', app_path('Traits/HasRbac.php'), ['namespace' => $namespace]);

        // 4. Copy Controllers
        $this->ensureDirectoryExists(app_path('Http/Controllers/Rbac'));
        $this->publishStub('Controllers/RoleController.stub', app_path('Http/Controllers/Rbac/RoleController.php'), ['namespace' => $namespace]);
        $this->publishStub('Controllers/ModuleController.stub', app_path('Http/Controllers/Rbac/ModuleController.php'), ['namespace' => $namespace]);

        // 5. Copy Views
        $this->ensureDirectoryExists(resource_path('views/rbac/roles'));
        $this->ensureDirectoryExists(resource_path('views/rbac/modules'));
        $this->publishStub('views/layout.stub', resource_path('views/rbac/layout.blade.php'));
        $this->publishStub('views/roles/index.stub', resource_path('views/rbac/roles/index.blade.php'));

        // 6. Copy Migration
        $this->copyMigration();

        // 7. Append Routes
        $this->appendRoutes();

        $this->info('✅ RBAC Scaffolding completed successfully!');
        $this->warn('Next steps:');
        $this->line('1. Run "php artisan migrate"');
        $this->line('2. Open /rbac/dashboard and use the "Sync to Seeder" button to generate your seeder.');
    }

    protected function publishStub($stubPath, $destPath, $replacements = [])
    {
        $stubFile = __DIR__ . '/../../../stubs/' . $stubPath;
        if (!File::exists($stubFile)) {
            $this->error('Stub not found: ' . $stubFile);
            return;
        }

        $stub = File::get($stubFile);

        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{' . $key . '}}', $value, $stub);
        }

        File::put($destPath, $stub);
        $this->line('   Created: ' . str_replace(base_path(), '', $destPath));
    }

    protected function ensureDirectoryExists($path)
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    protected function getAppNamespace()
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
            if ($path === 'app/') {
                return $namespace;
            }
        }
        return 'App\\';
    }

    protected function copyMigration()
    {
        $migrationFile = '2024_01_01_000001_create_rbac_tables.php';
        $path = database_path('migrations/' . $migrationFile);
        
        if (!File::exists($path)) {
            $stub = File::get(__DIR__ . '/../../../stubs/migrations/2024_01_01_000001_create_rbac_tables.stub');
            File::put($path, $stub);
            $this->line('   Created: Migration ' . $migrationFile);
        }
    }

    protected function appendRoutes()
    {
        $routeFile = base_path('routes/web.php');
        $routes = "\n\n// RBAC Routes\nRoute::group(['prefix' => 'rbac', 'as' => 'rbac.', 'namespace' => 'App\Http\Controllers\Rbac', 'middleware' => ['web', 'auth']], function() {\n    Route::get('/dashboard', function() { return view('rbac.layout'); })->name('dashboard');\n    Route::resource('roles', 'RoleController');\n    Route::get('/modules', 'ModuleController@index')->name('modules.index');\n    Route::post('/modules', 'ModuleController@store')->name('modules.store');\n    Route::post('/modules/{module}/permissions', 'ModuleController@addPermission')->name('modules.permissions.store');\n    Route::post('/generate-seeder', 'ModuleController@generateSeeder')->name('generate-seeder');\n});\n";
        
        if (!Str::contains(File::get($routeFile), 'RBAC Routes')) {
            File::append($routeFile, $routes);
            $this->line('   Updated: routes/web.php');
        }
    }
}
