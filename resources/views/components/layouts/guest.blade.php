<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="antialiased">
        {{ $slot }}
        
        <!-- Toast Container -->
        <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
        
        <script>
            // Toast notification system
            window.addEventListener('show-toast', event => {
                const { message, type } = event.detail;
                const toast = document.createElement('div');
                
                // Icons
                const successIcon = `<svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
                const errorIcon = `<svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
                const infoIcon = `<svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
                
                const icon = type === 'success' ? successIcon : (type === 'error' ? errorIcon : infoIcon);
                const borderColor = type === 'success' ? 'border-green-500/30' : (type === 'error' ? 'border-red-500/30' : 'border-blue-500/30');
                
                toast.className = `flex items-center gap-4 px-6 py-4 rounded-2xl shadow-2xl backdrop-blur-xl bg-zinc-900/90 border ${borderColor} transform transition-all duration-500 translate-x-full opacity-0 hover:scale-[1.02] cursor-default min-w-[320px]`;
                
                toast.innerHTML = `
                    <div class="flex-shrink-0">
                        ${icon}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-white leading-relaxed">${message}</p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-zinc-500 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                `;
                
                const container = document.getElementById('toast-container');
                container.appendChild(toast);
                
                // Animate in
                requestAnimationFrame(() => {
                    toast.classList.remove('translate-x-full', 'opacity-0');
                });
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.classList.add('translate-x-full', 'opacity-0');
                        setTimeout(() => toast.remove(), 500);
                    }
                }, 5000);
            });
        </script>
        
        @fluxScripts
    </body>
</html>
