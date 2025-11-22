<?php

use function Livewire\Volt\{layout, title};

layout('components.layouts.guest');
title('TogetherPay - Split Bills, Not Friendships');

?>

<div class="min-h-screen selection:bg-brand-500 selection:text-white overflow-x-hidden">
    <!-- Navbar -->
    <!-- Navbar -->
    <x-guest-navbar />

    <!-- Hero Section -->
    <div class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <!-- Animated Background Layers -->
        <div class="bg-animated-aurora"></div>
        <div class="bg-animated-grid"></div>

        <!-- Floating Orbs -->
        <div class="bg-floating-orbs">
            <div class="floating-orb floating-orb-1"></div>
            <div class="floating-orb floating-orb-2"></div>
            <div class="floating-orb floating-orb-3"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 backdrop-blur-md mb-8 animate-fade-in-up">
                <span class="flex h-2 w-2 rounded-full bg-brand-500 animate-pulse"></span>
                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Available Soon on iOS & Android</span>
            </div>

            <h1 class="text-5xl md:text-7xl font-bold tracking-tight text-zinc-900 dark:text-white mb-8 animate-fade-in-up" style="animation-delay: 0.1s">
                Split Bills Together,<br>
                <span class="text-gradient-hero">Pay Separately.</span>
            </h1>

            <p class="max-w-2xl mx-auto text-xl text-zinc-600 dark:text-zinc-400 mb-12 leading-relaxed animate-fade-in-up" style="animation-delay: 0.2s">
                Manage shared expenses effortlessly. Create groups, send payment requests via Venmo or Cash App, and track collections with automated reminders.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-in-up" style="animation-delay: 0.3s">
                <a href="#download-ios" class="btn-primary-glow group">
                    <svg class="w-8 h-8 mr-3 transition-transform group-hover:scale-110" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#2196f3" d="M44,24c0,11.044-8.956,20-20,20S4,35.044,4,24S12.956,4,24,4S44,12.956,44,24z"></path>
                        <path fill="#fff" d="M31.684 23.867l-2.48 1.441L22.43 13.652c-.27-.465-.113-1.063.355-1.336l.793-.461c.465-.27 1.063-.113 1.332.355L31.684 23.867zM29.48 25.789l2.48-1.441 1.438 2.469-2.48 1.441L29.48 25.789zM13.84 28.152l5.988-10.246 2.555 1.492-5.988 10.246L13.84 28.152zM12.285 33.375l1.234-4.676 2.555 1.496-3.469 3.367c-.059.063-.156.074-.23.031C12.297 33.547 12.262 33.461 12.285 33.375M20.035 17.547l.863-1.469c.27-.469.871-.625 1.336-.352l.867.508c.465.273.621.871.348 1.336l-.859 1.473L20.035 17.547zM33.023 27.578c-.402.23-1.004.586-1.359.797-.664.395-.152 1.559 0 1.809.859 1.441 1.746 1.238 2.414 2.258.367.559.266.805.379.992.047.066.191.133.246.055 1.031-1.426.73-3.879-.02-4.973C34.336 28.004 33.703 27.191 33.023 27.578M36.637 25.41h-3.563l-1.555-2.855h5.117V25.41zM28.617 25.41h-9.293l1.586-2.855h6.121L28.617 25.41zM14.945 25.41h-3.617v-2.855h5.309L14.945 25.41z">
                        </path>
                    </svg>
                    <span>Download on iOS</span>
                </a>
                <a href="#download-android" class="btn-secondary-glass group">
                    <svg class="w-8 h-8 mr-3 transition-transform group-hover:scale-110" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#4db6ac" d="M7.705,4.043C7.292,4.15,7,4.507,7,5.121c0,1.802,0,18.795,0,18.795S7,42.28,7,43.091c0,0.446,0.197,0.745,0.5,0.856l20.181-20.064L7.705,4.043z">
                        </path>
                        <path fill="#dce775" d="M33.237,18.36l-8.307-4.796c0,0-15.245-8.803-16.141-9.32C8.401,4.02,8.019,3.961,7.705,4.043l19.977,19.84L33.237,18.36z">
                        </path>
                        <path fill="#d32f2f" d="M8.417,43.802c0.532-0.308,15.284-8.825,24.865-14.357l-5.601-5.562L7.5,43.947C7.748,44.038,8.066,44.004,8.417,43.802z">
                        </path>
                        <path fill="#fbc02d" d="M41.398,23.071c-0.796-0.429-8.1-4.676-8.1-4.676l-0.061-0.035l-5.556,5.523l5.601,5.562c4.432-2.559,7.761-4.48,8.059-4.653C42.285,24.248,42.194,23.5,41.398,23.071z">
                        </path>
                    </svg>
                    <span>Get it on Android</span>
                </a>
            </div>

            <!-- Hero Image/Mockup -->
            <div class="mt-20 relative max-w-5xl mx-auto animate-fade-in-up" style="animation-delay: 0.5s">
                <div class="absolute inset-0 bg-gradient-to-t from-zinc-50 dark:from-zinc-950 via-transparent to-transparent z-20 h-full w-full pointer-events-none"></div>
                <div class="relative glass-premium rounded-3xl p-4 md:p-8 transform rotate-x-12 perspective-1000">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Mockup Card 1 -->
                        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-6 shadow-sm border border-zinc-100 dark:border-zinc-800 flex flex-col gap-4 animate-float">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center text-brand-600 dark:text-brand-400 font-bold">JD</div>
                                    <div>
                                        <div class="text-sm font-bold text-zinc-900 dark:text-white">John Doe</div>
                                        <div class="text-xs text-zinc-500">Paid via Venmo</div>
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-brand-600 dark:text-brand-400">+$45.00</span>
                            </div>
                            <div class="h-2 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                <div class="h-full w-full bg-brand-500 rounded-full"></div>
                            </div>
                        </div>
                        <!-- Mockup Card 2 -->
                        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-6 shadow-sm border border-zinc-100 dark:border-zinc-800 flex flex-col gap-4 animate-float-delayed">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-accent-teal/10 flex items-center justify-center text-accent-teal font-bold">AS</div>
                                    <div>
                                        <div class="text-sm font-bold text-zinc-900 dark:text-white">Alice Smith</div>
                                        <div class="text-xs text-zinc-500">Pending Request</div>
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-zinc-400">$32.50</span>
                            </div>
                            <div class="flex gap-2">
                                <button class="flex-1 py-2 text-xs font-medium bg-brand-600 text-white rounded-lg">Remind</button>
                                <button class="flex-1 py-2 text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 rounded-lg">Skip</button>
                            </div>
                        </div>
                        <!-- Mockup Card 3 -->
                        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-6 shadow-sm border border-zinc-100 dark:border-zinc-800 flex flex-col gap-4 animate-float">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-accent-amber/10 flex items-center justify-center text-accent-amber font-bold">MK</div>
                                    <div>
                                        <div class="text-sm font-bold text-zinc-900 dark:text-white">Mike K.</div>
                                        <div class="text-xs text-zinc-500">Paid via Cash App</div>
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-brand-600 dark:text-brand-400">+$28.00</span>
                            </div>
                            <div class="h-2 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                <div class="h-full w-full bg-brand-500 rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Grid -->
    <div id="features" class="py-24 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <h2 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-6">Everything you need to manage shared bills</h2>
                <p class="text-lg text-zinc-600 dark:text-zinc-400">Simple, transparent, and fee-free payment collection designed for roommates, trips, and groups.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" data-stagger>
                <!-- Feature 1 -->
                <div class="glass-card p-8 rounded-3xl group" data-stagger-item>
                    <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-3">Create Groups</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Set up billing groups for shared expenses like phone plans, utilities, or subscriptions.</p>
                </div>

                <!-- Feature 2 -->
                <div class="glass-card p-8 rounded-3xl group" data-stagger-item>
                    <div class="w-14 h-14 rounded-2xl bg-accent-teal/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-accent-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-3">Instant Requests</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Generate and send payment requests via Venmo or Cash App instantly.</p>
                </div>

                <!-- Feature 3 -->
                <div class="glass-card p-8 rounded-3xl group" data-stagger-item>
                    <div class="w-14 h-14 rounded-2xl bg-accent-amber/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-accent-amber" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-3">Smart Reminders</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Automated follow-ups keep payments on track without the awkward conversations.</p>
                </div>

                <!-- Feature 4 -->
                <div class="glass-card p-8 rounded-3xl group" data-stagger-item>
                    <div class="w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-900/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-3">Analytics</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Track collection rates and view payment history with detailed reports.</p>
                </div>

                <!-- Feature 5 -->
                <div class="glass-card p-8 rounded-3xl group" data-stagger-item>
                    <div class="w-14 h-14 rounded-2xl bg-accent-teal/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-accent-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-3">Recurring Billing</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Set up monthly cycles. Requests are generated and sent automatically.</p>
                </div>

                <!-- Feature 6 -->
                <div class="glass-card p-8 rounded-3xl group" data-stagger-item>
                    <div class="w-14 h-14 rounded-2xl bg-accent-amber/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-accent-amber" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-3">Secure & Private</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">We never handle payments directly. All transactions go through trusted P2P apps.</p>
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
