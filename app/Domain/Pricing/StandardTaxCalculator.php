<?php

namespace App\Domain\Pricing;

use App\Core\Contracts\TaxCalculatorContract;
use App\Models\Address;
use App\Models\ProductVariant;

class StandardTaxCalculator implements TaxCalculatorContract
{
    /**
     * Authoritatively compute tax for line item.
     */
    public function calculateTax(ProductVariant $variant, int $unitPriceInCents, int $quantity, ?Address $destination = null): array
    {
        $taxClass = $variant->taxClass;
        $rate = $taxClass ? (float) $taxClass->rate_percentage : 0.0;

        $lineSubtotal = $unitPriceInCents * $quantity;
        $taxAmount = (int) round($lineSubtotal * ($rate / 100.0));

        return [
            'rate_percentage' => $rate,
            'tax_amount' => $taxAmount,
            'tax_class_id' => $taxClass?->id,
        ];
    }
}
