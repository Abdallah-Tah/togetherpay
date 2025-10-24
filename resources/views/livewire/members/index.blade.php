@php
    use Illuminate\Support\Str;

    $pageTitle = $title ?? 'Members';
@endphp

<div class="space-y-6">
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

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-gray-900 dark:text-white">{{ $pageTitle }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage payment participants across every group and keep their payout preferences up to date.
            </p>
        </div>

        <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <div class="relative w-full sm:w-80">
                <label for="member-search" class="sr-only">Search members</label>
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 dark:text-gray-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M18 11a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    id="member-search"
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search by name, email, or group..."
                    class="block w-full rounded-xl border border-gray-200/60 bg-white py-2 pl-10 pr-3 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-white/10 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500"
                />
            </div>

            <button
                type="button"
                wire:click="openCreateModal"
                wire:loading.attr="disabled"
                wire:target="openCreateModal"
                class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-500 via-indigo-600 to-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:from-indigo-600 hover:via-indigo-700 hover:to-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-gray-900 disabled:opacity-60 disabled:shadow-none"
                @if (! $this->hasGroups) disabled @endif
            >
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Member
            </button>
        </div>
    </div>

    @if (! $this->hasGroups)
        <div class="rounded-2xl border border-dashed border-indigo-400/60 bg-indigo-50/50 px-6 py-5 text-sm text-indigo-800 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-100">
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

    <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-slate-900 text-slate-100 shadow-2xl shadow-slate-900/40">
        <div class="absolute inset-0 bg-gradient-to-b from-white/5 via-transparent"></div>

        <div class="relative overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-white/5 text-xs font-semibold uppercase tracking-wider text-slate-300">
                        <th scope="col" class="px-6 py-4 text-left">Member</th>
                        <th scope="col" class="px-6 py-4 text-left">Group</th>
                        <th scope="col" class="px-6 py-4 text-left">Share</th>
                        <th scope="col" class="px-6 py-4 text-left">Preferred Channel</th>
                        <th scope="col" class="px-6 py-4 text-left">Status</th>
                        <th scope="col" class="px-6 py-4 text-left whitespace-nowrap">Added</th>
                        <th scope="col" class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-white/5">
                    @forelse ($members as $member)
                        <tr
                            wire:key="member-{{ $member->id }}"
                            class="transition hover:bg-white/5"
                        >
                            <td class="px-6 py-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div class="grid h-10 w-10 place-items-center rounded-full bg-indigo-500/20 text-sm font-semibold text-indigo-100 ring-1 ring-inset ring-indigo-500/40">
                                        {{ $member->user?->initials() ?? Str::of($member->group->name)->substr(0, 2)->upper() }}
                                    </div>
                                    <div class="leading-tight">
                                        <p class="font-medium text-slate-100">
                                            {{ $member->user?->name ?? 'Pending Invite' }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{ $member->user?->email ?? '—' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="leading-tight">
                                    <p class="font-medium text-slate-100">{{ $member->group->name }}</p>
                                    <p class="text-xs text-slate-400 capitalize">{{ strtolower($member->group->cadence) }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-slate-100">
                                        @if ($member->share_type === 'PERCENT')
                                            {{ rtrim(rtrim(number_format($member->share_value, 2, '.', ''), '0'), '.') }}%
                                        @else
                                            ${{ number_format($member->share_value, 2) }}
                                        @endif
                                    </span>
                                    <span class="rounded-full bg-slate-800 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-slate-300">
                                        {{ $member->share_type === 'PERCENT' ? 'Percent' : 'Amount' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="leading-tight">
                                    <p class="font-medium text-slate-100">{{ Str::headline(strtolower($member->preferred_channel ?? 'Unset')) }}</p>
                                    @if ($handle = $member->getPaymentHandle())
                                        <p class="text-xs text-slate-400">{{ $handle }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold tracking-wide
                                    @class([
                                        'bg-emerald-500/20 text-emerald-200 ring-1 ring-inset ring-emerald-500/40' => $member->status === 'active',
                                        'bg-amber-500/20 text-amber-200 ring-1 ring-inset ring-amber-500/40' => $member->status === 'paused',
                                        'bg-slate-500/20 text-slate-300 ring-1 ring-inset ring-slate-500/40' => $member->status === 'left',
                                    ])">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ ucfirst($member->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 align-middle text-slate-400">
                                {{ optional($member->created_at)->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="editMember({{ $member->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="editMember"
                                        class="inline-flex items-center rounded-lg border border-white/10 px-3 py-1.5 text-xs font-medium text-slate-100 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="confirmRemoval({{ $member->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="confirmRemoval"
                                        class="inline-flex items-center rounded-lg border border-red-500/40 px-3 py-1.5 text-xs font-medium text-red-300 transition hover:bg-red-500/10 focus:outline-none focus:ring-2 focus:ring-red-400"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-400">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/5 ring-1 ring-white/10">
                                    <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405M19 13a7 7 0 10-14 0 7 7 0 0014 0z" />
                                    </svg>
                                </div>
                                <p class="mt-4 text-base font-medium text-slate-100">No members match your filters</p>
                                <p class="mt-1 text-sm text-slate-400">Adjust your search terms or add someone directly from your group.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="relative border-t border-white/5 px-6 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-400">
                    Showing
                    <span class="font-medium text-slate-200">{{ $members->firstItem() ?? 0 }}</span>
                    to
                    <span class="font-medium text-slate-200">{{ $members->lastItem() ?? 0 }}</span>
                    of
                    <span class="font-medium text-slate-200">{{ $members->total() }}</span>
                    members
                </p>
                <div class="text-right">
                    {{ $members->links() }}
                </div>
            </div>
        </div>

        <div
            wire:loading.flex
            class="absolute inset-0 z-20 hidden items-center justify-center bg-slate-900/70 backdrop-blur"
            wire:target="createMember,updateMember,removeMember,editMember,confirmRemoval"
        >
            <div class="h-10 w-10 animate-spin rounded-full border-2 border-white/30 border-t-transparent"></div>
        </div>
    </div>

    @if ($showCreateModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-gray-950/70 p-4 backdrop-blur">
            <div class="w-full max-w-xl rounded-2xl border border-white/10 bg-slate-900 px-6 py-6 text-slate-100 shadow-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-semibold">Add Member</h2>
                        <p class="mt-1 text-sm text-slate-400">
                            Invite an existing TogetherPay user into one of your groups.
                        </p>
                    </div>
                    <button type="button" wire:click="closeModals" class="rounded-lg p-1 text-slate-400 transition hover:bg-white/5 hover:text-white">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form class="mt-6 space-y-5" wire:submit.prevent="createMember">
                    <div>
                        <label for="create-group" class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Group</label>
                        <select
                            id="create-group"
                            wire:model="formGroupId"
                            class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        >
                            <option value="">Select group...</option>
                            @foreach ($groups as $groupId => $groupName)
                                <option value="{{ $groupId }}">{{ $groupName }}</option>
                            @endforeach
                        </select>
                        @error('formGroupId') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="create-email" class="block text-xs font-semibold uppercase tracking-wide text-slate-400">User email</label>
                            <input
                                id="create-email"
                                type="email"
                                wire:model.defer="formEmail"
                                placeholder="member@example.com"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            @error('formEmail') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="create-phone" class="block text-xs font-semibold uppercase tracking-wide text-slate-400">User phone</label>
                            <input
                                id="create-phone"
                                type="text"
                                wire:model.defer="formPhone"
                                placeholder="+1 555 000 1234"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            @error('formPhone') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="create-share-type" class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Share type</label>
                            <select
                                id="create-share-type"
                                wire:model="formShareType"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            >
                                <option value="AMOUNT">Fixed amount</option>
                                <option value="PERCENT">Percentage</option>
                            </select>
                            @error('formShareType') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="create-share-value" class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Share value</label>
                            <input
                                id="create-share-value"
                                type="number"
                                step="0.01"
                                wire:model.defer="formShareValue"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            @error('formShareValue') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="create-channel" class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Preferred channel</label>
                            <select
                                id="create-channel"
                                wire:model="formPreferredChannel"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            >
                                <option value="VENMO">Venmo</option>
                                <option value="CASH_APP">Cash App</option>
                                <option value="CARD">Card</option>
                            </select>
                            @error('formPreferredChannel') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="create-venmo" class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Venmo username</label>
                            <input
                                id="create-venmo"
                                type="text"
                                wire:model.defer="formVenmoUsername"
                                placeholder="@username"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            @error('formVenmoUsername') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="create-cashtag" class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Cash tag</label>
                            <input
                                id="create-cashtag"
                                type="text"
                                wire:model.defer="formCashTag"
                                placeholder="$cashtag"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            @error('formCashTag') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="create-cash-phone" class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Cash phone</label>
                            <input
                                id="create-cash-phone"
                                type="text"
                                wire:model.defer="formCashPhone"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            @error('formCashPhone') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="create-cash-email" class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Cash email</label>
                            <input
                                id="create-cash-email"
                                type="email"
                                wire:model.defer="formCashEmail"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            @error('formCashEmail') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button
                            type="button"
                            wire:click="closeModals"
                            class="rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-slate-400"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="createMember"
                            class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400"
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
            <div class="w-full max-w-xl rounded-2xl border border-white/10 bg-slate-900 px-6 py-6 text-slate-100 shadow-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-semibold">Edit Member</h2>
                        <p class="mt-1 text-sm text-slate-400">
                            Adjust collection details and payout preferences.
                        </p>
                    </div>
                    <button type="button" wire:click="closeModals" class="rounded-lg p-1 text-slate-400 transition hover:bg-white/5 hover:text-white">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" fill="none" stroke="CurrentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form class="mt-6 space-y-5" wire:submit.prevent="updateMember">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Share type</label>
                            <select
                                wire:model="formShareType"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            >
                                <option value="AMOUNT">Fixed amount</option>
                                <option value="PERCENT">Percentage</option>
                            </select>
                            @error('formShareType') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Share value</label>
                            <input
                                type="number"
                                step="0.01"
                                wire:model.defer="formShareValue"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            @error('formShareValue') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Preferred channel</label>
                            <select
                                wire:model="formPreferredChannel"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            >
                                <option value="VENMO">Venmo</option>
                                <option value="CASH_APP">Cash App</option>
                                <option value="CARD">Card</option>
                            </select>
                            @error('formPreferredChannel') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Status</label>
                            <select
                                wire:model="formStatus"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            >
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="left">Left</option>
                            </select>
                            @error('formStatus') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Venmo username</label>
                            <input
                                type="text"
                                wire:model.defer="formVenmoUsername"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            @error('formVenmoUsername') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Cash tag</label>
                            <input
                                type="text"
                                wire:model.defer="formCashTag"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            @error('formCashTag') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Cash phone</label>
                            <input
                                type="text"
                                wire:model.defer="formCashPhone"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            @error('formCashPhone') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Cash email</label>
                            <input
                                type="email"
                                wire:model.defer="formCashEmail"
                                class="mt-2 block w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                            @error('formCashEmail') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="rounded-xl border border-white/5 bg-white/5 px-4 py-3 text-xs text-slate-300">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            class="rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-slate-400"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="updateMember"
                            class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400"
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
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-gray-950/75 p-4 backdrop-blur">
            <div class="w-full max-w-md rounded-2xl border border-red-500/30 bg-slate-900 px-6 py-6 text-slate-100 shadow-2xl">
                <div class="flex items-start gap-4">
                    <div class="rounded-full bg-red-500/20 p-3 text-red-300 ring-1 ring-red-400/40">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.22 5.22a7 7 0 119.9 9.9L12 21l-3.12-3.12a7 7 0 01-3.66-12.66z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Remove member?</h3>
                        <p class="mt-2 text-sm text-slate-400">
                            This will mark the member as <strong>left</strong>. They will no longer receive payment requests, but existing history is preserved.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        wire:click="closeModals"
                        class="rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-slate-400"
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
