<x-layouts.auth>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-3xl font-bold text-zinc-900 dark:text-white">Welcome back</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                Sign in to your account to continue
            </p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="rounded-2xl bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800 p-4 text-sm text-brand-700 dark:text-brand-300">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                    Email address
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    value="{{ old('email') }}"
                    placeholder="you@example.com"
                    class="block w-full px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-shadow @error('email') border-red-500 ring-2 ring-red-500 @enderror"
                />
                @error('email')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="password" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        Password
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                            Forgot password?
                        </a>
                    @endif
                </div>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="block w-full px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-shadow @error('password') border-red-500 ring-2 ring-red-500 @enderror"
                />
                @error('password')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    {{ old('remember') ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500 dark:border-zinc-600 dark:bg-zinc-800"
                />
                <label for="remember" class="ml-2 block text-sm text-zinc-700 dark:text-zinc-300">
                    Remember me for 30 days
                </label>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="w-full flex justify-center items-center px-6 py-3.5 bg-gradient-to-r from-brand-500 to-brand-600 text-white font-semibold rounded-xl hover:from-brand-600 hover:to-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-all duration-200 shadow-lg shadow-brand-500/30 hover:shadow-xl hover:shadow-brand-500/40 transform hover:-translate-y-0.5"
            >
                <span>Sign in</span>
                <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </button>
        </form>

        <!-- Sign Up Link -->
        @if (Route::has('register'))
            <div class="text-center pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Don't have an account?
                    <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                        Sign up for free
                    </a>
                </p>
            </div>
        @endif
    </div>
</x-layouts.auth>
