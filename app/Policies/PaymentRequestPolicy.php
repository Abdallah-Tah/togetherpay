<?php

namespace App\Policies;

use App\Models\PaymentRequest;
use App\Models\User;

class PaymentRequestPolicy
{
    /**
     * Determine whether the user can view the payment request.
     * Owner can see all requests in their groups.
     * Member can only see their own request.
     */
    public function view(User $user, PaymentRequest $paymentRequest): bool
    {
        // Owner can see all requests in their groups
        if ($paymentRequest->group->owner_id === $user->id) {
            return true;
        }

        // Member can only see their own request
        return $paymentRequest->member->user_id === $user->id;
    }

    /**
     * Member can mark their own payment as paid
     */
    public function markPaid(User $user, PaymentRequest $paymentRequest): bool
    {
        return $paymentRequest->member->user_id === $user->id
            && in_array($paymentRequest->status, ['sent', 'pending'], true);
    }

    /**
     * Owner can confirm payment received
     */
    public function confirm(User $user, PaymentRequest $paymentRequest): bool
    {
        return $paymentRequest->group->owner_id === $user->id
            && $paymentRequest->status === 'member_marked';
    }

    /**
     * Owner can manage (nudge, etc.) payment requests in their groups
     */
    public function manage(User $user, PaymentRequest $paymentRequest): bool
    {
        return $paymentRequest->group->owner_id === $user->id;
    }

    /**
     * Owner can bulk confirm payments in their groups
     */
    public function bulkConfirm(User $user, PaymentRequest $paymentRequest): bool
    {
        return $paymentRequest->group->owner_id === $user->id;
    }
}
