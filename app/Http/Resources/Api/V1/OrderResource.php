<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'fulfillment_status' => $this->fulfillment_status,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'delivery_fee' => $this->delivery_fee,
            'grand_total' => $this->grand_total,
            'shipping_address' => $this->shipping_address_snapshot,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'sku' => $item->sku_snapshot,
                'product_name' => $item->product_name_snapshot,
                'variant_name' => $item->variant_name_snapshot,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'discount' => $item->discount,
                'tax' => $item->tax,
                'line_total' => $item->line_total,
            ]),
            'payments' => $this->payments->map(fn ($p) => [
                'id' => $p->id,
                'gateway' => $p->gateway,
                'amount' => $p->amount,
                'status' => $p->status,
            ]),
            'invoice' => $this->invoice ? [
                'invoice_number' => $this->invoice->invoice_number,
                'amount' => $this->invoice->amount,
                'status' => $this->invoice->status,
            ] : null,
        ];
    }
}
