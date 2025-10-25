<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Support\PaymentLinks;
use Illuminate\Http\Request;

class SmartPayController extends Controller
{
    /**
     * Show smart payment page with multiple payment options
     */
    public function show(PaymentRequest $request)
    {
        $member = $request->member; // relation to GroupMember
        $group = $request->cycle->group;

        $note = "Group {$group->name} - " . $request->created_at->format('M Y') . " - PR#{$request->id}";

        // Generate Cash App link if member has cash tag
        $cashLink = $member->cash_tag
            ? PaymentLinks::cashApp($member->cash_tag, $request->amount_cents, $note)
            : null;

        // Generate Venmo link if member has venmo username
        $venmoLink = !empty($member->venmo_username)
            ? PaymentLinks::venmo($member->venmo_username, $request->amount_cents, $note)
            : null;

        return view('smartpay.show', compact('request', 'cashLink', 'venmoLink', 'group'));
    }

    /**
     * Mark payment request as paid
     */
    public function markPaid(Request $request, int $id)
    {
        $paymentRequest = PaymentRequest::findOrFail($id);

        // Optional: Add authorization check
        // $this->authorize('markPaid', $paymentRequest);

        $paymentRequest->update([
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Payment marked as paid',
        ]);
    }
}
