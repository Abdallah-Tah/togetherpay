<?php

use function Livewire\Volt\{layout, title};

layout('components.layouts.guest');
title('Terms of Service - TogetherPay');

?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Terms of Service</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Last updated: {{ date('F j, Y') }}</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-3xl">
            <div class="px-6 py-8 sm:p-10 space-y-10 prose dark:prose-invert max-w-none">
                <section>
                    <h2>1. Acceptance of Terms</h2>
                    <p>By downloading or using TogetherPay (“Service”) you agree to these Terms. If you do not agree, do not access or use the Service.</p>
                </section>

                <section>
                    <h2>2. Description of Service</h2>
                    <p>TogetherPay provides tooling for group owners to organize shared expenses, send payment requests, and track payment status. We offer two collection modes:</p>
                    <ul>
                        <li><strong>Manual mode</strong> – generates pre-filled links for peer‑to‑peer apps such as Cash App or Venmo.</li>
                        <li><strong>Auto Cash App Pay</strong> – publishes Cash App Pay invoices through the owner’s connected Square merchant account.</li>
                    </ul>
                    <p><em>Important:</em> TogetherPay is not a payment processor and never holds user funds. Payments occur directly between the parties or via the owner’s Square account.</p>
                </section>

                <section>
                    <h2>3. Accounts & Responsibilities</h2>
                    <ul>
                        <li>You must be at least 18 years old and provide accurate registration information.</li>
                        <li>You are responsible for safeguarding your credentials and the content you create.</li>
                        <li>You may not use the Service for unlawful or prohibited activities.</li>
                        <li>You agree to comply with all applicable laws and the terms of any linked payment providers.</li>
                    </ul>
                </section>

                <section>
                    <h2>4. Payment Terms (Manual Mode)</h2>
                    <p>When using manual mode you acknowledge that all financial transactions are handled solely through third‑party peer-to-peer services. TogetherPay is not responsible for failed payments, disputes, chargebacks, or fees imposed by those providers.</p>
                </section>

                <section>
                    <h2>5. Square & Cash App Pay Autopay</h2>
                    <ul>
                        <li>Connecting Square authorizes TogetherPay to create invoices and read invoice metadata on your behalf.</li>
                        <li>You remain the merchant of record. Square deposits funds into your Square balance or linked bank account. TogetherPay never receives those funds.</li>
                        <li>Your use of Square is governed by Square’s Merchant Terms and Card Brand rules. You are solely responsible for compliance, taxes, chargebacks, refunds, and any disputes.</li>
                        <li>You may disconnect Square at any time from within TogetherPay or the Square dashboard. Disconnecting stops new autopay invoices but does not cancel invoices already published.</li>
                    </ul>
                </section>

                <section>
                    <h2>6. Data & Privacy</h2>
                    <p>We collect and store information needed to provide the Service (e.g., group details, email addresses, Square OAuth tokens). See our Privacy Policy for details on data handling. We never store full card numbers or bank credentials.</p>
                </section>

                <section>
                    <h2>7. Service Availability</h2>
                    <p>We aim for continuous availability but may suspend or modify the Service at any time for maintenance, upgrades, or security reasons. We are not liable for any loss resulting from downtime.</p>
                </section>

                <section>
                    <h2>8. Limitation of Liability</h2>
                    <p>TogetherPay is provided “as is.” To the fullest extent permitted by law, TogetherPay and its affiliates are not liable for indirect, incidental, or consequential damages arising from the use of the Service.</p>
                </section>

                <section>
                    <h2>9. Contact</h2>
                    <p>Questions about these Terms? Email <a href="mailto:support@togetherpay.com">support@togetherpay.com</a>.</p>
                </section>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('welcome') }}" class="text-brand-600 dark:text-brand-400 hover:text-brand-500">← Back to home</a>
        </div>
    </div>
</div>
