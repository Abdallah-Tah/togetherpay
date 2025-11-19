<?php

use function Livewire\Volt\{state, rules, layout, title};

layout('components.layouts.guest');
title('Contact Us - TogetherPay');

state(['name' => '', 'email' => '', 'subject' => '', 'message' => '', 'submitted' => false]);

rules([
    'name' => 'required|string|max:255',
    'email' => 'required|email|max:255',
    'subject' => 'required|string|max:255',
    'message' => 'required|string|min:10',
]);

$submit = function () {
    $this->validate();

    // Log the contact form submission
    \Log::info('Contact form submission', [
        'name' => $this->name,
        'email' => $this->email,
        'subject' => $this->subject,
        'message' => $this->message,
    ]);

    // TODO: Send email notification to support team
    // For now, just mark as submitted
    $this->submitted = true;

    // Reset form
    $this->name = '';
    $this->email = '';
    $this->subject = '';
    $this->message = '';
};

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
            <h1 class="text-4xl md:text-5xl font-bold text-zinc-900 dark:text-white mb-4 animate-fade-in-up">Contact TogetherPay</h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto animate-fade-in-up" style="animation-delay: 0.1s;">
                We’re a small team that answers every message personally. Ask about features, billing, or how Square autopay works—we’re happy to help.
            </p>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto pb-24 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Contact Information -->
            <div class="animate-fade-in-up" style="animation-delay: 0.2s">
                <div class="glass-card p-8 rounded-3xl h-full">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">Get in Touch</h2>

                    <div class="space-y-8">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-900/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-1">Email</h3>
                                <p class="text-zinc-600 dark:text-zinc-400 mb-2">For general inquiries and support:</p>
                                <a href="mailto:support@togetherpay.com" class="text-brand-600 dark:text-brand-400 font-medium hover:underline">
                                    support@togetherpay.com
                                </a>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-900/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-1">Response Time</h3>
                                <p class="text-zinc-600 dark:text-zinc-400">
                                    We typically respond within 24-48 hours during business days.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-900/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-1">Support Hours</h3>
                                <p class="text-zinc-600 dark:text-zinc-400">
                                    Monday - Friday: 9:00 AM - 5:00 PM EST<br>
                                    Saturday - Sunday: Closed
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="animate-fade-in-up" style="animation-delay: 0.3s">
                <div class="glass-premium rounded-3xl p-8 border border-white/40 dark:border-white/5">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">Send us a Message</h2>

                    @if($submitted)
                        <div class="rounded-2xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 p-6 mb-6 animate-fade-in">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-green-900 dark:text-green-100 mb-1">Message Sent!</h3>
                                    <p class="text-green-700 dark:text-green-300">
                                        Thank you for contacting us! We'll get back to you soon.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form wire:submit="submit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                wire:model="name"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-700 bg-white/50 dark:bg-zinc-900/50 text-zinc-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 transition-all"
                                placeholder="Your name"
                                required
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="email"
                                id="email"
                                wire:model="email"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-700 bg-white/50 dark:bg-zinc-900/50 text-zinc-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 transition-all"
                                placeholder="you@example.com"
                                required
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Subject -->
                        <div>
                            <label for="subject" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Subject <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="subject"
                                wire:model="subject"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-700 bg-white/50 dark:bg-zinc-900/50 text-zinc-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 transition-all"
                                placeholder="How can we help?"
                                required
                            >
                            @error('subject')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Message -->
                        <div>
                            <label for="message" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Message <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                id="message"
                                wire:model="message"
                                rows="5"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-700 bg-white/50 dark:bg-zinc-900/50 text-zinc-900 dark:text-white focus:border-brand-500 focus:ring-brand-500 transition-all resize-none"
                                placeholder="Tell us more..."
                                required
                            ></textarea>
                            @error('message')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                class="w-full btn-primary-glow justify-center"
                            >
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Back to Home -->
        <div class="mt-12 text-center">
            <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2 text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 font-medium transition-colors group">
                <svg class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Home
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
