<div>
    <div class="space-y-8">
        <!-- Hero Section -->
        <div class="bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 rounded-3xl shadow-xl p-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">{{ $group->name }}</h1>
                    <p class="text-brand-100 text-lg">View invoices, receipts, and payment history.</p>
                </div>
                <a href="{{ route('app.groups.show', $group) }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back
                </a>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Invoices -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm p-6 border border-zinc-100 dark:border-zinc-700">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Total Invoices</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $allRequests->count() }}</p>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Paid Invoices -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm p-6 border border-zinc-100 dark:border-zinc-700">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Paid</p>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $paidRequests->count() }}</p>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending Invoices -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm p-6 border border-zinc-100 dark:border-zinc-700">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-1">Pending</p>
                        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $pendingRequests->count() }}</p>
                    </div>
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                        <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for Different Views -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden">
            <!-- Tab Navigation -->
            <div class="border-b border-zinc-100 dark:border-zinc-700 px-6 py-4">
                <div class="flex gap-8">
                    <button type="button" wire:click="$set('activeTab', 'all')"
                        class="px-4 py-2 font-medium text-sm border-b-2 transition-colors {{ $activeTab === 'all' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                        All Invoices
                    </button>
                    <button type="button" wire:click="$set('activeTab', 'paid')"
                        class="px-4 py-2 font-medium text-sm border-b-2 transition-colors {{ $activeTab === 'paid' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                        Paid Receipts
                    </button>
                    <button type="button" wire:click="$set('activeTab', 'pending')"
                        class="px-4 py-2 font-medium text-sm border-b-2 transition-colors {{ $activeTab === 'pending' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                        Pending
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                @if($filteredRequests->count() > 0)
                    <div class="space-y-4">
                        @foreach($filteredRequests as $request)
                            <div class="flex items-center justify-between p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                                <div class="flex-1">
                                    <div class="flex items-center gap-4">
                                        <div class="flex-shrink-0">
                                            <div class="flex items-center justify-center w-10 h-10 rounded-lg {{ $request->status === 'paid' ? 'bg-green-100 dark:bg-green-900/30' : ($request->status === 'pending' ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-red-100 dark:bg-red-900/30') }}">
                                                @if($request->status === 'paid')
                                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                @elseif($request->status === 'pending')
                                                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l-2-2m0 0l-2-2m2 2l2-2m-2 2l-2 2"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-zinc-900 dark:text-white">
                                                {{ $request->member?->user?->name ?? 'Unknown Member' }}
                                            </p>
                                            <div class="flex items-center gap-4 mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                                <span>PR Code: {{ $request->pr_code }}</span>
                                                <span>{{ $request->created_at->format('M d, Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 flex-shrink-0">
                                    <!-- Amount -->
                                    <div class="text-right">
                                        <p class="font-bold text-zinc-900 dark:text-white">${{ number_format($request->amount_cents / 100, 2) }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ ucfirst($request->status) }}</p>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-2">
                                        @if($request->square_invoice_id)
                                            <button type="button"
                                                wire:click="downloadSquareInvoice({{ $request->id }})"
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors duration-200">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                </svg>
                                                Invoice
                                            </button>
                                        @endif

                                        @if($request->status === 'paid' && $request->paid_at)
                                            <button type="button"
                                                wire:click="downloadReceipt({{ $request->id }})"
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors duration-200">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                </svg>
                                                Receipt
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-zinc-900 dark:text-white">No invoices yet</h3>
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                            @if($activeTab === 'paid')
                                No paid invoices in this group.
                            @elseif($activeTab === 'pending')
                                No pending invoices in this group.
                            @else
                                Invoices will appear here once payment requests are created.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if (session('error'))
        <div class="fixed bottom-4 right-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg shadow-lg z-50">
            <p>{{ session('error') }}</p>
        </div>
    @endif
</div>
