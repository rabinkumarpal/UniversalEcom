<?php

namespace Packages\PaymentGateways\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayConfig extends Model
{
    protected $table = 'payment_gateway_configs';

    protected $fillable = [
        'gateway_id',
        'credentials',
        'settings',
        'is_active',
        'is_test_mode',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
            'is_test_mode' => 'boolean',
        ];
    }

    /**
     * Get or create Razorpay configuration defaults.
     */
    public static function getRazorpayConfig(): self
    {
        return static::firstOrCreate(
            ['gateway_id' => 'razorpay'],
            [
                'credentials' => [
                    'key_id' => config('services.razorpay.key_id', 'rzp_test_ecom_mock_key_001'),
                    'key_secret' => config('services.razorpay.key_secret', 'rzp_test_secret_mock_999'),
                    'webhook_secret' => config('services.razorpay.webhook_secret', 'rzp_whsec_mock_888'),
                ],
                'settings' => [
                    'display_name' => 'Razorpay (UPI, Cards, Netbanking)',
                    'theme_color' => '#4f46e5',
                    'auto_capture' => true,
                ],
                'is_active' => true,
                'is_test_mode' => true,
            ]
        );
    }

    /**
     * Get or create Advanced Cash on Delivery configuration defaults.
     */
    public static function getCodConfig(): self
    {
        return static::firstOrCreate(
            ['gateway_id' => 'advanced_cod'],
            [
                'credentials' => [],
                'settings' => [
                    'advance_deposit_percentage' => 15, // 15% upfront for high-ticket orders
                    'advance_deposit_threshold' => 1000000, // ₹10,000 in paise (orders >= ₹10,000 require advance)
                    'allow_full_cod_under_threshold' => true,
                ],
                'is_active' => true,
                'is_test_mode' => false,
            ]
        );
    }
}
