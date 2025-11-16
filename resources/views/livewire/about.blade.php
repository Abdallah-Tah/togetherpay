<?php

use function Livewire\Volt\{layout, title};

layout('components.layouts.guest');
title('About TogetherPay');

?>

<div class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-gray-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <!-- Hero Header with Gradient -->
    <div class="relative bg-gradient-to-r from-brand-600 to-brand-700 dark:from-brand-700 dark:to-brand-800 overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4 animate-fade-in-up">Built for group owners</h1>
            <p class="text-xl text-brand-100 max-w-3xl animate-fade-in-up" style="animation-delay: 0.1s;">TogetherPay keeps payments organized while the money flows directly between your members and your wallets.</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-2xl shadow-gray-900/5 dark:shadow-brand-500/10 overflow-hidden sm:rounded-3xl border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-8 sm:p-12 space-y-12">
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Our promise</h2>
                    <p class="text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
                        We never touch your funds. Manual mode uses peer-to-peer links. Auto mode uses the Square account you already own.
                        TogetherPay exists to remove the stressful parts of running shared bills—not to become another payment processor.
                    </p>
                </section>

                <section>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Two ways to collect</h2>
                    <div class="grid gap-6 md:grid-cols-2 md:items-stretch">
                        <!-- Manual Mode Card -->
                        <div class="group relative flex">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-gray-400 to-gray-500 rounded-3xl opacity-0 group-hover:opacity-20 transition duration-300 blur"></div>
                            <div class="relative flex flex-col rounded-3xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-8 group-hover:border-gray-300 dark:group-hover:border-gray-600 transition-all duration-300 w-full">
                                <div class="inline-flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">Manual payouts</p>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-2 mb-3">Great for casual groups</h3>
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-6 flex-grow">Members tap a pre-filled Cash App or Venmo link, mark themselves paid, and you approve from the app.</p>
                                <ul class="space-y-3 mt-auto">
                                    <li class="flex items-start text-gray-700 dark:text-gray-300">
                                        <svg class="w-5 h-5 text-brand-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Personalized payment links & reminders</span>
                                    </li>
                                    <li class="flex items-start text-gray-700 dark:text-gray-300">
                                        <svg class="w-5 h-5 text-brand-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Owner-controlled confirmations</span>
                                    </li>
                                    <li class="flex items-start text-gray-700 dark:text-gray-300">
                                        <svg class="w-5 h-5 text-brand-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Exportable receipts for every cycle</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Auto Mode Card with Gradient -->
                        <div class="group relative flex">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-brand-500 to-brand-600 rounded-3xl opacity-75 group-hover:opacity-100 transition duration-300 blur"></div>
                            <div class="relative flex flex-col rounded-3xl bg-gradient-to-br from-brand-500 to-brand-600 p-8 text-white transform group-hover:scale-[1.02] transition-all duration-300 w-full">
                                <div class="inline-flex items-center px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full mb-4">
                                    <p class="text-xs font-bold uppercase tracking-wide">Auto Cash App Pay</p>
                                </div>
                                <h3 class="text-2xl font-bold mt-2 mb-3">For teams that need autopay</h3>
                                <p class="text-white/90 leading-relaxed mb-6 flex-grow">Owners connect their own Square merchant accounts. We publish Cash App Pay invoices each cycle. Members approve in Cash App, Square pays you.</p>
                                <ul class="space-y-3 mt-auto">
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>No pooled funds, ever</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Deposits land in your Square balance/bank</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>TogetherPay simply automates the workflow</span>
                                    </li>
                                </ul>
                            </div>
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
