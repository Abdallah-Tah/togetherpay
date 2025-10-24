<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BillingCycle;
use App\Models\PaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentRequestController extends Controller
{
    /**
     * Get payment requests for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get user's group memberships
        $memberIds = $user->groupMemberships()->pluck('id');

        // Get payment requests for user's memberships
        $requests = PaymentRequest::whereIn('member_id', $memberIds)
            ->with(['cycle.group', 'member'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($requests);
    }

    /**
     * Get payment requests for a specific cycle
     */
    public function byCycle(Request $request, BillingCycle $cycle): JsonResponse
    {
        $this->authorize('view', $cycle->group);

        $requests = $cycle->paymentRequests()
            ->with(['member.user'])
            ->get();

        return response()->json([
            'cycle' => $cycle,
            'requests' => $requests,
        ]);
    }

    /**
     * Get a specific payment request
     */
    public function show(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        // User can view if they're the member or group owner
        $user = $request->user();
        $isOwner = $paymentRequest->cycle->group->owner_id === $user->id;
        $isMember = $paymentRequest->member->user_id === $user->id;

        if (!$isOwner && !$isMember) {
            abort(403, 'Unauthorized');
        }

        $paymentRequest->load(['cycle.group', 'member.user', 'dunningEvents']);

        return response()->json([
            'payment_request' => $paymentRequest,
        ]);
    }

    /**
     * Mark payment request as paid (manual confirmation)
     */
    public function markPaid(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        // Only owner or the member can mark as paid
        $user = $request->user();
        $isOwner = $paymentRequest->cycle->group->owner_id === $user->id;
        $isMember = $paymentRequest->member->user_id === $user->id;

        if (!$isOwner && !$isMember) {
            abort(403, 'Unauthorized');
        }

        if ($paymentRequest->isPaid()) {
            return response()->json([
                'message' => 'Payment request is already marked as paid',
            ], 422);
        }

        $paymentRequest->markAsPaid();

        return response()->json([
            'payment_request' => $paymentRequest->fresh(),
            'message' => 'Payment marked as paid successfully',
        ]);
    }

    /**
     * Retry a failed payment request (regenerate link)
     */
    public function retry(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        // Only member can retry their own payment
        if ($paymentRequest->member->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        if ($paymentRequest->isPaid()) {
            return response()->json([
                'message' => 'Payment request is already paid',
            ], 422);
        }

        // Reset status to pending so it can be resent
        $paymentRequest->update([
            'status' => 'pending',
            'sent_at' => null,
            'fail_reason' => null,
        ]);

        return response()->json([
            'payment_request' => $paymentRequest->fresh(),
            'message' => 'Payment request will be resent',
        ]);
    }

    /**
     * Get payment statistics for authenticated user
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $memberIds = $user->groupMemberships()->pluck('id');

        $totalRequests = PaymentRequest::whereIn('member_id', $memberIds)->count();
        $paidRequests = PaymentRequest::whereIn('member_id', $memberIds)
            ->where('status', 'paid')
            ->count();
        $pendingRequests = PaymentRequest::whereIn('member_id', $memberIds)
            ->whereIn('status', ['pending', 'sent'])
            ->count();
        $totalPaid = PaymentRequest::whereIn('member_id', $memberIds)
            ->where('status', 'paid')
            ->sum('amount_cents') / 100;

        $onTimeRate = $totalRequests > 0 ? ($paidRequests / $totalRequests) * 100 : 0;

        return response()->json([
            'total_requests' => $totalRequests,
            'paid_requests' => $paidRequests,
            'pending_requests' => $pendingRequests,
            'total_paid' => round($totalPaid, 2),
            'on_time_rate' => round($onTimeRate, 2),
        ]);
    }
}
