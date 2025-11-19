<footer class="bg-white dark:bg-zinc-950 border-t border-zinc-200 dark:border-zinc-800 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2">
                <x-app-logo class="w-8 h-8 rounded-lg" />
                <span class="font-bold text-zinc-900 dark:text-white">TogetherPay</span>
            </div>
            
            <div class="flex gap-8 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                <a href="{{ route('about') }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">About</a>
                <a href="{{ route('privacy') }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Privacy</a>
                <a href="{{ route('terms') }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Terms</a>
                <a href="{{ route('contact') }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Contact</a>
            </div>
            
            <div class="text-sm text-zinc-500">
                &copy; {{ date('Y') }} TogetherPay. All rights reserved.
            </div>
        </div>
    </div>
</footer>
