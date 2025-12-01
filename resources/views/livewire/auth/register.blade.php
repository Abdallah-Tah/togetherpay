<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        @if (request('invitation'))
            <flux:banner variant="info" class="text-sm">
                Creating account for invitation to join a group
            </flux:banner>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Hidden invitation token -->
            @if (request('invitation'))
                <input type="hidden" name="invitation_token" value="{{ request('invitation') }}">
            @endif

            <div class="flex flex-col gap-2">
                <!-- Name -->
                <flux:input name="name" :label="__('Name')" type="text" required autofocus autocomplete="name"
                    class="mt-2" :placeholder="__('Full name')" value="{{ old('name', request('name')) }}"
                    @if (request('invitation')) readonly @endif />

                <!-- Email Address -->
                <flux:input name="email" :label="__('Email address')" type="email" required autocomplete="email"
                    class="mt-2" placeholder="email@example.com" value="{{ old('email', request('email')) }}"
                    @if (request('invitation')) readonly @endif />

                <!-- Password -->
                <flux:input name="password" :label="__('Password')" type="password" required autocomplete="new-password"
                    class="mt-2" :placeholder="__('Password')" viewable />

                <!-- Confirm Password -->
                <flux:input name="password_confirmation" :label="__('Confirm password')" type="password" required
                    class="mt-2" autocomplete="new-password" :placeholder="__('Confirm password')" viewable />
            </div>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full mt-2" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400 mt-2">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>
