<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ManualPaymentRequestRequest;
use App\Models\BillingCycle;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PaymentRequest;
use App\Services\PaymentRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
     * Get a specific payment request by ID
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
     * Get payment request by PR code (V2 endpoint)
     *
     * GET /api/v1/requests/by-code/{prCode}
     */
    public function showByCode(string $prCode): JsonResponse
    {
        $pr = PaymentRequest::where('pr_code', $prCode)
            ->with(['group.owner', 'member.user', 'cycle'])
            ->firstOrFail();

        $this->authorize('view', $pr);

        return response()->json($this->transformPaymentRequest($pr));
    }

    /**
     * Create and optionally send a manual payment request to a member.
     */
    public function storeManual(
        ManualPaymentRequestRequest $request,
        Group $group,
        GroupMember $member,
        PaymentRequestService $paymentRequestService
    ): JsonResponse {
        $this->authorize('update', $group);

        if ($member->group_id !== $group->id) {
            abort(404, 'Member not found in this group');
        }

        if ($member->status !== 'active') {
            throw ValidationException::withMessages([
                'member' => ['Member is not active in this group.'],
            ]);
        }

        if (! $member->preferred_channel || ! $member->getPaymentHandle()) {
            throw ValidationException::withMessages([
                'member' => ['Member is missing payment handle information.'],
            ]);
        }

        $cycle = null;
        if ($request->filled('cycle_id')) {
            $cycle = BillingCycle::where('group_id', $group->id)
                ->findOrFail($request->integer('cycle_id'));
        }

        $amountCents = $request->filled('amount_cents')
            ? $request->integer('amount_cents')
            : (int) round($request->float('amount') * 100);

        $note = $request->input(
            'note',
            sprintf('%s • Manual request %s', $group->name, now($group->timezone ?? config('app.timezone'))->format('M d, Y'))
        );

        $sendNow = $request->boolean('send_now', true);

        $paymentRequest = $paymentRequestService->createManualRequest(
            $member,
            $amountCents,
            $note,
            $cycle,
            $sendNow
        );

        $paymentRequest->load(['group.owner', 'member.user', 'cycle']);

        return response()->json([
            'ok' => true,
            'message' => $sendNow
                ? 'Payment request sent successfully.'
                : 'Payment request created successfully.',
            'payment_request' => $this->transformPaymentRequest($paymentRequest),
        ], 201);
    }

    protected function transformPaymentRequest(PaymentRequest $pr): array
    {
        $group = $pr->group;
        $cycle = $pr->cycle;
        $member = $pr->member;

        return [
            'id' => $pr->id,
            'pr_code' => $pr->pr_code,
            'group' => $group ? [
                'id' => $group->id,
                'name' => $group->name,
                'owner' => $group->owner ? [
                    'name' => $group->owner->name,
                    'cash_tag' => $group->owner->cash_tag,
                    'venmo_username' => $group->owner->venmo_username,
                ] : null,
            ] : null,
            'member' => $member ? [
                'id' => $member->id,
                'name' => $member->user?->name,
                'email' => $member->user?->email,
                'preferred_channel' => $member->preferred_channel,
            ] : null,
            'amount_cents' => $pr->amount_cents,
            'amount_display' => '$'.number_format($pr->amount_cents / 100, 2),
            'status' => $pr->status,
            'method_hint' => $pr->method_hint,
            'note' => $pr->note,
            'deep_link' => $pr->deep_link,
            'web_fallback_link' => $pr->web_fallback_link,
            'sent_at' => $pr->sent_at?->toIso8601String(),
            'marked_at' => $pr->marked_at?->toIso8601String(),
            'confirmed_at' => $pr->confirmed_at?->toIso8601String(),
            'due_date' => $cycle?->due_at?->toIso8601String(),
            'cycle' => $cycle ? [
                'id' => $cycle->id,
                'period_start' => $cycle->period_start?->toDateString(),
                'period_end' => $cycle->period_end?->toDateString(),
                'due_at' => $cycle->due_at?->toIso8601String(),
            ] : null,
            'metadata' => $pr->metadata,
            'evidence_url' => $pr->evidence_url,
        ];
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

    /**
     * Member marks payment as sent (V2 endpoint)
     *
     * POST /api/v1/requests/by-code/{prCode}/mark
     */
    public function markByCode(Request $request, string $prCode): JsonResponse
    {
        $pr = PaymentRequest::where('pr_code', $prCode)->firstOrFail();

        // Update status to member_marked
        $pr->update([
            'status' => 'member_marked',
            'marked_at' => now(),
            'evidence_url' => $request->input('evidence_url'),
            'evidence_type' => $request->input('evidence_type'),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Payment marked as sent',
            'pr' => [
                'id' => $pr->id,
                'pr_code' => $pr->pr_code,
                'status' => $pr->status,
                'marked_at' => $pr->marked_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Owner confirms payment received (V2 endpoint)
     *
     * POST /api/v1/requests/by-code/{prCode}/confirm
     */
    public function confirmPayment(string $prCode): JsonResponse
    {
        $pr = PaymentRequest::where('pr_code', $prCode)
            ->with('cycle')
            ->firstOrFail();

        $this->authorize('confirm', $pr);

        $pr->confirmPayment();

        return response()->json([
            'ok' => true,
            'pr' => [
                'id' => $pr->id,
                'pr_code' => $pr->pr_code,
                'status' => $pr->status,
                'confirmed_at' => $pr->confirmed_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Owner bulk confirms multiple payments (V2 endpoint)
     *
     * POST /api/v1/cycles/{cycleId}/bulk-confirm
     */
    public function bulkConfirm(int $cycleId, Request $request): JsonResponse
    {
        $cycle = BillingCycle::with('group')->findOrFail($cycleId);

        $this->authorize('view', $cycle->group);

        $validated = $request->validate([
            'pr_codes' => 'required|array|min:1',
            'pr_codes.*' => 'required|string',
        ]);

        // Only confirm payments that are member_marked
        $payments = PaymentRequest::whereIn('pr_code', $validated['pr_codes'])
            ->where('cycle_id', $cycleId)
            ->where('status', 'member_marked')
            ->get();

        $confirmed = 0;
        $totalCents = 0;

        foreach ($payments as $pr) {
            $pr->confirmPayment();
            $confirmed++;
            $totalCents += $pr->amount_cents;
        }

        return response()->json([
            'ok' => true,
            'confirmed_count' => $confirmed,
            'total_cents' => $totalCents,
            'total_display' => '$'.number_format($totalCents / 100, 2),
        ]);
    }

    /**
     * Owner sends reminder/nudge to member (V2 endpoint)
     *
     * POST /api/v1/requests/by-code/{prCode}/nudge
     */
    public function nudge(string $prCode): JsonResponse
    {
        $pr = PaymentRequest::where('pr_code', $prCode)->firstOrFail();

        $this->authorize('manage', $pr);

        // TODO: Dispatch reminder job
        // \App\Jobs\SendPaymentReminderJob::dispatch($pr);

        return response()->json([
            'ok' => true,
            'message' => 'Reminder sent',
        ]);
    }

    /**
     * Owner cancels a payment request (V2 endpoint)
     *
     * POST /api/v1/requests/by-code/{prCode}/cancel
     */
    public function cancel(string $prCode): JsonResponse
    {
        $pr = PaymentRequest::where('pr_code', $prCode)->firstOrFail();

        $this->authorize('manage', $pr);

        // Can only cancel if not already paid/confirmed
        if (in_array($pr->status, ['paid', 'confirmed', 'owner_confirmed'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Cannot cancel a paid or confirmed payment request',
            ], 422);
        }

        // Soft delete the payment request
        $pr->update([
            'metadata' => array_merge($pr->metadata ?? [], [
                'cancelled_at' => now()->toIso8601String(),
                'cancelled_by_user_id' => auth()->id(),
                'cancelled_reason' => 'Owner cancelled the request',
            ]),
        ]);

        $pr->delete(); // Soft delete

        return response()->json([
            'ok' => true,
            'message' => 'Payment request cancelled',
            'pr' => [
                'id' => $pr->id,
                'pr_code' => $pr->pr_code,
                'status' => 'cancelled',
            ],
        ]);
    }
}
