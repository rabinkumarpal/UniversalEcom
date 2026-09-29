<?php

namespace App\Core\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SettingService
{
    /**
     * @var array<string, mixed>
     */
    protected array $cache = [];

    /**
     * Get a setting value with caching and config fallback.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        if (Schema::hasTable('system_settings')) {
            $record = SystemSetting::where('key', $key)->first();
            if ($record !== null) {
                return $this->cache[$key] = $record->value;
            }
        }

        // Check config fallback
        $configVal = config($key);
        if ($configVal !== null) {
            return $this->cache[$key] = $configVal;
        }

        return $this->cache[$key] = $default;
    }

    /**
     * Set a persistent setting.
     */
    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        $this->cache[$key] = $value;

        if (Schema::hasTable('system_settings')) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }
    }

    /**
     * Check if a setting exists in the database.
     */
    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->cache)) {
            return true;
        }

        if (Schema::hasTable('system_settings')) {
            return SystemSetting::where('key', $key)->exists();
        }

        return false;
    }

    /**
     * Get all settings, optionally filtered by group.
     *
     * @return array<string, mixed>
     */
    public function all(?string $group = null): array
    {
        if (! Schema::hasTable('system_settings')) {
            return [];
        }

        $query = SystemSetting::query();
        if ($group !== null) {
            $query->where('group', $group);
        }

        return $query->pluck('value', 'key')->toArray();
    }

    /**
     * Determine if an add-on is currently enabled.
     */
    public function isAddonEnabled(string $addonId): bool
    {
        $kebab = Str::kebab($addonId);
        $snake = Str::snake($addonId);

        // Check persistent settings first
        $keys = [
            "addon.{$kebab}.enabled",
            "addon.{$snake}.enabled",
            "addons.{$snake}.enabled",
            "{$kebab}.enabled",
        ];

        foreach ($keys as $key) {
            if (array_key_exists($key, $this->cache)) {
                return (bool) filter_var($this->cache[$key], FILTER_VALIDATE_BOOLEAN);
            }

            if (Schema::hasTable('system_settings')) {
                $record = SystemSetting::where('key', $key)->first();
                if ($record !== null) {
                    $val = filter_var($record->value, FILTER_VALIDATE_BOOLEAN);

                    return $this->cache[$key] = (bool) $val;
                }
            }

            $cfg = config($key);
            if ($cfg !== null) {
                return $this->cache[$key] = (bool) filter_var($cfg, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return true;
    }

    /**
     * Toggle or set the active status of an add-on.
     */
    public function toggleAddon(string $addonId, ?bool $state = null): bool
    {
        $kebab = Str::kebab($addonId);
        $current = $this->isAddonEnabled($addonId);
        $newState = $state ?? ! $current;

        $key = "addon.{$kebab}.enabled";
        $this->set($key, $newState, 'addons');

        return $newState;
    }

    /**
     * Flush memory cache.
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
