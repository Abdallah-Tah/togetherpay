<?php

use function Livewire\Volt\{layout, title};

layout('components.layouts.guest');
title('Privacy Policy - TogetherPay');

?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-950 selection:bg-brand-500 selection:text-white overflow-x-hidden">
    <!-- Navbar -->
    <!-- Navbar -->
    <x-guest-navbar />

    <!-- Header -->
    <div class="relative pt-32 pb-12 overflow-hidden">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full z-0 pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-brand-400/5 dark:bg-brand-500/5 rounded-full blur-[120px] animate-pulse-slow"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-brand-300/5 dark:bg-accent-teal/5 rounded-full blur-[120px] animate-pulse-slow" style="animation-delay: 2s"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center z-10">
            <h1 class="text-4xl md:text-5xl font-bold text-zinc-900 dark:text-white mb-4 animate-fade-in-up">Privacy Policy</h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400 animate-fade-in-up" style="animation-delay: 0.1s;">Last updated: {{ date('F j, Y') }}</p>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto pb-24 px-4 sm:px-6 lg:px-8">
        <div class="glass-premium rounded-3xl overflow-hidden border border-white/40 dark:border-white/5">
            <div class="px-6 py-8 sm:p-12 prose prose-lg dark:prose-invert max-w-none prose-headings:text-zinc-900 dark:prose-headings:text-white prose-p:text-zinc-600 dark:prose-p:text-zinc-400 prose-strong:text-zinc-900 dark:prose-strong:text-white prose-a:text-brand-600 dark:prose-a:text-brand-400 prose-li:text-zinc-600 dark:prose-li:text-zinc-400">

                <section class="mb-8 animate-fade-in-up" style="animation-delay: 0.2s">
                    <h2 class="text-2xl font-bold mb-4">Introduction</h2>
                    <p class="leading-relaxed">
                        TogetherPay ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our service.
                    </p>
                </section>

                <section class="mb-8 animate-fade-in-up" style="animation-delay: 0.3s">
                    <h2 class="text-2xl font-bold mb-4">Information We Collect</h2>

                    <h3 class="text-xl font-semibold mb-3">Personal Information</h3>
                    <p class="leading-relaxed mb-3">
                        We collect information that you provide directly to us, including:
                    </p>
                    <ul class="list-disc list-inside space-y-2 mb-4">
                        <li>Name and email address</li>
                        <li>Phone number (optional)</li>
                        <li>Payment channel handles (Venmo/Cash App usernames)</li>
                        <li>Billing group information and member details</li>
                        <li>Payment request history and status</li>
                    </ul>

                    <h3 class="text-xl font-semibold mb-3">Automatically Collected Information</h3>
                    <ul class="list-disc list-inside space-y-2">
                        <li>Device information (browser type, operating system)</li>
                        <li>IP address and location data</li>
                        <li>Usage data and analytics</li>
                        <li>Cookies and similar tracking technologies</li>
                    </ul>
                </section>

                <section class="mb-8 animate-fade-in-up" style="animation-delay: 0.4s">
                    <h2 class="text-2xl font-bold mb-4">How We Use Your Information</h2>
                    <p class="leading-relaxed mb-3">
                        We use the information we collect to:
                    </p>
                    <ul class="list-disc list-inside space-y-2">
                        <li>Provide, maintain, and improve our services</li>
                        <li>Send payment requests and reminders to group members</li>
                        <li>Process group invitations and member management</li>
                        <li>Generate reports and analytics</li>
                        <li>Send service-related communications and notifications</li>
                        <li>Detect and prevent fraud or security issues</li>
                        <li>Comply with legal obligations</li>
                    </ul>
                </section>

                <section class="mb-8 animate-fade-in-up" style="animation-delay: 0.5s">
                    <h2 class="text-2xl font-bold mb-4">Information Sharing and Disclosure</h2>
                    <p class="leading-relaxed mb-3">
                        We do not sell your personal information. We may share your information in the following circumstances:
                    </p>
                    <ul class="list-disc list-inside space-y-2">
                        <li><strong>With Group Members:</strong> Your name and payment information is shared with members of groups you join</li>
                        <li><strong>Service Providers:</strong> Third-party vendors who perform services on our behalf (hosting, analytics, email delivery)</li>
                        <li><strong>Legal Requirements:</strong> When required by law or to protect our rights and safety</li>
                        <li><strong>Business Transfers:</strong> In connection with a merger, sale, or acquisition</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">Payment Information</h2>
                    <div class="bg-brand-50 dark:bg-brand-900/20 border border-brand-100 dark:border-brand-800 rounded-xl p-6">
                        <p class="leading-relaxed text-brand-900 dark:text-brand-100">
                            <strong>Important:</strong> TogetherPay does not process, store, or have access to credit card or bank account information.
                            All payments are made directly between members via Venmo or Cash App. We only facilitate the request process
                            by generating payment links - we never handle actual money transfers.
                        </p>
                    </div>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">Data Security</h2>
                    <p class="leading-relaxed">
                        We implement appropriate technical and organizational measures to protect your personal information, including:
                    </p>
                    <ul class="list-disc list-inside space-y-2 mt-3">
                        <li>Encryption of data in transit and at rest</li>
                        <li>Regular security audits and updates</li>
                        <li>Access controls and authentication requirements</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">Your Rights and Choices</h2>
                    <p class="leading-relaxed mb-3">
                        You have the following rights regarding your personal information:
                    </p>
                    <ul class="list-disc list-inside space-y-2">
                        <li><strong>Access:</strong> Request a copy of your personal data</li>
                        <li><strong>Correction:</strong> Update or correct inaccurate information</li>
                        <li><strong>Deletion:</strong> Request deletion of your account and data</li>
                        <li><strong>Opt-out:</strong> Unsubscribe from marketing communications</li>
                        <li><strong>Data Portability:</strong> Export your data in a structured format</li>
                    </ul>
                    <p class="leading-relaxed mt-3">
                        To exercise these rights, please contact us at <a href="mailto:privacy@togetherpay.com" class="text-brand-600 dark:text-brand-400 hover:underline">privacy@togetherpay.com</a>.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">Data Retention</h2>
                    <p class="leading-relaxed">
                        We retain your personal information for as long as necessary to provide our services and comply with legal obligations.
                        When you delete your account, we will delete or anonymize your personal information within 30 days,
                        except where we are required to retain it for legal, tax, or regulatory purposes.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">Children's Privacy</h2>
                    <p class="leading-relaxed">
                        Our service is not intended for children under 18 years of age. We do not knowingly collect personal
                        information from children. If you become aware that a child has provided us with personal information,
                        please contact us.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">International Data Transfers</h2>
                    <p class="leading-relaxed">
                        Your information may be transferred to and processed in countries other than your country of residence.
                        We ensure appropriate safeguards are in place to protect your information in accordance with this Privacy Policy.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">Changes to This Privacy Policy</h2>
                    <p class="leading-relaxed">
                        We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new
                        Privacy Policy on this page and updating the "Last updated" date. Continued use of our service after changes
                        constitutes acceptance of the updated policy.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold mb-4">Contact Us</h2>
                    <p class="leading-relaxed">
                        If you have questions or concerns about this Privacy Policy, please contact us:
                    </p>
                    <ul class="list-none space-y-2 mt-3">
                        <li><strong>Email:</strong> <a href="mailto:privacy@togetherpay.com" class="text-brand-600 dark:text-brand-400 hover:underline">privacy@togetherpay.com</a></li>
                        <li><strong>Contact Form:</strong> <a href="{{ route('contact') }}" class="text-brand-600 dark:text-brand-400 hover:underline">togetherpay.com/contact</a></li>
                    </ul>
                </section>

            </div>
        </div>

        <!-- Back to Home -->
        <div class="mt-10 text-center">
            <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2 text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 font-medium transition-colors group">
                <svg class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Home
            </a>
        </div>
    </div>

    <!-- Footer -->
    <!-- Footer -->
    <x-guest-footer />
</div>
