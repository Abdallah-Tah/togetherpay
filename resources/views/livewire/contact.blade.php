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

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Contact Us</h1>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Contact Information -->
            <div>
                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Get in Touch</h2>

                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Email</h3>
                            <p class="text-gray-700 dark:text-gray-300">
                                <a href="mailto:support@togetherpay.com" class="text-brand-600 dark:text-brand-400 hover:underline">
                                    support@togetherpay.com
                                </a>
                            </p>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Response Time</h3>
                            <p class="text-gray-700 dark:text-gray-300">
                                We typically respond within 24-48 hours during business days.
                            </p>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Support Hours</h3>
                            <p class="text-gray-700 dark:text-gray-300">
                                Monday - Friday: 9:00 AM - 5:00 PM EST<br>
                                Saturday - Sunday: Closed
                            </p>
                        </div>

                        <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Other Resources</h3>
                            <ul class="space-y-2">
                                <li>
                                    <a href="{{ route('about') }}" class="text-brand-600 dark:text-brand-400 hover:underline">
                                        About TogetherPay
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('privacy') }}" class="text-brand-600 dark:text-brand-400 hover:underline">
                                        Privacy Policy
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('terms') }}" class="text-brand-600 dark:text-brand-400 hover:underline">
                                        Terms of Service
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div>
                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Send us a Message</h2>

                    @if($submitted)
                        <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                                        Thank you for contacting us! We'll get back to you soon.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form wire:submit="submit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Name *
                            </label>
                            <input
                                type="text"
                                id="name"
                                wire:model="name"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                required
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Email Address *
                            </label>
                            <input
                                type="email"
                                id="email"
                                wire:model="email"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                required
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Subject -->
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Subject *
                            </label>
                            <input
                                type="text"
                                id="subject"
                                wire:model="subject"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                required
                            >
                            @error('subject')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Message -->
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Message *
                            </label>
                            <textarea
                                id="message"
                                wire:model="message"
                                rows="6"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                required
                            ></textarea>
                            @error('message')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <button
                                type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500"
                            >
                                Send Message
                            </button>
                        </div>
                    </form>
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
