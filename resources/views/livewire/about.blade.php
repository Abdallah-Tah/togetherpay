<?php

use function Livewire\Volt\{layout, title};

layout('components.layouts.guest');
title('About TogetherPay');

?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Built for group owners</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-2">TogetherPay keeps payments organized while the money flows directly between your members and your wallets.</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-3xl">
            <div class="px-6 py-8 sm:p-10 space-y-10">
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Our promise</h2>
                    <p class="text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
                        We never touch your funds. Manual mode uses peer-to-peer links. Auto mode uses the Square account you already own.
                        TogetherPay exists to remove the stressful parts of running shared bills—not to become another payment processor.
                    </p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Two ways to collect</h2>
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                            <p class="text-sm uppercase tracking-wide text-gray-500 dark:text-gray-400">Manual payouts</p>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mt-2">Great for casual groups</h3>
                            <p class="mt-3 text-gray-700 dark:text-gray-300">Members tap a pre-filled Cash App or Venmo link, mark themselves paid, and you approve from the app.</p>
                            <ul class="mt-4 space-y-2 text-gray-700 dark:text-gray-300">
                                <li>• Personalized payment links & reminders</li>
                                <li>• Owner-controlled confirmations</li>
                                <li>• Exportable receipts for every cycle</li>
                            </ul>
                        </div>
                        <div class="rounded-2xl border border-brand-200 bg-brand-50 dark:border-brand-500/30 dark:bg-brand-500/10 p-6">
                            <p class="text-sm uppercase tracking-wide text-brand-700 dark:text-brand-300">Auto Cash App Pay</p>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mt-2">For teams that need autopay</h3>
                            <p class="mt-3 text-gray-700 dark:text-gray-100">Owners connect their own Square merchant accounts. We publish Cash App Pay invoices each cycle. Members approve in Cash App, Square pays you.</p>
                            <ul class="mt-4 space-y-2 text-gray-700 dark:text-gray-100">
                                <li>• No pooled funds, ever</li>
                                <li>• Deposits land in your Square balance/bank</li>
                                <li>• TogetherPay simply automates the workflow</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Trust & security</h2>
                    <div class="space-y-4 text-gray-700 dark:text-gray-300 leading-relaxed">
                        <p><strong>Owner as merchant.</strong> When you enable auto billing, Square issues the funds directly to you. TogetherPay only stores the encrypted OAuth token needed to create invoices.</p>
                        <p><strong>Transparent activity.</strong> Every request, reminder, and confirmation is logged so you can prove who paid when.</p>
                        <p><strong>Modern stack.</strong> Laravel 12, PostgreSQL, and encrypted secrets running on hardened cloud infrastructure give your data the same protections as enterprise apps.</p>
                    </div>
                </section>

                <section class="bg-gray-50 dark:bg-gray-700/60 rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Need help getting started?</h2>
                    <p class="text-gray-700 dark:text-gray-200 mb-4">We’ll walk you through moving off spreadsheets, connecting Square, or inviting your first members.</p>
                    <a href="{{ route('contact') }}" class="inline-flex items-center px-5 py-3 rounded-xl text-white bg-brand-600 hover:bg-brand-700">Talk to support</a>
                </section>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('welcome') }}" class="inline-flex items-center text-brand-600 dark:text-brand-400 hover:text-brand-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to home
            </a>
        </div>
    </div>
</div>
