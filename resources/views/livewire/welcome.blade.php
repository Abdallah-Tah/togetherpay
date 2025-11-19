<?php

use function Livewire\Volt\{layout, title};

layout('components.layouts.guest');
title('TogetherPay - Split Bills, Not Friendships');

?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-950 selection:bg-brand-500 selection:text-white overflow-x-hidden">
    <!-- Navbar -->
    <nav class="fixed top-0 w-full z-50 transition-all duration-300" id="navbar">
        <div class="absolute inset-0 bg-white/70 dark:bg-zinc-950/70 backdrop-blur-xl border-b border-zinc-200/50 dark:border-zinc-800/50"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-3">
                    <x-app-logo class="w-10 h-10 rounded-xl shadow-lg shadow-brand-500/20" />
                    <span class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">TogetherPay</span>
                </div>
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Features</a>
                    <a href="#how-it-works" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">How it Works</a>
                    <a href="{{ route('login') }}" class="text-sm font-medium text-zinc-900 dark:text-white hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Log in</a>
                    <a href="{{ route('register') }}" class="px-5 py-2.5 text-sm font-semibold text-white bg-zinc-900 dark:bg-white dark:text-zinc-900 rounded-xl hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-all hover:-translate-y-0.5 shadow-lg shadow-zinc-900/20 dark:shadow-white/10">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full z-0 pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-brand-500/10 rounded-full blur-[120px] animate-pulse-slow"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-accent-purple/10 rounded-full blur-[120px] animate-pulse-slow" style="animation-delay: 2s"></div>
            <div class="absolute top-[20%] right-[20%] w-[30%] h-[30%] bg-accent-pink/10 rounded-full blur-[100px] animate-pulse-slow" style="animation-delay: 4s"></div>
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
                                    <div class="w-10 h-10 rounded-full bg-accent-purple/10 flex items-center justify-center text-accent-purple font-bold">AS</div>
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
                                    <div class="w-10 h-10 rounded-full bg-accent-pink/10 flex items-center justify-center text-accent-pink font-bold">MK</div>
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
                    <div class="w-14 h-14 rounded-2xl bg-accent-purple/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-accent-purple" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-3">Instant Requests</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Generate and send payment requests via Venmo or Cash App instantly.</p>
                </div>

                <!-- Feature 3 -->
                <div class="glass-card p-8 rounded-3xl group" data-stagger-item>
                    <div class="w-14 h-14 rounded-2xl bg-accent-pink/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-accent-pink" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                    <div class="w-14 h-14 rounded-2xl bg-accent-purple/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-accent-purple" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-3">Recurring Billing</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">Set up monthly cycles. Requests are generated and sent automatically.</p>
                </div>

                <!-- Feature 6 -->
                <div class="glass-card p-8 rounded-3xl group" data-stagger-item>
                    <div class="w-14 h-14 rounded-2xl bg-accent-pink/10 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-accent-pink" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
    <div class="py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-zinc-900 dark:bg-black">
            <div class="absolute inset-0 bg-gradient-to-br from-brand-900/50 to-transparent"></div>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-8">Ready to simplify bill splitting?</h2>
            <p class="text-xl text-zinc-300 mb-12 max-w-2xl mx-auto">Join thousands of users who have stopped chasing payments and started enjoying their shared experiences.</p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="#download-ios" class="btn-primary-glow group min-w-[200px]">
                    <span>Download Now</span>
                    <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
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
