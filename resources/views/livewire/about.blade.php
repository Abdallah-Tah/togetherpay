<?php

use function Livewire\Volt\{layout, title};

layout('components.layouts.guest');
title('About TogetherPay');

?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-950 selection:bg-brand-500 selection:text-white overflow-x-hidden">
    <!-- Navbar -->
    <!-- Navbar -->
    <x-guest-navbar />

    <!-- Hero Header -->
    <div class="relative pt-32 pb-20 overflow-hidden">
        <!-- Animated Background Layers -->
        <div class="bg-animated-aurora"></div>
        <div class="bg-animated-grid"></div>

        <!-- Floating Orbs -->
        <div class="bg-floating-orbs">
            <div class="floating-orb floating-orb-1"></div>
            <div class="floating-orb floating-orb-2"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center z-10">
            <h1 class="text-4xl md:text-6xl font-bold text-zinc-900 dark:text-white mb-6 animate-fade-in-up">
                Built for <span class="text-gradient-hero">group owners</span>
            </h1>
            <p class="text-xl text-zinc-600 dark:text-zinc-400 max-w-3xl mx-auto animate-fade-in-up" style="animation-delay: 0.1s;">
                TogetherPay keeps payments organized while the money flows directly between your members and your wallets.
            </p>
        </div>
    </div>

    <div class="max-w-5xl mx-auto pb-24 px-4 sm:px-6 lg:px-8">
        <div class="glass-premium rounded-3xl overflow-hidden border border-white/40 dark:border-white/5">
            <div class="px-6 py-8 sm:p-12 space-y-16">
                <section class="animate-fade-in-up" style="animation-delay: 0.2s">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-4">Our promise</h2>
                    <p class="text-lg text-zinc-600 dark:text-zinc-300 leading-relaxed">
                        We never touch your funds. Manual mode uses peer-to-peer links. Auto mode uses the Square account you already own.
                        TogetherPay exists to remove the stressful parts of running shared bills—not to become another payment processor.
                    </p>
                </section>

                <section class="animate-fade-in-up" style="animation-delay: 0.3s">
                    <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-8">Two ways to collect</h2>
                    <div class="grid gap-8 md:grid-cols-2 md:items-stretch">
                        <!-- Manual Mode Card -->
                        <div class="glass-card p-8 rounded-3xl flex flex-col h-full group hover:-translate-y-1 transition-transform duration-300">
                            <div class="inline-flex items-center px-3 py-1 bg-zinc-100 dark:bg-zinc-800 rounded-full mb-4 w-fit">
                                <p class="text-xs font-bold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Manual payouts</p>
                            </div>
                            <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mt-2 mb-3">Great for casual groups</h3>
                            <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed mb-6 flex-grow">Members tap a pre-filled Cash App or Venmo link, mark themselves paid, and you approve from the app.</p>
                            <ul class="space-y-3 mt-auto">
                                <li class="flex items-start text-zinc-600 dark:text-zinc-400">
                                    <svg class="w-5 h-5 text-brand-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Personalized payment links & reminders</span>
                                </li>
                                <li class="flex items-start text-zinc-600 dark:text-zinc-400">
                                    <svg class="w-5 h-5 text-brand-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Owner-controlled confirmations</span>
                                </li>
                                <li class="flex items-start text-zinc-600 dark:text-zinc-400">
                                    <svg class="w-5 h-5 text-brand-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Exportable receipts for every cycle</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Auto Mode Card -->
                        <div class="relative group h-full">
                            <div class="absolute inset-0 bg-gradient-to-br from-brand-500 to-brand-600 rounded-3xl opacity-100 transition-transform duration-300 group-hover:scale-[1.02] shadow-lg shadow-brand-500/20"></div>
                            <div class="relative flex flex-col h-full p-8 text-white">
                                <div class="inline-flex items-center px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full mb-4 w-fit">
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

                <section class="animate-fade-in-up" style="animation-delay: 0.4s">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-4">Trust & security</h2>
                    <div class="space-y-4 text-zinc-600 dark:text-zinc-300 leading-relaxed">
                        <p><strong class="text-zinc-900 dark:text-white">Owner as merchant.</strong> When you enable auto billing, Square issues the funds directly to you. TogetherPay only stores the encrypted OAuth token needed to create invoices.</p>
                        <p><strong class="text-zinc-900 dark:text-white">Transparent activity.</strong> Every request, reminder, and confirmation is logged so you can prove who paid when.</p>
                        <p><strong class="text-zinc-900 dark:text-white">Modern stack.</strong> Laravel 12, PostgreSQL, and encrypted secrets running on hardened cloud infrastructure give your data the same protections as enterprise apps.</p>
                    </div>
                </section>

                <section class="bg-zinc-50 dark:bg-zinc-900/50 rounded-2xl p-8 border border-zinc-100 dark:border-zinc-800 animate-fade-in-up" style="animation-delay: 0.5s">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                        <div>
                            <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-2">Need help getting started?</h2>
                            <p class="text-zinc-600 dark:text-zinc-400">We’ll walk you through moving off spreadsheets, connecting Square, or inviting your first members.</p>
                        </div>
                        <a href="{{ route('contact') }}" class="btn-beam whitespace-nowrap">Talk to support</a>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <!-- Footer -->
    <x-guest-footer />
</div>
