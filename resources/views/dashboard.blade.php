<x-layouts.app>
    <x-slot:title>Dashboard</x-slot:title>

    <div class="pb-24 space-y-6">
        <!-- Shared Wallet Card -->
        <div class="bg-teal-deep rounded-[32px] p-6 text-white shadow-xl shadow-teal-deep/20 relative overflow-hidden">
            <div class="absolute top-0 right-0 -mt-8 -mr-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <p class="text-teal-100 text-sm font-medium mb-1">Shared Wallet</p>
                        <h2 class="text-4xl font-bold">$2,450.00</h2>
                    </div>
                    <div class="bg-white/20 p-2 rounded-2xl backdrop-blur-md">
                        <flux:icon.wallet class="h-6 w-6 text-white" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-white/10 rounded-2xl p-3 backdrop-blur-sm">
                        <p class="text-teal-100 text-[10px] uppercase tracking-wider font-bold mb-1">Incoming</p>
                        <p class="text-lg font-bold">+$420.00</p>
                    </div>
                    <div class="bg-white/10 rounded-2xl p-3 backdrop-blur-sm">
                        <p class="text-teal-100 text-[10px] uppercase tracking-wider font-bold mb-1">Outgoing</p>
                        <p class="text-lg font-bold">-$150.00</p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button class="flex-1 bg-white text-teal-deep font-bold py-3 rounded-2xl shadow-lg hover:bg-teal-50 transition-colors">
                        Settle up
                    </button>
                    <button class="flex-1 bg-teal-600 text-white font-bold py-3 rounded-2xl border border-teal-500/30 hover:bg-teal-700 transition-colors">
                        Request
                    </button>
                </div>
            </div>
        </div>

        <!-- This Month Spending Breakdown -->
        <div class="bg-white rounded-[32px] p-6 shadow-sm border border-zinc-100">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-zinc-900">This Month</h3>
                <div class="bg-zinc-100 p-1 rounded-xl flex">
                    <button class="px-3 py-1 text-xs font-bold rounded-lg bg-white shadow-sm text-teal-deep">My share</button>
                    <button class="px-3 py-1 text-xs font-bold rounded-lg text-zinc-500">Group total</button>
                </div>
            </div>

            <div class="flex items-center gap-8">
                <div class="relative w-32 h-32">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="64" cy="64" r="54" stroke="currentColor" stroke-width="12" fill="transparent" class="text-zinc-100" />
                        <circle cx="64" cy="64" r="54" stroke="currentColor" stroke-width="12" fill="transparent" stroke-dasharray="339.292" stroke-dashoffset="100" class="text-teal-deep" />
                        <circle cx="64" cy="64" r="54" stroke="currentColor" stroke-width="12" fill="transparent" stroke-dasharray="339.292" stroke-dashoffset="250" class="text-coral" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-bold text-zinc-900">$840</span>
                        <span class="text-[10px] text-zinc-500 font-medium">Spent</span>
                    </div>
                </div>

                <div class="flex-1 grid grid-cols-2 gap-y-3 gap-x-4">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-teal-deep"></div>
                        <span class="text-xs text-zinc-600">Groceries</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-coral"></div>
                        <span class="text-xs text-zinc-600">Dining</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                        <span class="text-xs text-zinc-600">Transport</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-400"></div>
                        <span class="text-xs text-zinc-600">Travel</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Goals Card -->
        <div class="bg-white rounded-[32px] p-6 shadow-sm border border-zinc-100">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-zinc-900">Goals</h3>
                <button class="bg-teal-50 text-teal-deep p-2 rounded-xl hover:bg-teal-100 transition-colors">
                    <flux:icon.plus class="h-4 w-4" />
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-end mb-2">
                        <div>
                            <p class="text-sm font-bold text-zinc-900">Trip Fund</p>
                            <p class="text-xs text-zinc-500">Shared with 4 friends</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-teal-deep">$1,200 <span class="text-zinc-400 font-medium">/ $2,000</span></p>
                        </div>
                    </div>
                    <div class="h-3 bg-zinc-100 rounded-full overflow-hidden">
                        <div class="h-full bg-teal-deep rounded-full" style="width: 60%"></div>
                    </div>
                    <p class="text-[10px] text-zinc-400 mt-2 font-medium">$800 remaining • Ends in 12 days</p>
                </div>
            </div>
        </div>

        <!-- Open Requests -->
        <div class="bg-white rounded-[32px] p-6 shadow-sm border border-zinc-100">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-zinc-900">Open Requests</h3>
                <a href="#" class="text-teal-deep text-sm font-bold">View all</a>
            </div>

            <div class="space-y-4">
                @foreach([
                    ['name' => 'Dinner at Nobu', 'group' => 'Foodies', 'amount' => '$45.00', 'status' => 'pending', 'date' => 'Today'],
                    ['name' => 'Electricity Bill', 'group' => 'Apartment 4B', 'amount' => '$120.00', 'status' => 'approved', 'date' => 'Yesterday'],
                    ['name' => 'Gas for Roadtrip', 'group' => 'Summer Trip', 'amount' => '$32.50', 'status' => 'paid', 'date' => '2 days ago']
                ] as $request)
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-zinc-50 flex items-center justify-center text-zinc-400">
                        <flux:icon.users class="h-6 w-6" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-zinc-900 truncate">{{ $request['name'] }}</p>
                        <p class="text-xs text-zinc-500">{{ $request['group'] }} • {{ $request['date'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-zinc-900">{{ $request['amount'] }}</p>
                        <span class="text-[10px] font-bold uppercase tracking-wider {{ $request['status'] === 'paid' ? 'text-emerald-500' : ($request['status'] === 'approved' ? 'text-teal-deep' : 'text-amber-500') }}">
                            {{ $request['status'] }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-[32px] p-6 shadow-sm border border-zinc-100">
            <h3 class="text-lg font-bold text-zinc-900 mb-6">Recent Activity</h3>

            <div class="space-y-6">
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center flex-shrink-0">
                        <flux:icon.check-circle class="h-5 w-5 text-emerald-500" />
                    </div>
                    <div>
                        <p class="text-sm text-zinc-900"><span class="font-bold">Alex Johnson</span> settled up for <span class="font-bold">Groceries</span></p>
                        <p class="text-xs text-zinc-500 mt-1">2 hours ago • $84.20</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center flex-shrink-0">
                        <flux:icon.arrow-path class="h-5 w-5 text-teal-deep" />
                    </div>
                    <div>
                        <p class="text-sm text-zinc-900">New request from <span class="font-bold">Sarah Miller</span> for <span class="font-bold">Internet</span></p>
                        <p class="text-xs text-zinc-500 mt-1">5 hours ago • $35.00</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-full bg-zinc-50 flex items-center justify-center flex-shrink-0">
                        <flux:icon.user-plus class="h-5 w-5 text-zinc-400" />
                    </div>
                    <div>
                        <p class="text-sm text-zinc-900"><span class="font-bold">Mike Ross</span> joined <span class="font-bold">Summer Trip</span></p>
                        <p class="text-xs text-zinc-500 mt-1">Yesterday</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
