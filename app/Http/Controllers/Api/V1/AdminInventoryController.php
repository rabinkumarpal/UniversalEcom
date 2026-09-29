<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Services\AuditService;
use App\Domain\Inventory\InventoryService;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AuditService $audit
    ) {}

    public function index(): JsonResponse
    {
        $items = InventoryItem::with(['variant.product', 'warehouse'])
            ->orderBy('id')
            ->paginate(20);

        return response()->json([
            'data' => $items,
        ]);
    }

    public function adjust(Request $request, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'quantity_change' => 'required|integer',
            'reason' => 'required|string|max:255',
        ]);

        $item = InventoryItem::findOrFail($itemId);
        $oldStock = $item->on_hand;

        $movement = $this->inventoryService->adjustStock(
            $item,
            $validated['quantity_change'],
            'adjustment',
            $validated['reason'],
            $request->user()?->id
        );

        $item->refresh();

        $this->audit->stockAdjusted(
            $item,
            ['on_hand' => $oldStock],
            ['on_hand' => $item->on_hand, 'change' => $validated['quantity_change'], 'reason' => $validated['reason']]
        );

        return response()->json([
            'data' => $item,
            'meta' => [
                'message' => 'Stock adjusted successfully.',
                'movement_id' => $movement->id,
            ],
        ]);
    }
}
