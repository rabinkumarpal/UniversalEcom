<?php

namespace App\Core\Registry;

use App\Core\Contracts\AddonContext;
use App\Core\Contracts\AddonInterface;
use App\Core\Services\SettingService;
use Illuminate\Contracts\Foundation\Application;

class AddonRegistry
{
    /**
     * @var array<string, AddonInterface>
     */
    protected array $addons = [];

    /**
     * @var array<string, bool>
     */
    protected array $booted = [];

    public function __construct(
        protected Application $app
    ) {}

    public function register(AddonInterface $addon): void
    {
        $this->addons[$addon->id()] = $addon;

        // If the application is already booted, boot this addon immediately
        if ($this->app->isBooted() && $addon->isEnabled() && ! isset($this->booted[$addon->id()])) {
            $this->bootAddon($addon);
        }
    }

    public function has(string $id): bool
    {
        return isset($this->addons[$id]);
    }

    public function get(string $id): ?AddonInterface
    {
        return $this->addons[$id] ?? null;
    }

    /**
     * @return array<string, AddonInterface>
     */
    public function all(): array
    {
        return $this->addons;
    }

    /**
     * Check if an add-on is enabled according to persistent settings or its manifest.
     */
    public function isEnabled(string $id): bool
    {
        if (! isset($this->addons[$id])) {
            return false;
        }

        if ($this->app->bound(SettingService::class)) {
            /** @var SettingService $settingService */
            $settingService = $this->app->make(SettingService::class);

            return $settingService->isAddonEnabled($id);
        }

        return $this->addons[$id]->isEnabled();
    }

    /**
     * Check if an add-on has booted in the current request lifecycle.
     */
    public function isBooted(string $id): bool
    {
        return ! empty($this->booted[$id]);
    }

    /**
     * Boot all registered and enabled add-ons.
     */
    public function bootAll(): void
    {
        foreach ($this->addons as $addon) {
            if ($this->isEnabled($addon->id()) && ! isset($this->booted[$addon->id()])) {
                $this->bootAddon($addon);
            }
        }
    }

    protected function bootAddon(AddonInterface $addon): void
    {
        $context = new AddonContext($this->app);
        $addon->boot($context);
        $this->booted[$addon->id()] = true;
    }
}
