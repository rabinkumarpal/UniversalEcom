<?php

namespace App\Core\Registry;

use App\Core\Contracts\PricingAdjustmentContract;

class PricingAdjustmentRegistry
{
    /**
     * @var array<string, PricingAdjustmentContract>
     */
    protected array $adjusters = [];

    public function register(PricingAdjustmentContract $adjuster): void
    {
        $this->adjusters[$adjuster->id()] = $adjuster;
    }

    /**
     * Get all registered adjustment providers sorted by priority.
     *
     * @return array<PricingAdjustmentContract>
     */
    public function sorted(): array
    {
        $list = array_values($this->adjusters);
        usort($list, fn (PricingAdjustmentContract $a, PricingAdjustmentContract $b) => $a->priority() <=> $b->priority());

        return $list;
    }

    public function has(string $id): bool
    {
        return isset($this->adjusters[$id]);
    }

    public function get(string $id): ?PricingAdjustmentContract
    {
        return $this->adjusters[$id] ?? null;
    }
}
