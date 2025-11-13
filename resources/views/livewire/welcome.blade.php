<?php

use function Livewire\Volt\{layout, title};

layout('components.layouts.guest');
title('Welcome to TogetherPay');

?>

<div
    class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-brand-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <!-- Hero Section -->
    <div class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white sm:text-5xl md:text-6xl">
                    <span class="block">Split Bills Together</span>
                    <span class="block text-brand-600 dark:text-brand-400">Pay Separately</span>
                </h1>
                <p
                    class="mt-3 max-w-md mx-auto text-base text-gray-500 dark:text-gray-400 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                    Manage shared expenses effortlessly. Create groups, send payment requests via Venmo or Cash App, and
                    track collections with automated reminders.
                </p>
                <div class="mt-8 max-w-2xl mx-auto flex flex-col gap-4 sm:flex-row sm:justify-center">
                    <a href="#download-ios"
                        class="flex items-center justify-center px-8 py-4 rounded-xl bg-black text-white font-semibold shadow-lg hover:opacity-90 transition">
                        <svg class="w-8 h-8 mr-3" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#2196f3"
                                d="M44,24c0,11.044-8.956,20-20,20S4,35.044,4,24S12.956,4,24,4S44,12.956,44,24z"></path>
                            <path fill="#fff"
                                d="M31.684 23.867l-2.48 1.441L22.43 13.652c-.27-.465-.113-1.063.355-1.336l.793-.461c.465-.27 1.063-.113 1.332.355L31.684 23.867zM29.48 25.789l2.48-1.441 1.438 2.469-2.48 1.441L29.48 25.789zM13.84 28.152l5.988-10.246 2.555 1.492-5.988 10.246L13.84 28.152zM12.285 33.375l1.234-4.676 2.555 1.496-3.469 3.367c-.059.063-.156.074-.23.031C12.297 33.547 12.262 33.461 12.285 33.375M20.035 17.547l.863-1.469c.27-.469.871-.625 1.336-.352l.867.508c.465.273.621.871.348 1.336l-.859 1.473L20.035 17.547zM33.023 27.578c-.402.23-1.004.586-1.359.797-.664.395-.152 1.559 0 1.809.859 1.441 1.746 1.238 2.414 2.258.367.559.266.805.379.992.047.066.191.133.246.055 1.031-1.426.73-3.879-.02-4.973C34.336 28.004 33.703 27.191 33.023 27.578M36.637 25.41h-3.563l-1.555-2.855h5.117V25.41zM28.617 25.41h-9.293l1.586-2.855h6.121L28.617 25.41zM14.945 25.41h-3.617v-2.855h5.309L14.945 25.41z">
                            </path>
                        </svg> Download on iOS
                    </a>
                    <a href="#download-android"
                        class="flex items-center justify-center px-8 py-4 rounded-xl bg-brand-600 text-white font-semibold shadow-lg hover:bg-brand-700 transition">
                        <svg class="w-8 h-8 mr-3" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#4db6ac"
                                d="M7.705,4.043C7.292,4.15,7,4.507,7,5.121c0,1.802,0,18.795,0,18.795S7,42.28,7,43.091c0,0.446,0.197,0.745,0.5,0.856l20.181-20.064L7.705,4.043z">
                            </path>
                            <path fill="#dce775"
                                d="M33.237,18.36l-8.307-4.796c0,0-15.245-8.803-16.141-9.32C8.401,4.02,8.019,3.961,7.705,4.043l19.977,19.84L33.237,18.36z">
                            </path>
                            <path fill="#d32f2f"
                                d="M8.417,43.802c0.532-0.308,15.284-8.825,24.865-14.357l-5.601-5.562L7.5,43.947C7.748,44.038,8.066,44.004,8.417,43.802z">
                            </path>
                            <path fill="#fbc02d"
                                d="M41.398,23.071c-0.796-0.429-8.1-4.676-8.1-4.676l-0.061-0.035l-5.556,5.523l5.601,5.562c4.432-2.559,7.761-4.48,8.059-4.653C42.285,24.248,42.194,23.5,41.398,23.071z">
                            </path>
                        </svg>
                        Get it on Android
                    </a>
                </div>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">TogetherPay is available exclusively on iOS and
                    Android. Web access is for info only.</p>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="py-12 bg-white dark:bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl">
                    Everything you need to manage shared bills
                </h2>
                <p class="mt-4 text-xl text-gray-500 dark:text-gray-400">
                    Simple, transparent, and fee-free payment collection
                </p>
            </div>

            <div class="mt-16">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature 1 -->
                    <div class="pt-6">
                        <div class="flow-root bg-gray-50 dark:bg-gray-700 rounded-lg px-6 pb-8">
                            <div class="-mt-6">
                                <div>
                                    <span
                                        class="inline-flex items-center justify-center p-3 bg-brand-500 rounded-md shadow-lg">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                            </path>
                                        </svg>
                                    </span>
                                </div>
                                <h3 class="mt-8 text-lg font-medium text-gray-900 dark:text-white tracking-tight">Create
                                    Groups</h3>
                                <p class="mt-5 text-base text-gray-500 dark:text-gray-400">
                                    Set up billing groups for shared expenses like phone plans, utilities, or
                                    subscriptions. Define member shares and payment preferences.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="pt-6">
                        <div class="flow-root bg-gray-50 dark:bg-gray-700 rounded-lg px-6 pb-8">
                            <div class="-mt-6">
                                <div>
                                    <span
                                        class="inline-flex items-center justify-center p-3 bg-brand-500 rounded-md shadow-lg">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                            </path>
                                        </svg>
                                    </span>
                                </div>
                                <h3 class="mt-8 text-lg font-medium text-gray-900 dark:text-white tracking-tight">Send
                                    Requests</h3>
                                <p class="mt-5 text-base text-gray-500 dark:text-gray-400">
                                    Automatically generate and send payment requests via Venmo or Cash App. No
                                    transaction fees - members pay directly to you.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="pt-6">
                        <div class="flow-root bg-gray-50 dark:bg-gray-700 rounded-lg px-6 pb-8">
                            <div class="-mt-6">
                                <div>
                                    <span
                                        class="inline-flex items-center justify-center p-3 bg-brand-500 rounded-md shadow-lg">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                                            </path>
                                        </svg>
                                    </span>
                                </div>
                                <h3 class="mt-8 text-lg font-medium text-gray-900 dark:text-white tracking-tight">Smart
                                    Reminders</h3>
                                <p class="mt-5 text-base text-gray-500 dark:text-gray-400">
                                    Automated follow-ups and reminders keep payments on track. Track status in real-time
                                    and never miss a collection.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 4 -->
                    <div class="pt-6">
                        <div class="flow-root bg-gray-50 dark:bg-gray-700 rounded-lg px-6 pb-8">
                            <div class="-mt-6">
                                <div>
                                    <span
                                        class="inline-flex items-center justify-center p-3 bg-brand-500 rounded-md shadow-lg">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                            </path>
                                        </svg>
                                    </span>
                                </div>
                                <h3 class="mt-8 text-lg font-medium text-gray-900 dark:text-white tracking-tight">
                                    Reports & Analytics</h3>
                                <p class="mt-5 text-base text-gray-500 dark:text-gray-400">
                                    View collection rates, export payment history to CSV/PDF, and track member payment
                                    patterns over time.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 5 -->
                    <div class="pt-6">
                        <div class="flow-root bg-gray-50 dark:bg-gray-700 rounded-lg px-6 pb-8">
                            <div class="-mt-6">
                                <div>
                                    <span
                                        class="inline-flex items-center justify-center p-3 bg-brand-500 rounded-md shadow-lg">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </span>
                                </div>
                                <h3 class="mt-8 text-lg font-medium text-gray-900 dark:text-white tracking-tight">
                                    Recurring Billing</h3>
                                <p class="mt-5 text-base text-gray-500 dark:text-gray-400">
                                    Set up monthly or custom billing cycles. Payment requests are generated and sent
                                    automatically on schedule.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 6 -->
                    <div class="pt-6">
                        <div class="flow-root bg-gray-50 dark:bg-gray-700 rounded-lg px-6 pb-8">
                            <div class="-mt-6">
                                <div>
                                    <span
                                        class="inline-flex items-center justify-center p-3 bg-brand-500 rounded-md shadow-lg">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                            </path>
                                        </svg>
                                    </span>
                                </div>
                                <h3 class="mt-8 text-lg font-medium text-gray-900 dark:text-white tracking-tight">
                                    Secure & Private</h3>
                                <p class="mt-5 text-base text-gray-500 dark:text-gray-400">
                                    Your data is encrypted and secure. We never handle payments directly - all
                                    transactions go through trusted P2P apps.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Collection Modes -->
    <div class="bg-gray-50 dark:bg-gray-900 py-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 border border-gray-100 dark:border-gray-700">
                    <p
                        class="inline-flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200 mb-4">
                        Manual Mode</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Share links. Confirm inside the
                        app.</h3>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-6">
                        Perfect for small groups that already swap payments through Venmo or Cash App. We generate the
                        links, remind members, and give you the receipts so you never scroll through screenshots again.
                    </p>
                    <ul class="space-y-3 text-gray-700 dark:text-gray-200">
                        <li>• Pre-filled payment links for every member</li>
                        <li>• Members mark themselves paid, you approve</li>
                        <li>• Real-time ledger for each billing cycle</li>
                    </ul>
                </div>
                <div class="bg-brand-600 text-white rounded-3xl shadow-2xl p-8">
                    <p
                        class="inline-flex items-center px-3 py-1 bg-white/20 rounded-full text-xs font-semibold uppercase tracking-wide mb-4">
                        Auto Cash App Pay</p>
                    <h3 class="text-2xl font-bold mb-3">Members approve in Cash App. Square handles the rest.</h3>
                    <p class="text-white/90 leading-relaxed mb-6">
                        Connect your own Square account once. Each cycle, we publish Cash App Pay invoices on your
                        behalf. Members get a Cash App push notification, you get funds in your Square balance—no
                        chasing.
                    </p>
                    <ul class="space-y-3 text-white/90">
                        <li>• TogetherPay never holds your money</li>
                        <li>• Square deposits directly to your bank or Square Checking</li>
                        <li>• Detailed audit trail for every auto charge</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-brand-600 dark:bg-brand-700">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                <span class="block">Ready to simplify bill splitting?</span>
                <span class="block text-brand-200">Start managing your groups today.</span>
            </h2>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                @guest
                    <div class="inline-flex rounded-md shadow">
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-brand-600 bg-white hover:bg-brand-50">
                            Get started for free
                        </a>
                    </div>
                @else
                    <div class="inline-flex rounded-md shadow">
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-brand-600 bg-white hover:bg-brand-50">
                            Go to Dashboard
                        </a>
                    </div>
                @endguest
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 md:flex md:items-center md:justify-between lg:px-8">
            <div class="flex justify-center space-x-6 md:order-2">
                <a href="{{ route('about') }}"
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">About</a>
                <a href="{{ route('privacy') }}"
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">Privacy</a>
                <a href="{{ route('terms') }}"
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">Terms</a>
                <a href="{{ route('contact') }}"
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">Contact</a>
            </div>
            <div class="mt-8 md:mt-0 md:order-1">
                <p class="text-center text-base text-gray-400">&copy; {{ date('Y') }} TogetherPay. All rights
                    reserved.</p>
            </div>
        </div>
    </footer>
</div>
