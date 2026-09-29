<?php

namespace Packages\VendorMarketplace\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Packages\VendorMarketplace\Models\Vendor;
use Packages\VendorMarketplace\Models\VendorOffer;
use Packages\VendorMarketplace\Models\VendorOrder;
use Packages\VendorMarketplace\Models\VendorOrderItem;

class VendorOrderSplittingService
{
    /**
     * Split a platform order into vendor orders for vendor fulfillment and commission.
     */
    public function splitOrder(Order $order): array
    {
        return DB::transaction(function () use ($order) {
            $createdVendorOrders = [];
            $itemsByVendor = [];

            foreach ($order->items as $item) {
                // Check if this variant was offered by a vendor
                $offer = VendorOffer::where('product_variant_id', $item->product_variant_id)
                    ->where('status', 'approved')
                    ->first();

                if ($offer && $offer->vendor && $offer->vendor->isActive()) {
                    $vendorId = $offer->vendor_id;
                    $itemsByVendor[$vendorId][] = [
                        'item' => $item,
                        'offer' => $offer,
                        'vendor' => $offer->vendor,
                    ];
                }
            }

            // Create a VendorOrder for each vendor involved
            $index = 1;
            foreach ($itemsByVendor as $vendorId => $vendorItems) {
                $vendor = $vendorItems[0]['vendor'];
                $vendorSubtotal = 0;
                $vendorCommission = 0;

                $commissionRate = (float) $vendor->commission_rate_percentage;

                foreach ($vendorItems as $entry) {
                    $item = $entry['item'];
                    $lineTotal = $item->line_total;
                    $vendorSubtotal += $lineTotal;

                    $lineCommission = (int) round($lineTotal * ($commissionRate / 100.0));
                    $vendorCommission += $lineCommission;
                }

                $netPayout = max(0, $vendorSubtotal - $vendorCommission);
                $vendorOrderNumber = 'VO-'.$order->order_number.'-'.sprintf('%02d', $index++);

                $vendorOrder = VendorOrder::create([
                    'vendor_id' => $vendor->id,
                    'order_id' => $order->id,
                    'vendor_order_number' => $vendorOrderNumber,
                    'status' => 'confirmed',
                    'subtotal' => $vendorSubtotal,
                    'commission_amount' => $vendorCommission,
                    'vendor_payout' => $netPayout,
                ]);

                foreach ($vendorItems as $entry) {
                    $item = $entry['item'];
                    $lineTotal = $item->line_total;
                    $itemCommission = (int) round($lineTotal * ($commissionRate / 100.0));

                    VendorOrderItem::create([
                        'vendor_order_id' => $vendorOrder->id,
                        'order_item_id' => $item->id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'commission_amount' => $itemCommission,
                        'payout_amount' => max(0, $lineTotal - $itemCommission),
                    ]);
                }

                $createdVendorOrders[] = $vendorOrder;
            }

            return $createdVendorOrders;
        });
    }
}
