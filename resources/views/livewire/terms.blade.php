<?php

use function Livewire\Volt\{layout, title};

layout('components.layouts.guest');
title('Terms of Service - TogetherPay');

?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Terms of Service</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Last updated: {{ date('F j, Y') }}</p>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6 prose dark:prose-invert max-w-none">

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">1. Acceptance of Terms</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        By accessing or using TogetherPay ("Service"), you agree to be bound by these Terms of Service ("Terms").
                        If you do not agree to these Terms, you may not access or use the Service.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">2. Description of Service</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">
                        TogetherPay provides a platform for managing shared expenses and facilitating payment requests between group members.
                        Our Service includes:
                    </p>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                        <li>Group creation and member management</li>
                        <li>Automated payment request generation and delivery</li>
                        <li>Payment tracking and status updates</li>
                        <li>Reminder notifications and dunning management</li>
                        <li>Reporting and analytics tools</li>
                    </ul>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mt-3">
                        <strong>Important:</strong> TogetherPay is not a payment processor. We do not handle, process, or store any
                        payment transactions. All payments are made directly between members via third-party services (Venmo, Cash App).
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">3. User Accounts and Responsibilities</h2>

                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Account Creation</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 mb-4">
                        <li>You must be at least 18 years old to create an account</li>
                        <li>You must provide accurate and complete information</li>
                        <li>You are responsible for maintaining the security of your account credentials</li>
                        <li>You may not share your account with others</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Account Usage</h3>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">You agree to:</p>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                        <li>Use the Service only for lawful purposes</li>
                        <li>Not impersonate others or provide false information</li>
                        <li>Not use the Service to harass, abuse, or harm others</li>
                        <li>Not attempt to gain unauthorized access to the Service</li>
                        <li>Comply with all applicable laws and regulations</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">4. Payment Terms</h2>

                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">No Payment Processing</h3>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                        TogetherPay does not process payments. All financial transactions occur directly between users via
                        third-party payment platforms (Venmo, Cash App). We are not responsible for:
                    </p>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 mb-4">
                        <li>Failed or delayed payments</li>
                        <li>Payment disputes between members</li>
                        <li>Third-party payment platform fees or policies</li>
                        <li>Unauthorized transactions on payment platforms</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Service Fees</h3>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        TogetherPay is currently free to use. We reserve the right to introduce fees for premium features in the future.
                        Users will be notified at least 30 days before any new fees take effect.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">5. Groups and Member Management</h2>

                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Group Owners</h3>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">As a group owner, you:</p>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 mb-4">
                        <li>Are responsible for the accuracy of billing information</li>
                        <li>Must obtain consent from members before adding them to groups</li>
                        <li>Are responsible for resolving payment disputes with members</li>
                        <li>May not use the Service to defraud or mislead members</li>
                    </ul>

                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Group Members</h3>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">As a group member, you:</p>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                        <li>Are responsible for paying your share of group expenses on time</li>
                        <li>Acknowledge that payment requests are sent on behalf of the group owner</li>
                        <li>May leave a group at any time through the Service</li>
                        <li>Should verify billing amounts with the group owner if there are discrepancies</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">6. Intellectual Property</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">
                        The Service and its original content, features, and functionality are owned by TogetherPay and are protected
                        by international copyright, trademark, and other intellectual property laws.
                    </p>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        You may not copy, modify, distribute, sell, or lease any part of our Service without our written permission.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">7. Privacy and Data Protection</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        Your use of the Service is also governed by our <a href="{{ route('privacy') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Privacy Policy</a>.
                        By using the Service, you consent to the collection and use of your information as described in the Privacy Policy.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">8. Disclaimers and Limitation of Liability</h2>

                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Service Availability</h3>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                        The Service is provided "as is" and "as available" without warranties of any kind. We do not guarantee that
                        the Service will be uninterrupted, secure, or error-free.
                    </p>

                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Limitation of Liability</h3>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">
                        To the maximum extent permitted by law, TogetherPay shall not be liable for:
                    </p>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                        <li>Indirect, incidental, or consequential damages</li>
                        <li>Loss of profits, data, or goodwill</li>
                        <li>Payment disputes between users</li>
                        <li>Actions or failures of third-party payment platforms</li>
                        <li>Unauthorized access to your account</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">9. Indemnification</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        You agree to indemnify and hold harmless TogetherPay from any claims, damages, losses, or expenses
                        (including legal fees) arising from your use of the Service, violation of these Terms, or infringement
                        of any third-party rights.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">10. Termination</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">
                        We may terminate or suspend your account and access to the Service immediately, without prior notice,
                        for any reason, including:
                    </p>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300 mb-4">
                        <li>Violation of these Terms</li>
                        <li>Fraudulent or illegal activity</li>
                        <li>At your request</li>
                        <li>For any other reason at our sole discretion</li>
                    </ul>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        Upon termination, your right to use the Service will immediately cease. Data retention is subject to our Privacy Policy.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">11. Dispute Resolution</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        Any disputes arising from these Terms or your use of the Service shall be resolved through binding arbitration
                        in accordance with the rules of the American Arbitration Association. You waive your right to a jury trial or
                        to participate in a class action lawsuit.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">12. Changes to Terms</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        We reserve the right to modify these Terms at any time. We will notify users of material changes via email
                        or through the Service. Continued use of the Service after changes constitutes acceptance of the modified Terms.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">13. Governing Law</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        These Terms shall be governed by and construed in accordance with the laws of the United States,
                        without regard to conflict of law principles.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">14. Contact Information</h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-3">
                        If you have questions about these Terms, please contact us:
                    </p>
                    <ul class="list-none space-y-2 text-gray-700 dark:text-gray-300">
                        <li><strong>Email:</strong> <a href="mailto:legal@togetherpay.com" class="text-indigo-600 dark:text-indigo-400 hover:underline">legal@togetherpay.com</a></li>
                        <li><strong>Contact Form:</strong> <a href="{{ route('contact') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">togetherpay.com/contact</a></li>
                    </ul>
                </section>

            </div>
        </div>

        <!-- Back to Home -->
        <div class="mt-6 text-center">
            <a href="{{ route('welcome') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                ← Back to Home
            </a>
        </div>
    </div>
</div>
