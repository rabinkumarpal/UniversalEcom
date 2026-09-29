<?php

namespace Packages\WhatsAppNotifications\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Packages\WhatsAppNotifications\Models\CommunicationTemplate;
use Packages\WhatsAppNotifications\Models\NotificationLog;
use Packages\WhatsAppNotifications\Services\CommunicationDispatchService;

class AdminCommunicationController extends Controller
{
    public function __construct(
        protected CommunicationDispatchService $dispatchService
    ) {}

    /**
     * Display the Communications Ledger & Delivery Analytics.
     */
    public function index(Request $request): View
    {
        $query = NotificationLog::query()->latest('id');

        if ($channel = $request->input('channel')) {
            if ($channel !== 'all') {
                $query->where('channel', $channel);
            }
        }

        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('recipient_phone', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('gateway_message_id', 'like', "%{$search}%")
                    ->orWhere('rendered_message', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(15)->withQueryString();

        // Metrics aggregation
        $totalLogs = NotificationLog::count();
        $deliveredCount = NotificationLog::whereIn('status', ['delivered', 'read'])->count();
        $readCount = NotificationLog::where('status', 'read')->count();
        $failedCount = NotificationLog::where('status', 'failed')->count();

        $deliveryRate = $totalLogs > 0 ? round(($deliveredCount / $totalLogs) * 100, 1) : 100.0;
        $readRate = $deliveredCount > 0 ? round(($readCount / $deliveredCount) * 100, 1) : 0.0;

        $templates = CommunicationTemplate::where('is_active', true)->get();

        return view('whatsapp-notifications::admin.index', compact(
            'logs',
            'totalLogs',
            'deliveredCount',
            'readCount',
            'failedCount',
            'deliveryRate',
            'readRate',
            'templates'
        ));
    }

    /**
     * Display Communication Templates management view.
     */
    public function templates(Request $request): View
    {
        // Seed default templates if empty
        if (CommunicationTemplate::count() === 0) {
            foreach (CommunicationTemplate::defaultTemplates() as $key => $tpl) {
                CommunicationTemplate::create([
                    'template_key' => $key,
                    'channel' => $tpl['channel'] ?? 'whatsapp',
                    'name' => $tpl['name'],
                    'content' => $tpl['content'],
                    'variables' => $tpl['variables'],
                    'is_active' => true,
                ]);
            }
        }

        $templates = CommunicationTemplate::orderBy('id')->get();

        return view('whatsapp-notifications::admin.templates', compact('templates'));
    }

    /**
     * Update an existing communication template.
     */
    public function updateTemplate(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'channel' => 'required|string|in:whatsapp,sms',
            'content' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $template = CommunicationTemplate::findOrFail($id);
        $template->update([
            'name' => $validated['name'],
            'channel' => $validated['channel'],
            'content' => $validated['content'],
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : false,
        ]);

        return redirect()->route('admin.communications.whatsapp.templates')
            ->with('success', "Template '{$template->name}' updated successfully.");
    }

    /**
     * Dispatch a test notification message from the admin portal.
     */
    public function sendTest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_phone' => 'required|string',
            'template_key' => 'required|string',
            'recipient_name' => 'nullable|string',
        ]);

        $phone = $validated['recipient_phone'];
        $name = $validated['recipient_name'] ?: 'Test User';
        $templateKey = $validated['template_key'];

        $sampleParams = [
            'customer_name' => $name,
            'order_number' => 'TEST-ORD-'.rand(1000, 9999),
            'total_amount' => '12,450.00',
            'payment_status' => 'Paid (Online UPI)',
            'tracking_url' => url('/track/TEST-TRK-'.rand(100, 999)),
            'tracking_number' => 'TEST-TRK-'.rand(100, 999),
            'driver_name' => 'Mukesh Sharma (Fleet)',
            'driver_phone' => '+919876543210',
            'otp' => (string) rand(100000, 999999),
            'recipient_name' => $name,
            'delivered_at' => now()->format('d M Y, h:i A'),
            'receipt_url' => url('/shipments/test-pod'),
            'exception_code' => 'ACCESS_BLOCKED',
            'notes' => 'Construction site gate closed for lunch break.',
        ];

        $log = $this->dispatchService->send($phone, $templateKey, $sampleParams, [
            'recipient_name' => $name,
        ]);

        return redirect()->back()->with('success', "Test message dispatched successfully! Gateway ID: {$log->gateway_message_id} via {$log->channel}.");
    }
}
