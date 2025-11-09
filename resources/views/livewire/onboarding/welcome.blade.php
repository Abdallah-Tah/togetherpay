<div>
    <x-slot:title>Welcome to TogetherPay</x-slot:title>

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-3xl w-full">
            <!-- Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-center space-x-2">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-bold shadow-lg">
                            1
                        </div>
                        <span class="ml-2 text-sm font-medium text-brand-600">Welcome</span>
                    </div>
                    <div class="w-16 h-1 bg-zinc-200 dark:bg-zinc-700 mx-2"></div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-zinc-500 font-bold">
                            2
                        </div>
                        <span class="ml-2 text-sm font-medium text-zinc-400">Setup</span>
                    </div>
                    <div class="w-16 h-1 bg-zinc-200 dark:bg-zinc-700 mx-2"></div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-zinc-500 font-bold">
                            3
                        </div>
                        <span class="ml-2 text-sm font-medium text-zinc-400">Complete</span>
                    </div>
                </div>
            </div>

            <!-- Welcome Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-3xl shadow-2xl overflow-hidden border border-zinc-200 dark:border-zinc-700">
                <!-- Hero Section -->
                <div class="bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 px-8 py-12 text-white text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-sm mb-6">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold mb-3">Welcome to TogetherPay!</h1>
                    <p class="text-xl text-brand-100">Split bills effortlessly with your group</p>
                </div>

                <!-- Features Grid -->
                <div class="px-8 py-10">
                    <p class="text-center text-lg text-zinc-600 dark:text-zinc-300 mb-10">
                        Let's get you set up in less than 2 minutes
                    </p>

                    <div class="grid md:grid-cols-3 gap-6 mb-10">
                        <!-- Feature 1 -->
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-brand-100 dark:bg-brand-900/30 mb-4">
                                <svg class="w-8 h-8 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Create Groups</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Organize your friends, family, or roommates into bill-splitting groups</p>
                        </div>

                        <!-- Feature 2 -->
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-success-100 dark:bg-success-900/30 mb-4">
                                <svg class="w-8 h-8 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Auto Requests</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Automatic payment requests sent via Venmo or Cash App on schedule</p>
                        </div>

                        <!-- Feature 3 -->
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-warning-100 dark:bg-warning-900/30 mb-4">
                                <svg class="w-8 h-8 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Track & Report</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Monitor payments and generate detailed reports for your records</p>
                        </div>
                    </div>

                    <!-- How It Works -->
                    <div class="bg-zinc-50 dark:bg-zinc-900 rounded-2xl p-6 mb-8">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 text-center">How It Works</h3>
                        <div class="space-y-3">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-600 text-white flex items-center justify-center text-xs font-bold">1</div>
                                <p class="text-sm text-zinc-600 dark:text-zinc-300 pt-0.5">Set up your payment preferences (Venmo or Cash App)</p>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-600 text-white flex items-center justify-center text-xs font-bold">2</div>
                                <p class="text-sm text-zinc-600 dark:text-zinc-300 pt-0.5">Create groups and add members with their payment details</p>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-600 text-white flex items-center justify-center text-xs font-bold">3</div>
                                <p class="text-sm text-zinc-600 dark:text-zinc-300 pt-0.5">We automatically send payment requests and track who's paid</p>
                            </div>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <div class="text-center">
                        <a href="{{ route('onboarding.payment-setup') }}" wire:navigate
                           class="inline-flex items-center px-8 py-4 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-2xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            Get Started
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>
                        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No credit card required • Free to start</p>
                    </div>
                </div>
            </div>

            <!-- Skip Option -->
            <div class="text-center mt-6">
                <a href="{{ route('dashboard') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">
                    Skip for now
                </a>
            </div>
        </div>
    </div>
</div>
