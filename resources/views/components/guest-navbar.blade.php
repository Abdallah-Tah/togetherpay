@props(['active' => ''])

<nav class="fixed top-0 w-full z-50 transition-all duration-300" id="navbar" x-data="{ mobileMenuOpen: false, scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 20)">
    <div class="absolute inset-0 bg-white/70 dark:bg-zinc-950/70 backdrop-blur-xl border-b border-zinc-200/50 dark:border-zinc-800/50 transition-opacity duration-300" :class="{ 'opacity-100': scrolled || mobileMenuOpen, 'opacity-0': !scrolled && !mobileMenuOpen }"></div>
    
    <!-- Gradient border for premium feel -->
    <div class="absolute bottom-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-zinc-300/50 dark:via-zinc-700/50 to-transparent opacity-0 transition-opacity duration-300" :class="{ 'opacity-100': scrolled }"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            <!-- Logo -->
            <div class="flex items-center gap-3 z-50">
                <a href="{{ route('welcome') }}" class="flex items-center gap-3 group">
                    <div class="relative">
                        <div class="absolute inset-0 bg-brand-500/20 rounded-xl blur-lg opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        <x-app-logo class="relative w-10 h-10 rounded-xl shadow-lg shadow-brand-500/10 group-hover:scale-105 transition-transform duration-300" />
                    </div>
                    <span class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">TogetherPay</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center gap-8">
                <a href="{{ route('welcome') }}" class="text-sm font-medium transition-colors {{ request()->routeIs('welcome') ? 'text-brand-600 dark:text-brand-400' : 'text-zinc-600 dark:text-zinc-400 hover:text-brand-600 dark:hover:text-brand-400' }}">Home</a>
                <a href="{{ route('how-it-works') }}" class="text-sm font-medium transition-colors {{ request()->routeIs('how-it-works') ? 'text-brand-600 dark:text-brand-400' : 'text-zinc-600 dark:text-zinc-400 hover:text-brand-600 dark:hover:text-brand-400' }}">How it Works</a>
                <a href="{{ route('about') }}" class="text-sm font-medium transition-colors {{ request()->routeIs('about') ? 'text-brand-600 dark:text-brand-400' : 'text-zinc-600 dark:text-zinc-400 hover:text-brand-600 dark:hover:text-brand-400' }}">About</a>
                <a href="{{ route('contact') }}" class="text-sm font-medium transition-colors {{ request()->routeIs('contact') ? 'text-brand-600 dark:text-brand-400' : 'text-zinc-600 dark:text-zinc-400 hover:text-brand-600 dark:hover:text-brand-400' }}">Contact</a>
            </div>

            <!-- Mobile Menu Button -->
            <div class="flex md:hidden items-center z-50">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 rounded-lg text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors focus:outline-none">
                    <span class="sr-only">Open menu</span>
                    <!-- Hamburger Icon -->
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <!-- Close Icon -->
                    <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div x-show="mobileMenuOpen" x-cloak 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         class="absolute top-20 left-0 w-full bg-white/95 dark:bg-zinc-950/95 backdrop-blur-xl border-b border-zinc-200 dark:border-zinc-800 shadow-xl md:hidden">
        <div class="px-4 py-6 space-y-4">
            <a href="{{ route('welcome') }}" class="block px-4 py-3 rounded-xl text-base font-medium {{ request()->routeIs('welcome') ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-900 hover:text-zinc-900 dark:hover:text-white' }}">
                Home
            </a>
            <a href="{{ route('how-it-works') }}" class="block px-4 py-3 rounded-xl text-base font-medium {{ request()->routeIs('how-it-works') ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-900 hover:text-zinc-900 dark:hover:text-white' }}">
                How it Works
            </a>
            <a href="{{ route('about') }}" class="block px-4 py-3 rounded-xl text-base font-medium {{ request()->routeIs('about') ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-900 hover:text-zinc-900 dark:hover:text-white' }}">
                About
            </a>
            <a href="{{ route('contact') }}" class="block px-4 py-3 rounded-xl text-base font-medium {{ request()->routeIs('contact') ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-900 hover:text-zinc-900 dark:hover:text-white' }}">
                Contact
            </a>
        </div>
    </div>
</nav>
