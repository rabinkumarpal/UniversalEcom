<?php

namespace Packages\DomainConstruction;

use App\Core\Contracts\AddonContext;
use App\Core\Contracts\AddonInterface;

class DomainConstructionAddon implements AddonInterface
{
    public function id(): string
    {
        return 'domain-construction';
    }

    public function name(): string
    {
        return 'Construction Materials Domain Pack';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Reference vertical for heavy materials, bulk volume pricing, local delivery slots, and construction attributes.';
    }

    public function isEnabled(): bool
    {
        return (bool) config('addons.domain_construction.enabled', true);
    }

    public function boot(AddonContext $context): void
    {
        // Domain pack boot logic
    }
}
