<?php

namespace App\Services;

use App\Contracts\PaymentRequestDriver;
use App\Drivers\CashAppLinkDriver;
use App\Drivers\VenmoLinkDriver;

final class PaymentDriverFactory
{
    /**
     * Create a payment driver instance based on payment method
     *
     * @param  string  $method  Payment method: 'cashapp', 'venmo', 'card', etc.
     *
     * @throws \InvalidArgumentException If method is not supported
     */
    public static function make(string $method): PaymentRequestDriver
    {
        return match (strtolower($method)) {
            'cashapp', 'cash_app' => new CashAppLinkDriver,
            'venmo' => new VenmoLinkDriver,
            default => throw new \InvalidArgumentException("Unsupported payment method: {$method}"),
        };
    }

    /**
     * Get all available driver names
     *
     * @return array<string>
     */
    public static function available(): array
    {
        return ['cashapp', 'venmo'];
    }

    /**
     * Check if a payment method is supported
     */
    public static function supports(string $method): bool
    {
        return in_array(strtolower($method), self::available(), true);
    }
}
