<?php

use function Livewire\Volt\{layout, title};

layout('components.layouts.guest');
title('How It Works - TogetherPay');

?>

<div class="min-h-screen selection:bg-brand-500 selection:text-white overflow-x-hidden">
    <!-- Navbar -->
    <!-- Navbar -->
    <x-guest-navbar />

    <!-- Hero Section -->
    <div class="relative pt-32 pb-20 lg:pt-40 lg:pb-24 overflow-hidden">
        <!-- Animated Background Layers -->
        <div class="bg-animated-aurora"></div>
        <div class="bg-animated-grid"></div>

        <!-- Floating Orbs -->
        <div class="bg-floating-orbs">
            <div class="floating-orb floating-orb-1"></div>
            <div class="floating-orb floating-orb-3"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl md:text-6xl font-bold tracking-tight text-zinc-900 dark:text-white mb-6 animate-fade-in-up">
                How <span class="text-gradient-hero">TogetherPay</span> Works
            </h1>
            <p class="text-xl text-zinc-600 dark:text-zinc-400 max-w-3xl mx-auto animate-fade-in-up" style="animation-delay: 0.1s">
                Split bills effortlessly in four simple steps. No more awkward money conversations.
            </p>
        </div>
    </div>

    <!-- Steps Section -->
    <div class="relative py-20 lg:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Step 1 -->
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center mb-32" data-stagger>
                <div class="order-2 lg:order-1 fade-in-up" data-stagger-item>
                    <div class="glass-strong p-8 rounded-3xl">
                        <img src="https://placehold.co/600x400/00CDBE/FFFFFF/png?text=Create+Group" alt="Create Group" class="w-full rounded-2xl shadow-2xl">
                    </div>
                </div>
                <div class="order-1 lg:order-2 fade-in-up" data-stagger-item>
                    <div class="inline-flex items-center gap-3 mb-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white font-bold text-xl shadow-lg shadow-brand-500/30">
                            1
                        </div>
                        <span class="text-sm font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider">Step One</span>
                    </div>
                    <h2 class="text-4xl font-bold text-zinc-900 dark:text-white mb-6">
                        Create Your Group
                    </h2>
                    <p class="text-lg text-zinc-600 dark:text-zinc-400 mb-8 leading-relaxed">
                        Set up a bill-splitting group in seconds. Add a name, set the total amount, choose your billing frequency (weekly, monthly, or custom), and you're ready to go.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-zinc-700 dark:text-zinc-300">Name your group (e.g., "Roommates", "Family Plan")</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-zinc-700 dark:text-zinc-300">Set total amount and billing frequency</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-zinc-700 dark:text-zinc-300">Choose payment collection method</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center mb-32" data-stagger>
                <div class="fade-in-up" data-stagger-item>
                    <div class="inline-flex items-center gap-3 mb-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-accent-teal to-accent-amber text-white font-bold text-xl shadow-lg shadow-accent-teal/30">
                            2
                        </div>
                        <span class="text-sm font-semibold text-accent-teal dark:text-accent-amber uppercase tracking-wider">Step Two</span>
                    </div>
                    <h2 class="text-4xl font-bold text-zinc-900 dark:text-white mb-6">
                        Add Members
                    </h2>
                    <p class="text-lg text-zinc-600 dark:text-zinc-400 mb-8 leading-relaxed">
                        Invite people to your group by email. Each member gets their own custom share amount. Perfect for unequal splits or varying contributions.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-accent-teal/10 dark:bg-accent-teal/20 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-accent-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-zinc-700 dark:text-zinc-300">Send email invitations instantly</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-accent-teal/10 dark:bg-accent-teal/20 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-accent-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-zinc-700 dark:text-zinc-300">Set individual amounts for each member</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-accent-teal/10 dark:bg-accent-teal/20 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-accent-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-zinc-700 dark:text-zinc-300">Members accept invitation to join</span>
                        </li>
                    </ul>
                </div>
                <div class="fade-in-up" data-stagger-item>
                    <div class="glass-strong p-8 rounded-3xl">
                        <img src="https://placehold.co/600x400/A855F7/FFFFFF/png?text=Add+Members" alt="Add Members" class="w-full rounded-2xl shadow-2xl">
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center mb-32" data-stagger>
                <div class="order-2 lg:order-1 fade-in-up" data-stagger-item>
                    <div class="glass-strong p-8 rounded-3xl">
                        <img src="https://placehold.co/600x400/F59E0B/FFFFFF/png?text=Automated+Billing" alt="Automated Billing" class="w-full rounded-2xl shadow-2xl">
                    </div>
                </div>
                <div class="order-1 lg:order-2 fade-in-up" data-stagger-item>
                    <div class="inline-flex items-center gap-3 mb-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-warning-400 to-warning-600 text-white font-bold text-xl shadow-lg shadow-warning-500/30">
                            3
                        </div>
                        <span class="text-sm font-semibold text-warning-600 dark:text-warning-400 uppercase tracking-wider">Step Three</span>
                    </div>
                    <h2 class="text-4xl font-bold text-zinc-900 dark:text-white mb-6">
                        Automated Billing
                    </h2>
                    <p class="text-lg text-zinc-600 dark:text-zinc-400 mb-8 leading-relaxed">
                        TogetherPay automatically creates billing cycles and sends payment requests. Choose between manual payment links or automatic Cash App Pay via Square.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-zinc-700 dark:text-zinc-300">Automatic billing cycle creation</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-zinc-700 dark:text-zinc-300">Email notifications to all members</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-zinc-700 dark:text-zinc-300">Flexible grace periods for late payments</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center" data-stagger>
                <div class="fade-in-up" data-stagger-item>
                    <div class="inline-flex items-center gap-3 mb-6">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-success-400 to-success-600 text-white font-bold text-xl shadow-lg shadow-success-500/30">
                            4
                        </div>
                        <span class="text-sm font-semibold text-success-600 dark:text-success-400 uppercase tracking-wider">Step Four</span>
                    </div>
                    <h2 class="text-4xl font-bold text-zinc-900 dark:text-white mb-6">
                        Get Paid
                    </h2>
                    <p class="text-lg text-zinc-600 dark:text-zinc-400 mb-8 leading-relaxed">
                        Members pay you directly through Venmo, Cash App, or automatic Cash App Pay. Track payments in real-time and never chase anyone for money again.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-success-100 dark:bg-success-900/30 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-zinc-700 dark:text-zinc-300">One-tap payment links with pre-filled amounts</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-success-100 dark:bg-success-900/30 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-zinc-700 dark:text-zinc-300">Real-time payment tracking dashboard</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-success-100 dark:bg-success-900/30 flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="text-zinc-700 dark:text-zinc-300">Automatic payment confirmation</span>
                        </li>
                    </ul>
                </div>
                <div class="fade-in-up" data-stagger-item>
                    <div class="glass-strong p-8 rounded-3xl">
                        <img src="https://placehold.co/600x400/10B981/FFFFFF/png?text=Get+Paid" alt="Get Paid" class="w-full rounded-2xl shadow-2xl">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Section -->
    <div class="relative py-20 lg:py-32 bg-white dark:bg-zinc-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-zinc-900 dark:text-white mb-6">
                    Flexible Payment Options
                </h2>
                <p class="text-xl text-zinc-600 dark:text-zinc-400 max-w-3xl mx-auto">
                    Choose the payment method that works best for your group
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8" data-stagger>
                <!-- Manual Payments -->
                <div class="card-elevated glass bg-white dark:bg-zinc-800 p-8 scale-in" data-stagger-item>
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-brand-100 dark:bg-brand-900/30">
                            <svg class="w-7 h-7 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">Manual Payouts</h3>
                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400 mt-1">Default</span>
                        </div>
                    </div>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                        Members receive payment links to Venmo or Cash App with pre-filled amounts. They tap the link and pay you directly.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <svg class="w-5 h-5 text-brand-600 dark:text-brand-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            No additional fees
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <svg class="w-5 h-5 text-brand-600 dark:text-brand-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Works with Venmo & Cash App
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <svg class="w-5 h-5 text-brand-600 dark:text-brand-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Simple one-tap payment
                        </li>
                    </ul>
                </div>

                <!-- Auto Payments via Square -->
                <div class="card-elevated glass bg-white dark:bg-zinc-800 p-8 scale-in" data-stagger-item>
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-warning-100 dark:bg-warning-900/30">
                            <svg class="w-7 h-7 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">Auto Payments</h3>
                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400 mt-1">Hands-free</span>
                        </div>
                    </div>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                        Connect your Square account and let TogetherPay automatically charge members. Completely hands-free with multiple payment options.
                    </p>
                    
                    <!-- Payment Method Icons -->
                    <div class="flex flex-wrap gap-3 mb-6 pb-6 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-2 px-3 py-2 bg-zinc-100 dark:bg-zinc-700/50 rounded-lg">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                <rect x="2" y="6" width="20" height="12" rx="2" stroke="currentColor" stroke-width="2"/>
                                <path d="M2 10h20" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Cards</span>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-2 bg-zinc-100 dark:bg-zinc-700/50 rounded-lg">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.31-8.86c-1.77-.45-2.34-.94-2.34-1.67 0-.84.79-1.43 2.1-1.43 1.38 0 1.9.66 1.94 1.64h1.71c-.05-1.34-.87-2.57-2.49-2.97V5H10.9v1.69c-1.51.32-2.72 1.3-2.72 2.81 0 1.79 1.49 2.69 3.66 3.21 1.95.46 2.34 1.15 2.34 1.87 0 .53-.39 1.39-2.1 1.39-1.6 0-2.23-.72-2.32-1.64H8.04c.1 1.7 1.36 2.66 2.86 2.97V19h2.34v-1.67c1.52-.29 2.72-1.16 2.73-2.77-.01-2.2-1.9-2.96-3.66-3.42z"/>
                            </svg>
                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Cash App</span>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-2 bg-zinc-100 dark:bg-zinc-700/50 rounded-lg">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.88-11.71L10 14.17l-1.88-1.88-1.41 1.41L10 17l7.3-7.3-1.42-1.41z"/>
                            </svg>
                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Apple Pay</span>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-2 bg-zinc-100 dark:bg-zinc-700/50 rounded-lg">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                            </svg>
                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Google Pay</span>
                        </div>
                    </div>

                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <svg class="w-5 h-5 text-warning-600 dark:text-warning-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Fully automated payments
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <svg class="w-5 h-5 text-warning-600 dark:text-warning-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Multiple payment methods
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <svg class="w-5 h-5 text-warning-600 dark:text-warning-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Requires Square account
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <svg class="w-5 h-5 text-warning-600 dark:text-warning-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Zero effort collection
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="relative py-20 lg:py-32 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-brand-500 via-brand-600 to-accent-teal opacity-10"></div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-5xl font-bold text-zinc-900 dark:text-white mb-6">
                Ready to Split Bills Smarter?
            </h2>
            <p class="text-xl text-zinc-600 dark:text-zinc-400 mb-10">
                Download TogetherPay today and never chase friends for money again.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <!-- App Store Button -->
                <a href="#download-ios" class="inline-flex items-center px-6 py-3.5 bg-black dark:bg-white rounded-xl hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-all hover:scale-105 shadow-lg group">
                    <svg class="w-10 h-10 mr-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.53 4.08l-.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z" fill="currentColor" class="text-white dark:text-black"/>
                    </svg>
                    <div class="text-left">
                        <div class="text-xs text-white/80 dark:text-black/80 font-normal">Download on the</div>
                        <div class="text-xl font-semibold text-white dark:text-black -mt-0.5">App Store</div>
                    </div>
                </a>

                <!-- Google Play Button -->
                <a href="#download-android" class="inline-flex items-center px-6 py-3.5 bg-black dark:bg-white rounded-xl hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-all hover:scale-105 shadow-lg group">
                    <svg class="w-10 h-10 mr-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3.609 3.784c-.18.244-.284.585-.284 1.002v14.428c0 .417.104.758.284 1.002l.022.021 8.084-8.084v-.192L3.631 3.763l-.022.021z" fill="#00D7FE" class="dark:opacity-80"/>
                        <path d="M14.411 14.747l-2.696-2.696v-.192l2.696-2.696.03.018 3.194 1.814c.912.518.912 1.365 0 1.883l-3.194 1.814-.03.055z" fill="#FFCE00" class="dark:opacity-80"/>
                        <path d="M14.441 14.692l-2.726-2.726L3.609 20.07c.301.32.799.36 1.365.024l9.467-5.402z" fill="#00AB4E" class="dark:opacity-80"/>
                        <path d="M14.441 9.308L4.974 3.906c-.566-.336-1.064-.296-1.365.024l8.106 8.104 2.726-2.726z" fill="#FF3E4C" class="dark:opacity-80"/>
                    </svg>
                    <div class="text-left">
                        <div class="text-xs text-white/80 dark:text-black/80 font-normal">GET IT ON</div>
                        <div class="text-xl font-semibold text-white dark:text-black -mt-0.5">Google Play</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Download Section -->
    <div id="download-ios" class="relative py-20 lg:py-32 bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-500/10 border border-brand-500/20 backdrop-blur-md mb-8">
                <span class="flex h-2 w-2 rounded-full bg-brand-500 animate-pulse"></span>
                <span class="text-sm font-medium text-brand-400">Coming Soon</span>
            </div>
            
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                Available Soon on iOS & Android
            </h2>
            <p class="text-xl text-zinc-400 mb-10 max-w-2xl mx-auto">
                We're putting the finishing touches on the TogetherPay mobile app. Sign up to be notified when we launch!
            </p>

            <!-- Email Signup Form -->
            <!-- Email Signup Form -->
            <livewire:notify-me />

            <!-- Platform Badges -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center opacity-50">
                <div class="inline-flex items-center px-6 py-3.5 bg-white/5 border border-white/10 rounded-xl">
                    <svg class="w-10 h-10 mr-3 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.53 4.08l-.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z" fill="currentColor"/>
                    </svg>
                    <div class="text-left">
                        <div class="text-xs text-white/60 font-normal">Coming to</div>
                        <div class="text-xl font-semibold text-white -mt-0.5">App Store</div>
                    </div>
                </div>

                <div id="download-android" class="inline-flex items-center px-6 py-3.5 bg-white/5 border border-white/10 rounded-xl">
                    <svg class="w-10 h-10 mr-3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3.609 3.784c-.18.244-.284.585-.284 1.002v14.428c0 .417.104.758.284 1.002l.022.021 8.084-8.084v-.192L3.631 3.763l-.022.021z" fill="#00D7FE" class="opacity-60"/>
                        <path d="M14.411 14.747l-2.696-2.696v-.192l2.696-2.696.03.018 3.194 1.814c.912.518.912 1.365 0 1.883l-3.194 1.814-.03.055z" fill="#FFCE00" class="opacity-60"/>
                        <path d="M14.441 14.692l-2.726-2.726L3.609 20.07c.301.32.799.36 1.365.024l9.467-5.402z" fill="#00AB4E" class="opacity-60"/>
                        <path d="M14.441 9.308L4.974 3.906c-.566-.336-1.064-.296-1.365.024l8.106 8.104 2.726-2.726z" fill="#FF3E4C" class="opacity-60"/>
                    </svg>
                    <div class="text-left">
                        <div class="text-xs text-white/60 font-normal">Coming to</div>
                        <div class="text-xl font-semibold text-white -mt-0.5">Google Play</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <!-- Footer -->
    <x-guest-footer />
</div>
