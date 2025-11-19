<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyMeConfirmation;
use Livewire\Attributes\Rule;

new class extends Component {
    #[Rule('required|email')]
    public string $email = '';

    public function submit()
    {
        \Log::info('NotifyMe: Form submitted', ['email' => $this->email]);
        
        try {
            $this->validate();
            \Log::info('NotifyMe: Validation passed');

            // Send confirmation email
            $notifyMe = \App\Models\NotifyMe::firstOrCreate(['email' => $this->email]);
            \Log::info('NotifyMe: Email saved to database', [
                'email' => $this->email,
                'was_existing' => !$notifyMe->wasRecentlyCreated
            ]);

            Mail::to($this->email)->send(new NotifyMeConfirmation());
            \Log::info('NotifyMe: Email queued for sending');
            
            // Show success toast via browser event
            $this->dispatch('show-toast', 
                message: "You're on the list! We'll be in touch soon.",
                type: 'success'
            );
            \Log::info('NotifyMe: Success toast triggered');

            // Reset form
            $this->reset('email');
            \Log::info('NotifyMe: Form reset');
        } catch (\Exception $e) {
            \Log::error('NotifyMe: Error occurred', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('show-toast', 
                message: 'Something went wrong. Please try again later.',
                type: 'error'
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
        class="min-w-[160px] px-8 py-4 bg-brand-500 hover:bg-brand-600 text-white font-semibold rounded-xl transition-all hover:scale-105 shadow-lg shadow-brand-500/30 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center"
        wire:loading.attr="disabled"
        wire:target="submit"
    >
        <span wire:loading.remove wire:target="submit">Notify Me</span>
        <span wire:loading wire:target="submit" class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Sending...</span>
        </span>
    </button>
</form>
