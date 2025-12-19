<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class RedirectController extends Controller
{
    /**
     * Redirect to Cash App, showing the amount in the URL/path.
     * Note: Cash App may still require the user to type the amount manually.
     */
    public function cashApp(Request $request): View
    {
        $cashtag = $request->input('cashtag');
        $amount = $request->input('amount', 0);
        $note = $request->input('note', '');

        // Clean cashtag (remove $ if present)
        $cashtag = ltrim($cashtag, '$');

        // Build Cash App URL
        $cashAppUrl = "https://cash.app/\${$cashtag}/{$amount}";

        return view('redirect.cashapp', [
            'cashtag' => "\${$cashtag}",
            'amount' => $amount,
            'note' => $note,
            'cashAppUrl' => $cashAppUrl,
        ]);
    }
}
