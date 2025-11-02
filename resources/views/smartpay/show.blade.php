<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay {{ number_format($request->amount_cents / 100, 2) }} USD</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-teal-500 to-emerald-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-block p-4 bg-teal-100 rounded-full mb-4">
                <svg class="w-12 h-12 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                ${{ number_format($request->amount_cents / 100, 2) }}
            </h1>
            <p class="text-gray-600">
                {{ $group->name }}
            </p>
            <p class="text-sm text-gray-500 mt-1">
                Due: {{ $request->due_at->format('M d, Y') }}
            </p>
        </div>

        <!-- Payment Options -->
        <div class="space-y-3 mb-6">
            @if($cashLink)
                <a href="{{ $cashLink }}"
                   class="flex items-center justify-center w-full py-4 px-6 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.59 3.475c-.18.17-.472.288-.763.288-.29 0-.583-.12-.763-.288L19.24.65a.939.939 0 010-1.338c.18-.17.472-.288.763-.288.29 0 .583.12.763.288l2.824 2.638c.18.17.29.472.29.763 0 .29-.11.583-.29.763zm-3.884.68c0-.507.41-.917.917-.917h4.325c.507 0 .917.41.917.917v13.384c0 .507-.41.917-.917.917h-4.325c-.507 0-.917-.41-.917-.917V4.155zm-5.23 2.508c0-.507.41-.917.917-.917h4.325c.507 0 .917.41.917.917v10.876c0 .507-.41.917-.917.917h-4.325c-.507 0-.917-.41-.917-.917V6.663zm-5.23 2.507c0-.507.41-.917.917-.917h4.325c.507 0 .917.41.917.917v8.37c0 .506-.41.916-.917.916H9.163c-.507 0-.917-.41-.917-.917V9.17zm-5.23 2.508c0-.507.41-.917.917-.917h4.325c.507 0 .917.41.917.917v5.862c0 .507-.41.917-.917.917H4.933c-.507 0-.917-.41-.917-.917v-5.862z"/>
                    </svg>
                    Open Cash App
                </a>
            @endif

            @if($venmoLink)
                <a href="{{ $venmoLink }}"
                   class="flex items-center justify-center w-full py-4 px-6 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.48 0.5H3.52C1.85 0.5 0.5 1.85 0.5 3.52v16.96c0 1.67 1.35 3.02 3.02 3.02h16.96c1.67 0 3.02-1.35 3.02-3.02V3.52c0-1.67-1.35-3.02-3.02-3.02zm-3.22 15.98l-3.07.02-2.92-10.46c.32-.73.5-1.54.5-2.38h2.83c-.03.42-.09.83-.18 1.24l1.93 8.12 2.49-8.12h2.73l-3.31 11.58z"/>
                    </svg>
                    Open Venmo
                </a>
            @endif

            @if(!$cashLink && !$venmoLink)
                <div class="text-center py-8">
                    <p class="text-gray-600 mb-4">No payment method configured</p>
                    <p class="text-sm text-gray-500">Please contact the group owner</p>
                </div>
            @endif
        </div>

        <!-- Info -->
        <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Request ID:</span>
                <span class="font-semibold text-gray-900">PR #{{ $request->id }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Status:</span>
                <span class="font-semibold {{ $request->status === 'PAID' ? 'text-green-600' : 'text-orange-600' }}">
                    {{ $request->status }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Created:</span>
                <span class="font-semibold text-gray-900">{{ $request->created_at->format('M d, Y') }}</span>
            </div>
        </div>

        <!-- Mark as Paid Button -->
        @if($request->status !== 'PAID' && $request->status !== 'confirmed')
        <div class="mt-6">
            <button
                id="markPaidBtn"
                onclick="markAsPaid()"
                class="w-full py-4 px-6 bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                I've Sent the Payment
            </button>
            <p class="text-xs text-gray-500 text-center mt-2">
                Click after completing the payment
            </p>
        </div>
        @endif

        @if($request->status === 'PAID' || $request->status === 'confirmed')
        <div class="mt-6 p-4 bg-green-50 rounded-xl text-center">
            <svg class="w-12 h-12 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-green-700 font-semibold">Payment Completed!</p>
            <p class="text-sm text-green-600 mt-1">Thank you for your payment</p>
        </div>
        @endif

        <!-- QR Code -->
        <div class="mt-6 border-t pt-6">
            <p class="text-center text-sm text-gray-600 mb-3">Or scan QR code to pay:</p>
            <div class="flex justify-center">
                <div class="bg-white p-4 rounded-xl shadow-md inline-block">
                    {!! $qrCode !!}
                </div>
            </div>
            <p class="text-center text-xs text-gray-500 mt-2">
                Share this QR code with the payer
            </p>
        </div>

        <!-- Help Text -->
        <div class="mt-6 text-center">
            <p class="text-xs text-gray-500">
                If the app doesn't open automatically, make sure you have it installed on your device.
            </p>
        </div>
    </div>

    <script>
        // Mark payment as paid
        function markAsPaid() {
            const btn = document.getElementById('markPaidBtn');
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

            fetch('/api/v1/requests/{{ $request->id }}/mark-paid', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    // Show success message
                    btn.className = 'w-full py-4 px-6 bg-green-500 text-white font-semibold rounded-xl';
                    btn.innerHTML = '<svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Payment Marked as Sent!';

                    // Reload after 2 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to mark payment');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = '<svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> I\'ve Sent the Payment';
            });
        }

        // Auto-redirect on mobile devices
        window.addEventListener('DOMContentLoaded', () => {
            const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
            const urlParams = new URLSearchParams(window.location.search);
            const skipAutoOpen = urlParams.get('skip_auto_open') === '1';

            if (isMobile && !skipAutoOpen) {
                @if($cashLink)
                    // Show a toast notification
                    const toast = document.createElement('div');
                    toast.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-teal-600 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                    toast.textContent = 'Opening Cash App...';
                    document.body.appendChild(toast);

                    // Auto-open Cash App after 1.5 seconds
                    setTimeout(() => {
                        window.location.href = '{{ $cashLink }}';
                        toast.remove();
                    }, 1500);
                @elseif($venmoLink)
                    // Show a toast notification
                    const toast = document.createElement('div');
                    toast.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                    toast.textContent = 'Opening Venmo...';
                    document.body.appendChild(toast);

                    // Auto-open Venmo after 1.5 seconds
                    setTimeout(() => {
                        window.location.href = '{{ $venmoLink }}';
                        toast.remove();
                    }, 1500);
                @endif
            }
        });
    </script>
</body>
</html>
