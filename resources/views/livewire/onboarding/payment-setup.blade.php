<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $preferred_payment_method = '';
    public string $venmo_username = '';
    public string $cashapp_tag = '';
    public bool $skipPayment = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->preferred_payment_method = $user->preferred_payment_method ?? '';
        $this->venmo_username = $user->venmo_username ?? '';
        $this->cashapp_tag = $user->cashapp_tag ?? '';
    }

    /**
     * Save payment preferences and continue
     */
    public function save(): void
    {
        $user = Auth::user();

        // Validate based on selected payment method
        if (!$this->skipPayment) {
            $rules = [
                'preferred_payment_method' => ['required', 'in:venmo,cashapp'],
            ];

            if ($this->preferred_payment_method === 'venmo') {
                $rules['venmo_username'] = ['required', 'string', 'max:255'];
            } elseif ($this->preferred_payment_method === 'cashapp') {
                $rules['cashapp_tag'] = ['required', 'string', 'max:255'];
            }

            $validated = $this->validate($rules);

            $user->update([
                'preferred_payment_method' => $validated['preferred_payment_method'],
                'venmo_username' => $this->preferred_payment_method === 'venmo' ? $validated['venmo_username'] : $user->venmo_username,
                'cashapp_tag' => $this->preferred_payment_method === 'cashapp' ? $validated['cashapp_tag'] : $user->cashapp_tag,
            ]);
        }

        $this->redirect(route('onboarding.complete'));
    }

    /**
     * Go back to welcome
     */
    public function back(): void
    {
        $this->redirect(route('onboarding.welcome'));
    }
}; ?>

<x-layouts.auth.simple>
    <x-slot:title>Payment Setup</x-slot:title>

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-2xl w-full">
            <!-- Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-center space-x-2">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-success-600 flex items-center justify-center text-white shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="ml-2 text-sm font-medium text-success-600">Welcome</span>
                    </div>
                    <div class="w-16 h-1 bg-brand-600 mx-2"></div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-bold shadow-lg">
                            2
                        </div>
                        <span class="ml-2 text-sm font-medium text-brand-600">Setup</span>
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

            <!-- Setup Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-3xl shadow-2xl overflow-hidden border border-zinc-200 dark:border-zinc-700">
                <!-- Header -->
                <div class="bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 px-8 py-8 text-white text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm mb-4">
                        <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold mb-2">Payment Setup</h2>
                    <p class="text-brand-100">Choose how you want to receive payments</p>
                </div>

                <!-- Form -->
                <form wire:submit="save" class="p-8 space-y-6">
                    @if(!$skipPayment)
                        <!-- Payment Method Selection -->
                        <div>
                            <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Select Your Payment Method</label>
                            <div class="grid md:grid-cols-2 gap-4">
                                <!-- Venmo Option -->
                                <label class="relative cursor-pointer">
                                    <input type="radio" wire:model.live="preferred_payment_method" value="venmo" class="peer sr-only">
                                    <div class="border-2 border-zinc-200 dark:border-zinc-700 rounded-2xl p-6 transition-all duration-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 dark:peer-checked:bg-brand-900/20 peer-checked:shadow-lg hover:border-zinc-300 dark:hover:border-zinc-600">
                                        <div class="flex items-center space-x-3 mb-3">
                                            <div class="w-12 h-12 rounded-xl bg-[#008CFF] flex items-center justify-center text-white font-bold text-xl">
                                                V
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-lg text-zinc-900 dark:text-white">Venmo</h3>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">@username</p>
                                            </div>
                                        </div>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Popular for social payments</p>
                                    </div>
                                </label>

                                <!-- Cash App Option -->
                                <label class="relative cursor-pointer">
                                    <input type="radio" wire:model.live="preferred_payment_method" value="cashapp" class="peer sr-only">
                                    <div class="border-2 border-zinc-200 dark:border-zinc-700 rounded-2xl p-6 transition-all duration-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 dark:peer-checked:bg-brand-900/20 peer-checked:shadow-lg hover:border-zinc-300 dark:hover:border-zinc-600">
                                        <div class="flex items-center space-x-3 mb-3">
                                            <div class="w-12 h-12 rounded-xl bg-[#00D632] flex items-center justify-center text-white font-bold text-xl">
                                                $
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-lg text-zinc-900 dark:text-white">Cash App</h3>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">$cashtag</p>
                                            </div>
                                        </div>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Fast and simple transfers</p>
                                    </div>
                                </label>
                            </div>
                            @error('preferred_payment_method')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Venmo Username Input -->
                        @if($preferred_payment_method === 'venmo')
                            <div class="animate-in fade-in slide-in-from-top-2 duration-300">
                                <flux:input
                                    wire:model="venmo_username"
                                    label="Venmo Username"
                                    placeholder="Enter your Venmo @username"
                                    required
                                    icon="at-symbol"
                                />
                                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                    This is how group members will send you payments
                                </p>
                            </div>
                        @endif

                        <!-- Cash App Tag Input -->
                        @if($preferred_payment_method === 'cashapp')
                            <div class="animate-in fade-in slide-in-from-top-2 duration-300">
                                <flux:input
                                    wire:model="cashapp_tag"
                                    label="Cash App $Cashtag"
                                    placeholder="Enter your $cashtag"
                                    required
                                />
                                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                    This is how group members will send you payments
                                </p>
                            </div>
                        @endif

                        <!-- Info Box -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                            <div class="flex items-start space-x-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-1">Why do we need this?</p>
                                    <p class="text-xs text-blue-700 dark:text-blue-300">
                                        We'll use this to help group members send you payments easily. You can always update this later in your settings.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Skip Message -->
                        <div class="text-center py-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-100 dark:bg-zinc-700 mb-4">
                                <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">No problem!</h3>
                            <p class="text-zinc-600 dark:text-zinc-300 mb-4">You can set up your payment method later from your settings.</p>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <button type="button" wire:click="back"
                                class="inline-flex items-center px-5 py-3 border-2 border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 font-semibold rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-all duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                            </svg>
                            Back
                        </button>

                        <div class="flex items-center space-x-3">
                            @if(!$skipPayment)
                                <button type="button" wire:click="$set('skipPayment', true)"
                                        class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">
                                    Skip for now
                                </button>
                            @endif

                            <button type="submit"
                                    class="inline-flex items-center px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                Continue
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.auth.simple>
