<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeAddonCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'make:addon 
                            {name : The human-readable name of the add-on, e.g. "B2B Wholesale" or "advanced-analytics"}
                            {--description= : Optional summary description of the add-on}';

    /**
     * The console command description.
     */
    protected $description = 'Scaffold a production-ready, portable Composer add-on package adhering to the Plugin Developer Specification';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawName = $this->argument('name');
        $studlyName = Str::studly($rawName);
        $kebabName = Str::kebab($rawName);
        $packageDir = base_path('packages/'.$kebabName);
        $description = $this->option('description') ?: "Portable {$rawName} extension package for Universal Ecommerce Platform.";

        if (File::exists($packageDir)) {
            $this->error("Add-on package directory [packages/{$kebabName}] already exists!");

            return self::FAILURE;
        }

        $this->info("Scaffolding portable add-on [{$studlyName}] in [packages/{$kebabName}]...");

        // Create directories
        File::makeDirectory($packageDir.'/src', 0755, true);
        File::makeDirectory($packageDir.'/config', 0755, true);
        File::makeDirectory($packageDir.'/routes', 0755, true);
        File::makeDirectory($packageDir.'/database/migrations', 0755, true);
        File::makeDirectory($packageDir.'/resources/views', 0755, true);
        File::makeDirectory($packageDir.'/tests/Feature', 0755, true);

        // 1. composer.json
        $composerJson = [
            'name' => "universal-ecom/{$kebabName}",
            'description' => $description,
            'type' => 'library',
            'license' => 'MIT',
            'autoload' => [
                'psr-4' => [
                    "Packages\\{$studlyName}\\" => 'src/',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        "Packages\\{$studlyName}\\{$studlyName}ServiceProvider",
                    ],
                ],
            ],
        ];
        File::put($packageDir.'/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        // 2. Addon class
        $snakeName = Str::snake($studlyName);
        $addonClass = <<<PHP
<?php

namespace Packages\\{$studlyName};

use App\Core\Contracts\AddonContext;
use App\Core\Contracts\EcommerceAddon;

class {$studlyName}Addon implements EcommerceAddon
{
    public function id(): string
    {
        return '{$kebabName}';
    }

    public function name(): string
    {
        return '{$rawName}';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return '{$description}';
    }

    public function isEnabled(): bool
    {
        return (bool) (config('addons.{$snakeName}.enabled', config('{$kebabName}.enabled', true)));
    }

    public function boot(AddonContext \$context): void
    {
        // Custom add-on initialization and contract registrations
    }
}
PHP;
        File::put($packageDir."/src/{$studlyName}Addon.php", $addonClass."\n");

        // 3. ServiceProvider
        $serviceProviderClass = <<<PHP
<?php

namespace Packages\\{$studlyName};

use App\Core\Registry\AddonRegistry;
use Illuminate\Support\ServiceProvider;

class {$studlyName}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        \$this->mergeConfigFrom(__DIR__ . '/../config/{$kebabName}.php', '{$kebabName}');
    }

    public function boot(AddonRegistry \$registry): void
    {
        \$registry->register(new {$studlyName}Addon());

        if (! config('{$kebabName}.enabled', true)) {
            return;
        }

        \$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        \$this->loadViewsFrom(__DIR__ . '/../resources/views', '{$kebabName}');

        if (file_exists(__DIR__ . '/../routes/web.php')) {
            \$this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        if (file_exists(__DIR__ . '/../routes/api.php')) {
            \$this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }
    }
}
PHP;
        File::put($packageDir."/src/{$studlyName}ServiceProvider.php", $serviceProviderClass."\n");

        // 4. Config
        $configContent = <<<PHP
<?php

return [
    'enabled' => env('ADDON_' . strtoupper(str_replace('-', '_', '{$kebabName}')) . '_ENABLED', true),
];
PHP;
        File::put($packageDir."/config/{$kebabName}.php", $configContent."\n");

        // 5. Routes
        $webRouteContent = <<<PHP
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('addons/{$kebabName}')->name('addon.{$kebabName}.')->group(function () {
    Route::get('/', fn () => view('{$kebabName}::index'))->name('index');
});
PHP;
        File::put($packageDir.'/routes/web.php', $webRouteContent."\n");

        $apiRouteContent = <<<PHP
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/addons/{$kebabName}')->group(function () {
    Route::get('/status', fn () => response()->json(['status' => 'active', 'addon' => '{$kebabName}']));
});
PHP;
        File::put($packageDir.'/routes/api.php', $apiRouteContent."\n");

        // 6. View
        $viewContent = <<<BLADE
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }} — {$rawName} Add-on</title>
</head>
<body style="font-family: sans-serif; padding: 2rem; background: #0f172a; color: #f8fafc;">
    <h1>{$rawName} Portable Add-on</h1>
    <p>{$description}</p>
    <p>Version 1.0.0 • Booted successfully via AddonRegistry.</p>
</body>
</html>
BLADE;
        File::put($packageDir.'/resources/views/index.blade.php', $viewContent."\n");

        // 7. README.md
        $readmeContent = <<<MD
# {$rawName} Add-on for Universal Ecommerce Platform

{$description}

## Installation

Add repository path in root `composer.json` or require via Composer:

```bash
composer require universal-ecom/{$kebabName}
```

## Configuration

Publish or customize in `config/{$kebabName}.php`:

```php
return [
    'enabled' => true,
];
```
MD;
        File::put($packageDir.'/README.md', $readmeContent."\n");

        $this->info("✓ Successfully generated add-on [packages/{$kebabName}]!");
        $this->line('  • composer.json');
        $this->line("  • src/{$studlyName}ServiceProvider.php");
        $this->line("  • src/{$studlyName}Addon.php");
        $this->line("  • config/{$kebabName}.php");
        $this->line('  • routes/web.php & api.php');
        $this->line('  • resources/views/index.blade.php');

        return self::SUCCESS;
    }
}
