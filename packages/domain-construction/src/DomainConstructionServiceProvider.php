<?php

namespace Packages\DomainConstruction;

use App\Core\Registry\AddonRegistry;
use Illuminate\Support\ServiceProvider;

class DomainConstructionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $addonRegistry = $this->app->make(AddonRegistry::class);
        $addonRegistry->register(new DomainConstructionAddon);
    }
}
