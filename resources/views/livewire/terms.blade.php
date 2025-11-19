<?php

use function Livewire\Volt\{layout, title};

layout('components.layouts.guest');
title('Terms of Service - TogetherPay');

?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-950 selection:bg-brand-500 selection:text-white overflow-x-hidden">
    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 transition-all duration-300" id="navbar">
        <div class="absolute inset-0 bg-white/70 dark:bg-zinc-950/70 backdrop-blur-xl border-b border-zinc-200/50 dark:border-zinc-800/50"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-3">
                    <a href="{{ route('welcome') }}" class="flex items-center gap-3">
                        <x-app-logo class="w-10 h-10 rounded-xl shadow-lg shadow-brand-500/20" />
                        <span class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">TogetherPay</span>
                    </a>
                </div>
                <div class="hidden md:flex items-center gap-8">
                    <a href="{{ route('welcome') }}#features" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Features</a>
                    <a href="{{ route('welcome') }}#how-it-works" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">How it Works</a>
                    <a href="{{ route('login') }}" class="text-sm font-medium text-zinc-900 dark:text-white hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Log in</a>
                    <a href="{{ route('register') }}" class="px-5 py-2.5 text-sm font-semibold text-white bg-zinc-900 dark:bg-white dark:text-zinc-900 rounded-xl hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-all hover:-translate-y-0.5 shadow-lg shadow-zinc-900/20 dark:shadow-white/10">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="relative pt-32 pb-12 overflow-hidden">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full z-0 pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-brand-500/5 rounded-full blur-[120px] animate-pulse-slow"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-accent-purple/5 rounded-full blur-[120px] animate-pulse-slow" style="animation-delay: 2s"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center z-10">
            <h1 class="text-4xl md:text-5xl font-bold text-zinc-900 dark:text-white mb-4 animate-fade-in-up">Terms of Service</h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400 animate-fade-in-up" style="animation-delay: 0.1s;">Last updated: {{ date('F j, Y') }}</p>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto pb-24 px-4 sm:px-6 lg:px-8">
        <div class="glass-premium rounded-3xl overflow-hidden border border-white/40 dark:border-white/5">
            <div class="px-6 py-8 sm:p-12 prose prose-lg dark:prose-invert max-w-none prose-headings:text-zinc-900 dark:prose-headings:text-white prose-p:text-zinc-600 dark:prose-p:text-zinc-400 prose-strong:text-zinc-900 dark:prose-strong:text-white prose-a:text-brand-600 dark:prose-a:text-brand-400 prose-li:text-zinc-600 dark:prose-li:text-zinc-400">
                
                <section class="animate-fade-in-up" style="animation-delay: 0.2s">
                    <h2 class="text-2xl font-bold mb-4">1. Acceptance of Terms</h2>
                    <p class="leading-relaxed">By downloading or using TogetherPay (“Service”) you agree to these Terms. If you do not agree, do not access or use the Service.</p>
                </section>

                <section class="animate-fade-in-up" style="animation-delay: 0.3s">
                    <h2 class="text-2xl font-bold mb-4">2. Description of Service</h2>
                    <p class="leading-relaxed mb-3">TogetherPay provides tooling for group owners to organize shared expenses, send payment requests, and track payment status. We offer two collection modes:</p>
                    <ul class="list-disc list-inside space-y-2 mb-3">
                        <li><strong>Manual mode</strong> – generates pre-filled links for peer‑to‑peer apps such as Cash App or Venmo.</li>
                        <li><strong>Auto Cash App Pay</strong> – publishes Cash App Pay invoices through the owner’s connected Square merchant account.</li>
                    </ul>
                    <div class="bg-brand-50 dark:bg-brand-900/20 border border-brand-100 dark:border-brand-800 rounded-xl p-6 mt-4">
                        <p class="leading-relaxed text-brand-900 dark:text-brand-100 m-0">
                            <em>Important:</em> TogetherPay is not a payment processor and never holds user funds. Payments occur directly between the parties or via the owner’s Square account.
                        </p>
                    </div>
                </section>

                <section class="animate-fade-in-up" style="animation-delay: 0.4s">
                    <h2 class="text-2xl font-bold mb-4">3. Accounts & Responsibilities</h2>
                    <ul class="list-disc list-inside space-y-2">
                        <li>You must be at least 18 years old and provide accurate registration information.</li>
                        <li>You are responsible for safeguarding your credentials and the content you create.</li>
                        <li>You may not use the Service for unlawful or prohibited activities.</li>
                        <li>You agree to comply with all applicable laws and the terms of any linked payment providers.</li>
                    </ul>
                </section>

                <section class="animate-fade-in-up" style="animation-delay: 0.5s">
                    <h2 class="text-2xl font-bold mb-4">4. Payment Terms (Manual Mode)</h2>
                    <p class="leading-relaxed">When using manual mode you acknowledge that all financial transactions are handled solely through third‑party peer-to-peer services. TogetherPay is not responsible for failed payments, disputes, chargebacks, or fees imposed by those providers.</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold mb-4">5. Square & Cash App Pay Autopay</h2>
                    <ul class="list-disc list-inside space-y-2">
                        <li>Connecting Square authorizes TogetherPay to create invoices and read invoice metadata on your behalf.</li>
                        <li>You remain the merchant of record. Square deposits funds into your Square balance or linked bank account. TogetherPay never receives those funds.</li>
                        <li>Your use of Square is governed by Square’s Merchant Terms and Card Brand rules. You are solely responsible for compliance, taxes, chargebacks, refunds, and any disputes.</li>
                        <li>You may disconnect Square at any time from within TogetherPay or the Square dashboard. Disconnecting stops new autopay invoices but does not cancel invoices already published.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-2xl font-bold mb-4">6. Data & Privacy</h2>
                    <p class="leading-relaxed">We collect and store information needed to provide the Service (e.g., group details, email addresses, Square OAuth tokens). See our Privacy Policy for details on data handling. We never store full card numbers or bank credentials.</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold mb-4">7. Service Availability</h2>
                    <p class="leading-relaxed">We aim for continuous availability but may suspend or modify the Service at any time for maintenance, upgrades, or security reasons. We are not liable for any loss resulting from downtime.</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold mb-4">8. Limitation of Liability</h2>
                    <p class="leading-relaxed">TogetherPay is provided “as is.” To the fullest extent permitted by law, TogetherPay and its affiliates are not liable for indirect, incidental, or consequential damages arising from the use of the Service.</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold mb-4">9. Contact</h2>
                    <p class="leading-relaxed">
                        Questions about these Terms? Email <a href="mailto:support@togetherpay.com" class="text-brand-600 dark:text-brand-400 hover:underline">support@togetherpay.com</a>.
                    </p>
                </section>
            </div>
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2 text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 font-medium transition-colors group">
                <svg class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to home
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white dark:bg-zinc-950 border-t border-zinc-200 dark:border-zinc-800 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-2">
                    <x-app-logo class="w-8 h-8 rounded-lg" />
                    <span class="font-bold text-zinc-900 dark:text-white">TogetherPay</span>
                </div>
                
                <div class="flex gap-8 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                    <a href="{{ route('about') }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">About</a>
                    <a href="{{ route('privacy') }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Privacy</a>
                    <a href="{{ route('terms') }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Terms</a>
                    <a href="{{ route('contact') }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Contact</a>
                </div>
                
                <div class="text-sm text-zinc-500">
                    &copy; {{ date('Y') }} TogetherPay. All rights reserved.
                </div>
            </div>
        </div>
    </footer>
</div>
