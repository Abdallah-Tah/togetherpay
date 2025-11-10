<?php

use function Livewire\Volt\{layout, title};

layout('components.layouts.guest');
title('About TogetherPay');

?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">About TogetherPay</h1>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <!-- Mission -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Our Mission</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        TogetherPay simplifies the process of managing shared expenses and collecting payments from group members.
                        We believe that splitting bills shouldn't be complicated, stressful, or expensive.
                    </p>
                </div>

                <!-- How It Works -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">How It Works</h2>
                    <div class="space-y-4 text-gray-700 dark:text-gray-300">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-brand-500 text-white font-bold">1</div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Create a Group</h3>
                                <p class="mt-1">Set up a billing group for any shared expense - phone plans, utilities, subscriptions, or rent.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-brand-500 text-white font-bold">2</div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Invite Members</h3>
                                <p class="mt-1">Add members via email invitation and define each person's share of the total bill.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-brand-500 text-white font-bold">3</div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Automate Requests</h3>
                                <p class="mt-1">Payment requests are sent automatically via Venmo or Cash App based on your billing schedule.</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-brand-500 text-white font-bold">4</div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Track & Report</h3>
                                <p class="mt-1">Monitor payments in real-time, send reminders, and generate reports for accounting.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Why TogetherPay -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Why Choose TogetherPay?</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">✓ No Transaction Fees</h3>
                            <p class="text-gray-700 dark:text-gray-300">Members pay you directly via Venmo or Cash App - we never touch the money.</p>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">✓ Automated Reminders</h3>
                            <p class="text-gray-700 dark:text-gray-300">Smart follow-ups ensure timely payments without awkward conversations.</p>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">✓ Transparent Tracking</h3>
                            <p class="text-gray-700 dark:text-gray-300">Real-time status updates so everyone knows who's paid and who's pending.</p>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">✓ Flexible Billing</h3>
                            <p class="text-gray-700 dark:text-gray-300">Monthly, 4-week, or custom billing cycles to match any payment schedule.</p>
                        </div>
                    </div>
                </div>

                <!-- Technology -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Built with Modern Technology</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                        TogetherPay is built on a modern technology stack designed for security, reliability, and performance:
                    </p>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                        <li><strong>Laravel 12</strong> - Robust PHP framework for secure backend operations</li>
                        <li><strong>Flutter</strong> - Cross-platform mobile app for iOS and Android</li>
                        <li><strong>PostgreSQL</strong> - Reliable database with data encryption at rest</li>
                        <li><strong>AWS</strong> - Cloud infrastructure for scalability and uptime</li>
                    </ul>
                </div>

                <!-- Contact CTA -->
                <div class="bg-brand-50 dark:bg-gray-700 rounded-lg p-6 mt-8">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Have Questions?</h2>
                    <p class="text-gray-700 dark:text-gray-300 mb-4">
                        We'd love to hear from you. Whether you have a question about features, pricing, or anything else, our team is ready to help.
                    </p>
                    <a href="{{ route('contact') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700">
                        Contact Us
                    </a>
                </div>
            </div>
        </div>

        <!-- Back to Home -->
        <div class="mt-6 text-center">
            <a href="{{ route('welcome') }}" class="text-brand-600 dark:text-brand-400 hover:text-brand-500">
                ← Back to Home
            </a>
        </div>
    </div>
</div>
