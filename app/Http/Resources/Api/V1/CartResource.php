<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Pricing\PricingPipeline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pipeline = app(PricingPipeline::class);
        $totals = $pipeline->calculate($this->resource, null, [
            'coupon_code' => $request->input('coupon_code'),
        ]);

        return [
            'id' => $this->id,
            'session_token' => $this->session_token,
            'items_count' => $this->items->count(),
            'total_quantity' => $this->total_quantity,
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'tax_total' => $totals['tax_total'],
            'delivery_fee' => $totals['delivery_fee'],
            'grand_total' => $totals['grand_total'],
            'free_shipping' => $totals['free_shipping'],
            'applied_promotions' => $totals['applied_promotions'],
            'items' => $totals['lines'],
        ];
    }
}
