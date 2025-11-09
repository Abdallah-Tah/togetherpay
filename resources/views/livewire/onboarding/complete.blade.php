<?php

use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\{mount};

mount(function () {
    // Mark onboarding as completed
    Auth::user()->completeOnboarding();
});

?>

<div>
    <x-slot:title>Welcome Aboard!</x-slot:title>

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-2xl w-full">
            <!-- Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-center space-x-2">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-success-600 flex items-center justify-center text-white shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="ml-2 text-sm font-medium text-success-600">Welcome</span>
                    </div>
                    <div class="w-16 h-1 bg-success-600 mx-2"></div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-success-600 flex items-center justify-center text-white shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="ml-2 text-sm font-medium text-success-600">Setup</span>
                    </div>
                    <div class="w-16 h-1 bg-success-600 mx-2"></div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-success-600 flex items-center justify-center text-white font-bold shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="ml-2 text-sm font-medium text-success-600">Complete</span>
                    </div>
                </div>
            </div>

            <!-- Completion Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-3xl shadow-2xl overflow-hidden border border-zinc-200 dark:border-zinc-700">
                <!-- Success Header -->
                <div class="bg-gradient-to-br from-success-500 via-success-600 to-success-700 px-8 py-12 text-white text-center">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-white/20 backdrop-blur-sm mb-6 animate-in zoom-in duration-500">
                        <svg class="w-14 h-14 animate-in zoom-in duration-700 delay-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold mb-3">You're All Set!</h1>
                    <p class="text-xl text-success-100">Welcome to TogetherPay, {{ auth()->user()->name }}!</p>
                </div>

                <!-- Content -->
                <div class="p-8">
                    <p class="text-center text-lg text-zinc-600 dark:text-zinc-300 mb-8">
                        Your account is ready. Let's get you started with your first group!
                    </p>

                    <!-- Next Steps -->
                    <div class="space-y-4 mb-8">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">What's Next?</h3>

                        <!-- Step 1 -->
                        <div class="flex items-start space-x-4 p-4 rounded-2xl bg-brand-50 dark:bg-brand-900/20 border border-brand-100 dark:border-brand-800">
                            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-brand-600 text-white flex items-center justify-center font-bold">
                                1
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-zinc-900 dark:text-white mb-1">Create Your First Group</h4>
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">Set up a group for your shared bills like rent, utilities, or subscriptions</p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="flex items-start space-x-4 p-4 rounded-2xl bg-success-50 dark:bg-success-900/20 border border-success-100 dark:border-success-800">
                            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-success-600 text-white flex items-center justify-center font-bold">
                                2
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-zinc-900 dark:text-white mb-1">Add Group Members</h4>
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">Invite your friends or family with their payment details</p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="flex items-start space-x-4 p-4 rounded-2xl bg-warning-50 dark:bg-warning-900/20 border border-warning-100 dark:border-warning-800">
                            <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-warning-600 text-white flex items-center justify-center font-bold">
                                3
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-zinc-900 dark:text-white mb-1">Sit Back & Relax</h4>
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">We'll handle payment requests and reminders automatically</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tip Box -->
                    <div class="bg-gradient-to-br from-brand-50 to-brand-100 dark:from-brand-900/20 dark:to-brand-800/20 border border-brand-200 dark:border-brand-800 rounded-2xl p-5 mb-8">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-brand-900 dark:text-brand-100 mb-1">Pro Tip</h4>
                                <p class="text-sm text-brand-800 dark:text-brand-200">
                                    Start with one small group to get familiar with how TogetherPay works. You can always create more groups later!
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('app.groups.create') }}" wire:navigate
                           class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-2xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Your First Group
                        </a>
                        <a href="{{ route('dashboard') }}" wire:navigate
                           class="flex-1 inline-flex items-center justify-center px-6 py-4 border-2 border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 font-semibold rounded-2xl hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-all duration-200">
                            Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Help Section -->
            <div class="text-center mt-8 space-y-2">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Need help getting started?
                </p>
                <div class="flex items-center justify-center space-x-4">
                    <a href="#" class="text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 font-medium">
                        View Tutorial
                    </a>
                    <span class="text-zinc-300 dark:text-zinc-600">•</span>
                    <a href="#" class="text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300 font-medium">
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
