<?php

namespace Tests\Feature;

use App\Core\Events\OrderPlaced;
use App\Core\Registry\AddonRegistry;
use App\Models\Order;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\DriverLogistics\Models\Driver;
use Packages\DriverLogistics\Services\DriverDispatchService;
use Packages\DriverLogistics\Services\DriverExceptionService;
use Packages\DriverLogistics\Services\DriverPodService;
use Packages\WhatsAppNotifications\Models\NotificationLog;
use Packages\WhatsAppNotifications\Services\CommunicationDispatchService;
use Packages\WhatsAppNotifications\Services\WhatsAppGatewayClient;
use Packages\WhatsAppNotifications\WhatsAppNotificationsAddon;
use Tests\TestCase;

class WhatsAppNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $customerUser;

    protected User $driverUser;

    protected Driver $driver;

    protected Order $sampleOrder;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles & Users
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $driverRole = Role::firstOrCreate(['slug' => 'driver'], ['name' => 'Driver']);

        $this->adminUser = User::factory()->create([
            'name' => 'Communications Admin',
            'email' => 'admin@universal-ecom.test',
        ]);
        $this->adminUser->roles()->attach($adminRole);

        $this->customerUser = User::factory()->create([
            'name' => 'Ramesh Contractor',
            'email' => 'ramesh@example.test',
            'phone' => '9876543210',
        ]);

        $this->driverUser = User::factory()->create([
            'name' => 'Suraj Fleet Driver',
            'email' => 'suraj.driver@example.test',
            'phone' => '9811223344',
        ]);
        $this->driverUser->roles()->attach($driverRole);

        $this->driver = Driver::create([
            'user_id' => $this->driverUser->id,
            'license_number' => 'DL-2026-9999',
            'vehicle_type' => 'Flatbed Tipper Truck',
            'vehicle_number' => 'MH-04-AB-1234',
            'status' => 'available',
        ]);

        // 2. Sample Order
        $this->sampleOrder = Order::create([
            'order_number' => 'ORD-WA-2026-001',
            'user_id' => $this->customerUser->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'INR',
            'subtotal' => 2500000,
            'tax_total' => 450000,
            'delivery_fee' => 50000,
            'discount_total' => 0,
            'grand_total' => 3000000,
            'shipping_address_snapshot' => [
                'first_name' => 'Ramesh',
                'last_name' => 'Contractor',
                'phone' => '9876543210',
                'address_line1' => 'Plot 42, Metro Construction Site',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
            ],
            'billing_address_snapshot' => [
                'first_name' => 'Ramesh',
                'last_name' => 'Contractor',
                'phone' => '9876543210',
                'address_line1' => 'Plot 42, Metro Construction Site',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
            ],
            'placed_at' => now(),
        ]);
    }

    /**
     * Test 1: Addon discovery & registration in AddonRegistry.
     */
    public function test_whatsapp_addon_is_registered_in_addon_registry(): void
    {
        $registry = app(AddonRegistry::class);
        $addon = $registry->get('whatsapp-notifications');

        $this->assertNotNull($addon, 'WhatsApp notifications add-on should be registered in the AddonRegistry.');
        $this->assertInstanceOf(WhatsAppNotificationsAddon::class, $addon);
        $this->assertTrue($addon->isEnabled());
        $this->assertEquals('1.0.0', $addon->version());
    }

    /**
     * Test 2: Order placed event triggers automated Order Confirmation WhatsApp notification.
     */
    public function test_order_placed_triggers_order_confirmation_whatsapp_notification(): void
    {
        // Fire OrderPlaced event
        event(new OrderPlaced($this->sampleOrder));

        // Assert NotificationLog created
        $log = NotificationLog::where('order_id', $this->sampleOrder->id)->first();
        $this->assertNotNull($log, 'A NotificationLog should be created when OrderPlaced fires.');
        $this->assertEquals('+919876543210', $log->recipient_phone);
        $this->assertEquals('whatsapp', $log->channel);
        $this->assertEquals('sent', $log->status);
        $this->assertStringStartsWith('WA-MSG-', $log->gateway_message_id);
        $this->assertStringContainsString('ORD-WA-2026-001', $log->rendered_message);
        $this->assertStringContainsString('30,000.00', $log->rendered_message);
    }

    /**
     * Test 3: Shipment dispatch triggers Delivery OTP WhatsApp notification.
     */
    public function test_shipment_dispatch_triggers_delivery_otp_notification(): void
    {
        $shipment = Shipment::create([
            'order_id' => $this->sampleOrder->id,
            'shipment_number' => 'SHP-WA-001',
            'status' => 'pending',
            'delivery_fee' => 50000,
        ]);

        $dispatchService = app(DriverDispatchService::class);
        $dispatchedShipment = $dispatchService->assignDriverToShipment($shipment, $this->driver);

        $this->assertNotEmpty($dispatchedShipment->delivery_otp);

        $log = NotificationLog::where('shipment_id', $dispatchedShipment->id)
            ->where('template_name', 'Consignment Out for Delivery')
            ->first();

        $this->assertNotNull($log, 'Out for delivery notification should be logged.');
        $this->assertEquals('+919876543210', $log->recipient_phone);
        $this->assertStringContainsString($dispatchedShipment->delivery_otp, $log->rendered_message);
        $this->assertStringContainsString('Suraj Fleet Driver', $log->rendered_message);
        $this->assertStringContainsString($dispatchedShipment->tracking_number, $log->rendered_message);
    }

    /**
     * Test 4: POD completion triggers digital handover receipt notification.
     */
    public function test_pod_completion_triggers_handover_receipt_notification(): void
    {
        $shipment = Shipment::create([
            'order_id' => $this->sampleOrder->id,
            'shipment_number' => 'SHP-WA-002',
            'driver_id' => $this->driver->id,
            'status' => 'out_for_delivery',
            'delivery_otp' => '654321',
            'tracking_number' => 'TRK-TEST-POD-001',
        ]);

        $podService = app(DriverPodService::class);
        $podService->submitProofOfDelivery($shipment, $this->driver, [
            'otp' => '654321',
            'recipient_name' => 'Site Supervisor Verma',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
        ]);

        $log = NotificationLog::where('shipment_id', $shipment->id)
            ->where('template_name', 'Proof of Delivery Completed')
            ->first();

        $this->assertNotNull($log, 'Proof of delivery receipt notification should be logged.');
        $this->assertEquals('+919876543210', $log->recipient_phone);
        $this->assertStringContainsString('Site Supervisor Verma', $log->rendered_message);
        $this->assertStringContainsString('TRK-TEST-POD-001', $log->rendered_message);
    }

    /**
     * Test 5: Delivery exception triggers site alert notification.
     */
    public function test_delivery_exception_triggers_site_alert_notification(): void
    {
        $shipment = Shipment::create([
            'order_id' => $this->sampleOrder->id,
            'shipment_number' => 'SHP-WA-003',
            'driver_id' => $this->driver->id,
            'status' => 'out_for_delivery',
            'delivery_otp' => '123456',
            'tracking_number' => 'TRK-EXCEPTION-001',
        ]);

        $exceptionService = app(DriverExceptionService::class);
        $exceptionService->recordException($shipment, $this->driver, 'SITE_UNREACHABLE', 'Heavy rain caused waterlogging at access gate.');

        $log = NotificationLog::where('shipment_id', $shipment->id)
            ->where('template_name', 'Delivery Exception Alert')
            ->first();

        $this->assertNotNull($log, 'Delivery exception alert notification should be logged.');
        $this->assertStringContainsString('SITE_UNREACHABLE', $log->rendered_message);
        $this->assertStringContainsString('waterlogging at access gate', $log->rendered_message);
    }

    /**
     * Test 6: Phone number normalizer standardizes varied Indian mobile inputs to E.164.
     */
    public function test_phone_number_normalizer_formats_indian_mobile_numbers(): void
    {
        $client = app(WhatsAppGatewayClient::class);

        // 10 digits
        $this->assertEquals('+919876543210', $client->normalizePhone('9876543210'));

        // Formatted with spaces & dashes
        $this->assertEquals('+919876543210', $client->normalizePhone('98765-43210'));
        $this->assertEquals('+919876543210', $client->normalizePhone('(98765) 43210'));

        // Leading zero 11 digits
        $this->assertEquals('+919876543210', $client->normalizePhone('09876543210'));

        // 12 digits with 91
        $this->assertEquals('+919876543210', $client->normalizePhone('919876543210'));

        // Already E.164
        $this->assertEquals('+919876543210', $client->normalizePhone('+919876543210'));
    }

    /**
     * Test 7: Inbound webhook updates message delivery and read status.
     */
    public function test_inbound_webhook_updates_message_delivery_and_read_status(): void
    {
        $log = NotificationLog::create([
            'recipient_phone' => '+919876543210',
            'channel' => 'whatsapp',
            'template_name' => 'Order Confirmation',
            'rendered_message' => 'Hello Ramesh, order confirmed.',
            'gateway_message_id' => 'WA-MSG-WEBHOOK-TEST',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // 1. Send 'delivered' status callback
        $deliveredResponse = $this->postJson(route('webhooks.whatsapp'), [
            'message_id' => 'WA-MSG-WEBHOOK-TEST',
            'status' => 'delivered',
            'timestamp' => now()->toISOString(),
        ]);

        $deliveredResponse->assertOk();
        $deliveredResponse->assertJson(['status' => 'success', 'current_status' => 'delivered']);
        $this->assertEquals('delivered', $log->fresh()->status);
        $this->assertNotNull($log->fresh()->delivered_at);

        // 2. Send 'read' status callback (Meta webhook structure)
        $readResponse = $this->postJson(route('webhooks.whatsapp'), [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'statuses' => [
                                    [
                                        'id' => 'WA-MSG-WEBHOOK-TEST',
                                        'status' => 'read',
                                        'timestamp' => now()->timestamp,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $readResponse->assertOk();
        $readResponse->assertJson(['status' => 'success', 'current_status' => 'read']);
        $this->assertEquals('read', $log->fresh()->status);
        $this->assertNotNull($log->fresh()->read_at);
    }

    /**
     * Test 8: Automatic SMS fallback when WhatsApp channel is disabled.
     */
    public function test_sms_channel_fallback_when_whatsapp_channel_disabled(): void
    {
        $dispatchService = app(CommunicationDispatchService::class);

        // Disable WhatsApp on gateway
        $dispatchService->getGateway()->setWhatsAppEnabled(false);

        $log = $dispatchService->send('9876543210', 'delivery_otp', [
            'otp' => '998877',
            'tracking_number' => 'TRK-SMS-001',
            'order_number' => 'ORD-SMS-001',
        ]);

        $this->assertEquals('sms', $log->channel, 'Notification should gracefully fall back to SMS channel.');
        $this->assertStringStartsWith('SMS-MSG-', $log->gateway_message_id);
        $this->assertStringContainsString('998877', $log->rendered_message);

        // Re-enable for subsequent tests
        $dispatchService->getGateway()->setWhatsAppEnabled(true);
    }

    /**
     * Test 9: Admin can view communications ledger and delivery metrics.
     */
    public function test_admin_can_view_communications_logs_with_delivery_metrics(): void
    {
        NotificationLog::create([
            'recipient_phone' => '+919876543210',
            'recipient_name' => 'Ledger Customer 1',
            'channel' => 'whatsapp',
            'template_name' => 'Order Confirmation',
            'rendered_message' => 'Sample message 1',
            'gateway_message_id' => 'WA-MSG-L1',
            'status' => 'delivered',
            'sent_at' => now(),
            'delivered_at' => now(),
        ]);

        NotificationLog::create([
            'recipient_phone' => '+919811223344',
            'recipient_name' => 'Ledger Customer 2',
            'channel' => 'sms',
            'template_name' => 'Delivery OTP',
            'rendered_message' => 'Sample OTP message',
            'gateway_message_id' => 'SMS-MSG-L2',
            'status' => 'failed',
            'error_message' => 'Network timeout',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.communications.whatsapp.index'));

        $response->assertOk();
        $response->assertSee('WhatsApp &amp; SMS Communications Hub', false);
        $response->assertSee('+919876543210');
        $response->assertSee('+919811223344');
        $response->assertSee('WA-MSG-L1');
        $response->assertSee('SMS-MSG-L2');
    }

    /**
     * Test 10: Admin can dispatch simulated test notification from portal.
     */
    public function test_admin_can_send_test_notification_from_portal(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.communications.whatsapp.test'), [
                'recipient_phone' => '9876543210',
                'recipient_name' => 'Portal Test Contractor',
                'template_key' => 'order_confirmed',
            ]);

        $response->assertSessionHas('success');

        $log = NotificationLog::where('recipient_name', 'Portal Test Contractor')->first();
        $this->assertNotNull($log, 'Test notification log must be created.');
        $this->assertEquals('+919876543210', $log->recipient_phone);
        $this->assertEquals('whatsapp', $log->channel);
        $this->assertEquals('sent', $log->status);
    }

    /**
     * Test 11: Admin can view communication templates configuration page.
     */
    public function test_admin_can_view_templates_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.communications.whatsapp.templates'));

        $response->assertOk();
        $response->assertSee('Communication Templates');
        $response->assertSee('order_confirmed');
    }
}
