<?php

namespace App\Core\Registry;

use App\Core\Contracts\ShippingRateContract;
use InvalidArgumentException;

class ShippingProviderRegistry
{
    /**
     * @var array<string, ShippingRateContract>
     */
    protected array $providers = [];

    protected ?string $defaultProviderId = null;

    public function register(ShippingRateContract $provider, bool $isDefault = false): void
    {
        $this->providers[$provider->id()] = $provider;

        if ($isDefault || $this->defaultProviderId === null) {
            $this->defaultProviderId = $provider->id();
        }
    }

    public function get(?string $id = null): ShippingRateContract
    {
        $target = $id ?? $this->defaultProviderId;

        if (! $target || ! isset($this->providers[$target])) {
            throw new InvalidArgumentException("Shipping provider [{$target}] is not registered.");
        }

        return $this->providers[$target];
    }

    public function has(string $id): bool
    {
        return isset($this->providers[$id]);
    }

    /**
     * @return array<string, ShippingRateContract>
     */
    public function all(): array
    {
        return $this->providers;
    }
}
