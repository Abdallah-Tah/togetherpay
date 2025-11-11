<?php

namespace App\Services\Square;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Manages Square Orders for invoice line items
 * Orders are used as the basis for Square invoices
 */
class SquareOrderService
{
    /**
     * Create an order for a billing charge
     * Returns the order ID to be used in invoice creation
     */
    public function createOrder(User $owner, int $amountCents, string $description = null): string
    {
        if (!$owner->square_location_id) {
            throw new \Exception("Owner does not have a Square location ID");
        }

        $client = new SquareApiClient($owner);

        $idempotencyKey = 'order_' . Str::random(12);

        $payload = [
            'idempotency_key' => $idempotencyKey,
            'order' => [
                'location_id' => $owner->square_location_id,
                'line_items' => [
                    [
                        'name' => $description ?? 'Group contribution',
                        'quantity' => '1',
                        'base_price_money' => [
                            'amount' => $amountCents,
                            'currency' => 'USD',
                        ],
                    ],
                ],
            ],
        ];

        return $client->createOrder($payload);
    }
}
