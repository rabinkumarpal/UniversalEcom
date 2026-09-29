<?php

namespace App\Core\Contracts;

use App\Models\Address;
use App\Models\ProductVariant;

interface TaxCalculatorContract
{
    /**
     * Calculate tax amount for a product variant line based on location and unit price.
     * Returns tax amount in minor units (cents / paise) and tax rate percentage snapshot.
     */
    public function calculateTax(ProductVariant $variant, int $unitPriceInCents, int $quantity, ?Address $destination = null): array;
}
