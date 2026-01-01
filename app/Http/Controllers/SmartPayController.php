<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Support\PaymentLinks;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;

class SmartPayController extends Controller
{
    /**
     * Show smart payment page with multiple payment options
     */
    public function show(PaymentRequest $request)
    {
        $group = $request->cycle?->group ?? $request->group;
        $owner = $group?->owner;

        $note = $group
            ? "Group {$group->name} - ".$request->created_at->format('M Y')." - PR#{$request->id}"
            : "Payment Request - ".$request->created_at->format('M Y')." - PR#{$request->id}";

        // Generate Cash App link if owner has cash tag
        $cashTag = $owner?->cashapp_tag ?? $owner?->cash_tag;
        $cashLink = $cashTag
            ? PaymentLinks::cashApp($cashTag, $request->amount_cents, $note)
            : null;

        // Generate Venmo link if owner has venmo username
        $venmoUsername = $owner?->venmo_username;
        $venmoLink = ! empty($venmoUsername)
            ? PaymentLinks::venmo($venmoUsername, $request->amount_cents, $note)
            : null;

        // Square invoice link (card/Apple Pay/etc.) if available
        $squareLink = data_get($request->metadata, 'square_public_url');

        $paymentOptions = array_values(array_filter([
            $cashLink ? [
                'id' => 'cash_app',
                'label' => 'Open Cash App',
                'url' => $cashLink,
                'toast' => 'Opening Cash App...',
            ] : null,
            $venmoLink ? [
                'id' => 'venmo',
                'label' => 'Open Venmo',
                'url' => $venmoLink,
                'toast' => 'Opening Venmo...',
            ] : null,
            $squareLink ? [
                'id' => 'square',
                'label' => 'Pay by Card / Apple Pay',
                'url' => $squareLink,
                'toast' => 'Opening payment page...',
            ] : null,
        ]));

        $autoOpenUrl = count($paymentOptions) === 1
            ? $paymentOptions[0]['url']
            : null;
        $autoOpenToast = count($paymentOptions) === 1
            ? $paymentOptions[0]['toast']
            : null;

        // Generate QR code for this payment page
        $qrCode = $this->generateQrCode(url("/pay/{$request->id}"));

        return view('smartpay.show', compact(
            'request',
            'cashLink',
            'venmoLink',
            'squareLink',
            'paymentOptions',
            'autoOpenUrl',
            'autoOpenToast',
            'group',
            'qrCode'
        ));
    }

    /**
     * Generate QR code SVG
     */
    private function generateQrCode(string $url): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $writer->writeString($url);
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
