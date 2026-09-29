<?php

namespace App\Core\Registry;

use App\Core\Contracts\PaymentGatewayContract;
use InvalidArgumentException;

class PaymentGatewayRegistry
{
    /**
     * @var array<string, PaymentGatewayContract>
     */
    protected array $gateways = [];

    public function register(PaymentGatewayContract $gateway): void
    {
        $this->gateways[$gateway->id()] = $gateway;
    }

    public function get(string $id): PaymentGatewayContract
    {
        if (! isset($this->gateways[$id])) {
            throw new InvalidArgumentException("Payment gateway [{$id}] is not registered.");
        }

        return $this->gateways[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->gateways[$id]);
    }

    /**
     * @return array<string, PaymentGatewayContract>
     */
    public function all(): array
    {
        return $this->gateways;
    }
}
