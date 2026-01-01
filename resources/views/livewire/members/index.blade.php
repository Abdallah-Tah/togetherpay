<div>
    @php
        use Illuminate\Support\Str;

        $pageTitle = $title ?? 'Members';
    @endphp

    <div class="space-y-8">
    @if (session('success'))
        <div class="rounded-xl border border-green-200/70 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:border-green-900 dark:bg-green-900/30 dark:text-green-100">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-red-200/70 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900 dark:bg-red-900/30 dark:text-red-100">
            {{ session('error') }}
        </div>
    @endif

    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-teal-500 to-emerald-500 rounded-3xl shadow-xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">{{ $pageTitle }}</h1>
                <p class="text-white/90 text-lg">Manage payment participants across every group and keep their payout preferences up to date.</p>
            </div>
            <div class="hidden md:block">
                <button
                    type="button"
                    wire:click="openCreateModal"
                    wire:loading.attr="disabled"
                    wire:target="openCreateModal"
                    class="inline-flex items-center px-6 py-3 bg-white text-teal-600 font-semibold rounded-2xl hover:bg-teal-50 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-60 disabled:transform-none"
                    @if (! $this->hasGroups) disabled @endif
                >
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Member
                </button>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 p-6">
        <div class="relative max-w-xl">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input wire:model.live.debounce.400ms="search"
                   type="search"
                   placeholder="Search by name, email, or group..."
                   class="block w-full pl-11 pr-4 py-3.5 border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-shadow">
        </div>
    </div>

    @if (! $this->hasGroups)
        <div class="rounded-2xl border border-dashed border-teal-400/60 bg-teal-50/50 px-6 py-5 text-sm text-teal-800 dark:border-teal-500/40 dark:bg-teal-500/10 dark:text-teal-100">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.25 6.75L9 9h3m1.5 8.25L15 15h-3M5.25 12a6.75 6.75 0 1113.5 0 6.75 6.75 0 01-13.5 0z" />
                </svg>
                <span>
                    You need at least one active group to add members. Create a group first, then invite its participants here.
                </span>
            </div>
        </div>
    @endif

    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-900/50 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-b border-zinc-100 dark:border-zinc-700">
                        <th scope="col" class="px-6 py-4 text-left">Member</th>
                        <th scope="col" class="px-6 py-4 text-left">Group</th>
                        <th scope="col" class="px-6 py-4 text-left">Share</th>
                        <th scope="col" class="px-6 py-4 text-left">Preferred Channel</th>
                        <th scope="col" class="px-6 py-4 text-left">Status</th>
                        <th scope="col" class="px-6 py-4 text-left whitespace-nowrap">Added</th>
                        <th scope="col" class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse ($members as $member)
                        <tr
                            wire:key="member-{{ $member->id }}"
                            class="transition hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                        >
                            <td class="px-6 py-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-teal-400 to-emerald-600 flex items-center justify-center text-sm font-semibold text-white">
                                        {{ $member->user?->initials() ?? Str::of($member->group->name)->substr(0, 2)->upper() }}
                                    </div>
                                    <div class="leading-tight">
                                        <p class="font-medium text-zinc-900 dark:text-white">
                                            {{ $member->user?->name ?? 'Pending Invite' }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $member->user?->email ?? '—' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="leading-tight">
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ $member->group->name }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 capitalize">{{ strtolower($member->group->cadence) }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-zinc-900 dark:text-white">
                                        @if ($member->share_type === 'PERCENT')
                                            {{ rtrim(rtrim(number_format($member->share_value, 2, '.', ''), '0'), '.') }}%
                                        @else
                                            ${{ number_format($member->share_value, 2) }}
                                        @endif
                                    </span>
                                    <span class="rounded-full bg-zinc-100 dark:bg-zinc-700 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-600 dark:text-zinc-300">
                                        {{ $member->share_type === 'PERCENT' ? 'Percent' : 'Amount' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="leading-tight">
                                    <p class="font-medium text-zinc-900 dark:text-white">SmartPay</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Member chooses at pay time</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold
                                    @class([
                                        'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' => $member->status === 'active',
                                        'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400' => $member->status === 'paused',
                                        'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' => $member->status === 'left',
                                    ])">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ ucfirst($member->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 align-middle text-zinc-500 dark:text-zinc-400">
                                {{ optional($member->created_at)->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="editMember({{ $member->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="editMember"
                                        class="inline-flex items-center rounded-lg border border-zinc-200 dark:border-zinc-700 px-3 py-1.5 text-xs font-medium text-zinc-900 dark:text-white transition hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-teal-400"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="confirmRemoval({{ $member->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="confirmRemoval"
                                        class="inline-flex items-center rounded-lg border border-red-200 dark:border-red-900/40 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 transition hover:bg-red-50 dark:hover:bg-red-900/20 focus:outline-none focus:ring-2 focus:ring-red-400"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-700">
                                    <svg class="h-8 w-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405M19 13a7 7 0 10-14 0 7 7 0 0014 0z" />
                                    </svg>
                                </div>
                                <p class="mt-4 text-base font-semibold text-zinc-900 dark:text-white">No members match your filters</p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Adjust your search terms or add someone directly from your group.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-100 dark:border-zinc-700 px-6 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Showing
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $members->firstItem() ?? 0 }}</span>
                    to
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $members->lastItem() ?? 0 }}</span>
                    of
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $members->total() }}</span>
                    members
                </p>
                <div class="text-right">
                    {{ $members->links() }}
                </div>
            </div>
        </div>
    </div>

    @if ($showCreateModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-zinc-950/70 p-4 backdrop-blur">
            <div class="w-full max-w-xl rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-6 py-6 shadow-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Add Member</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            Invite an existing TogetherPay user into one of your groups.
                        </p>
                    </div>
                    <button type="button" wire:click="closeModals" class="rounded-lg p-1 text-zinc-400 transition hover:bg-zinc-100 dark:hover:bg-zinc-700 hover:text-zinc-900 dark:hover:text-zinc-900 dark:hover:text-white">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form class="mt-6 space-y-5" wire:submit.prevent="createMember">
                    <div>
                        <label for="create-group" class="block text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Group</label>
                        <select
                            id="create-group"
                            wire:model="formGroupId"
                            class="mt-2 block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400"
                        >
                            <option value="">Select group...</option>
                            @foreach ($this->groups() as $groupId => $groupName)
                                <option value="{{ $groupId }}">{{ $groupName }}</option>
                            @endforeach
                        </select>
                        @error('formGroupId') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="create-email" class="block text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">User email</label>
                            <input
                                id="create-email"
                                type="email"
                                wire:model.defer="formEmail"
                                placeholder="member@example.com"
                                class="mt-2 block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400"
                            />
                            @error('formEmail') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="create-phone" class="block text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">User phone</label>
                            <input
                                id="create-phone"
                                type="text"
                                wire:model.defer="formPhone"
                                placeholder="+1 555 000 1234"
                                class="mt-2 block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400"
                            />
                            @error('formPhone') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="create-share-type" class="block text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Share type</label>
                            <select
                                id="create-share-type"
                                wire:model="formShareType"
                                class="mt-2 block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400"
                            >
                                <option value="AMOUNT">Fixed amount</option>
                                <option value="PERCENT">Percentage</option>
                            </select>
                            @error('formShareType') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="create-share-value" class="block text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Share value</label>
                            <input
                                id="create-share-value"
                                type="number"
                                step="0.01"
                                wire:model.defer="formShareValue"
                                class="mt-2 block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-400"
                            />
                            @error('formShareValue') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button
                            type="button"
                            wire:click="closeModals"
                            class="rounded-xl border border-zinc-200 dark:border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-100 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-slate-400"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="createMember"
                            class="inline-flex items-center rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-400"
                        >
                            <svg
                                wire:loading
                                wire:target="createMember"
                                class="-ml-1 mr-2 h-4 w-4 animate-spin text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            <span wire:loading.remove wire:target="createMember">Save Member</span>
                            <span wire:loading wire:target="createMember">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showEditModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-gray-950/70 p-4 backdrop-blur">
            <div class="w-full max-w-xl rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-6 py-6 text-zinc-900 dark:text-white shadow-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-semibold">Edit Member</h2>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            Adjust collection details and payout preferences.
                        </p>
                    </div>
                    <button type="button" wire:click="closeModals" class="rounded-lg p-1 text-zinc-600 dark:text-zinc-400 transition hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:text-zinc-900 dark:hover:text-white">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form class="mt-6 space-y-5" wire:submit.prevent="updateMember">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Share type</label>
                            <select
                                wire:model="formShareType"
                                class="mt-2 block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400"
                            >
                                <option value="AMOUNT">Fixed amount</option>
                                <option value="PERCENT">Percentage</option>
                            </select>
                            @error('formShareType') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Share value</label>
                            <input
                                type="number"
                                step="0.01"
                                wire:model.defer="formShareValue"
                                class="mt-2 block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400"
                            />
                            @error('formShareValue') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">Status</label>
                        <select
                            wire:model="formStatus"
                            class="mt-2 block w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-400"
                        >
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="left">Left</option>
                        </select>
                        @error('formStatus') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-xl border border-teal-200 dark:border-teal-900/40 bg-teal-50 dark:bg-teal-900/20 px-4 py-3 text-xs text-teal-800 dark:text-teal-200">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>
                                Members marked as <strong>Left</strong> are excluded from future billing cycles but remain in historical reports.
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button
                            type="button"
                            wire:click="closeModals"
                            class="rounded-xl border border-zinc-200 dark:border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-100 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-slate-400"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="updateMember"
                            class="inline-flex items-center rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-400"
                        >
                            <svg
                                wire:loading
                                wire:target="updateMember"
                                class="-ml-1 mr-2 h-4 w-4 animate-spin text-white"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            <span wire:loading.remove wire:target="updateMember">Save Changes</span>
                            <span wire:loading wire:target="updateMember">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showRemoveModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-zinc-950/75 p-4 backdrop-blur">
            <div class="w-full max-w-md rounded-2xl border border-red-200 dark:border-red-900/40 bg-white dark:bg-zinc-800 px-6 py-6 shadow-2xl">
                <div class="flex items-start gap-4">
                    <div class="rounded-full bg-red-100 dark:bg-red-900/30 p-3 text-red-600 dark:text-red-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.22 5.22a7 7 0 119.9 9.9L12 21l-3.12-3.12a7 7 0 01-3.66-12.66z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Remove member?</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            This will mark the member as <strong>left</strong>. They will no longer receive payment requests, but existing history is preserved.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        wire:click="closeModals"
                        class="rounded-xl border border-zinc-200 dark:border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 transition hover:bg-zinc-100 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-400"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="removeMember"
                        wire:loading.attr="disabled"
                        wire:target="removeMember"
                        class="inline-flex items-center rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400"
                    >
                        <svg
                            wire:loading
                            wire:target="removeMember"
                            class="-ml-1 mr-2 h-4 w-4 animate-spin text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        <span wire:loading.remove wire:target="removeMember">Confirm</span>
                        <span wire:loading wire:target="removeMember">Removing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
    </div>
</div>
