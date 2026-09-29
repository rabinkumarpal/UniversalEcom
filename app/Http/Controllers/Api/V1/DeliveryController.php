<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Delivery\DeliveryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService
    ) {}

    public function serviceability(Request $request): JsonResponse
    {
        $pincode = $request->query('pincode', '');
        $isServiceable = $this->deliveryService->isServiceable($pincode);

        return response()->json([
            'data' => [
                'pincode' => $pincode,
                'is_serviceable' => $isServiceable,
            ],
        ]);
    }

    public function slots(Request $request): JsonResponse
    {
        $pincode = $request->query('pincode', '');
        $slots = $this->deliveryService->getAvailableSlots($pincode, $request->query('date'));

        return response()->json([
            'data' => $slots,
        ]);
    }
}
