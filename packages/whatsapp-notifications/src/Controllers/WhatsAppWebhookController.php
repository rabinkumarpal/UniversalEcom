<?php

namespace Packages\WhatsAppNotifications\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Packages\WhatsAppNotifications\Models\NotificationLog;

class WhatsAppWebhookController extends Controller
{
    /**
     * Ingest two-way status receipts (sent, delivered, read, failed).
     * Supports both direct flat payload and Meta Cloud Webhook structures.
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Check for Meta Cloud Webhook nested payload
        $messageId = null;
        $status = null;
        $timestamp = null;

        if ($request->has('entry')) {
            $statusObj = $request->input('entry.0.changes.0.value.statuses.0');
            if ($statusObj) {
                $messageId = $statusObj['id'] ?? null;
                $status = $statusObj['status'] ?? null;
                $timestamp = isset($statusObj['timestamp']) ? Carbon::createFromTimestamp($statusObj['timestamp']) : now();
            }
        }

        // 2. Direct format fallback
        if (! $messageId) {
            $messageId = $request->input('message_id') ?? $request->input('id');
            $status = $request->input('status');
            $timestamp = $request->input('timestamp') ? Carbon::parse($request->input('timestamp')) : now();
        }

        if (! $messageId || ! $status) {
            return response()->json([
                'status' => 'ignored',
                'message' => 'Missing message ID or status in payload.',
            ], 400);
        }

        $log = NotificationLog::where('gateway_message_id', $messageId)->first();

        if (! $log) {
            return response()->json([
                'status' => 'not_found',
                'message' => "Message ID [{$messageId}] not found in notification registry.",
            ], 404);
        }

        // Process status transitions
        if ($status === 'delivered') {
            $log->markDelivered($timestamp);
        } elseif ($status === 'read') {
            if (! $log->delivered_at) {
                $log->markDelivered($timestamp);
            }
            $log->markRead($timestamp);
        } elseif ($status === 'failed') {
            $error = $request->input('error') ?? $request->input('error_message') ?? 'Gateway delivery failure';
            $log->markFailed(is_array($error) ? json_encode($error) : (string) $error);
        } else {
            $log->update(['status' => $status]);
        }

        return response()->json([
            'status' => 'success',
            'message_id' => $messageId,
            'current_status' => $log->fresh()->status,
        ]);
    }
}
