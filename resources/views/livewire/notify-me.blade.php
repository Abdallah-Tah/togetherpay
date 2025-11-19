<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyMeConfirmation;
use Livewire\Attributes\Rule;
use Flux\Flux;

new class extends Component {
    #[Rule('required|email')]
    public string $email = '';

    public function submit()
    {
        $this->validate();

        // Send confirmation email
        try {
            Mail::to($this->email)->send(new NotifyMeConfirmation());
            
            // Show success toast
            Flux::toast(
                text: "You're on the list! We'll be in touch soon.",
                heading: 'Success',
                variant: 'success'
            );

            // Reset form
            $this->reset('email');
        } catch (\Exception $e) {
            Flux::toast(
                text: 'Something went wrong. Please try again later.',
                heading: 'Error',
                variant: 'danger'
            );
        }
    }
}; ?>

<form wire:submit="submit" class="max-w-md mx-auto flex flex-col sm:flex-row gap-3 mb-8">
    <div class="flex-1 relative">
        <input 
            type="email" 
            wire:model="email"
            placeholder="Enter your email" 
            class="w-full px-6 py-4 rounded-xl bg-white/10 border border-white/20 text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent backdrop-blur-sm transition-all"
            required
        />
        @error('email') 
            <span class="absolute -bottom-6 left-0 text-sm text-red-400">{{ $message }}</span> 
        @enderror
    </div>
    <button 
        type="submit"
        class="px-8 py-4 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-xl transition-all hover:scale-105 shadow-lg shadow-brand-500/30 disabled:opacity-50 disabled:cursor-not-allowed"
        wire:loading.attr="disabled"
    >
        <span wire:loading.remove>Notify Me</span>
        <span wire:loading class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Sending...
        </span>
    </button>
</form>
