/**
 * TogetherPay - Modern UI Animations & Interactions
 * Professional, responsive, and elegant design system
 */

// Initialize animations on page load
document.addEventListener('DOMContentLoaded', () => {
    initAnimations();
    initInteractions();
    initThemeToggle();
});

/**
 * Initialize scroll-based animations
 */
function initAnimations() {
    // Intersection Observer for fade-in animations
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                entry.target.classList.remove('opacity-0');
            }
        });
    }, observerOptions);

    // Observe all elements with animation classes
    document.querySelectorAll('.fade-in, .slide-in-bottom, .slide-in-right, .scale-in').forEach(el => {
        el.classList.add('opacity-0');
        observer.observe(el);
    });

    // Stagger animations for lists
    document.querySelectorAll('[data-stagger]').forEach((container, containerIndex) => {
        const items = container.querySelectorAll('[data-stagger-item]');
        items.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            observer.observe(item);
        });
    });
}

/**
 * Initialize interactive elements
 */
function initInteractions() {
    // Add ripple effect to buttons
    document.querySelectorAll('.btn-primary, .btn-secondary, [data-ripple]').forEach(button => {
        button.addEventListener('click', createRipple);
    });

    // Add smooth hover effects to cards
    document.querySelectorAll('.card-elevated, [data-card]').forEach(card => {
        card.addEventListener('mouseenter', function(e) {
            this.style.transform = 'translateY(-4px) scale(1.01)';
        });

        card.addEventListener('mouseleave', function(e) {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Initialize number count-up animations
    initCountUp();

    // Initialize parallax effects
    initParallax();

    // Add loading state handlers
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', handleFormSubmit);
    });
}

/**
 * Create ripple effect on click
 */
function createRipple(event) {
    const button = event.currentTarget;
    const ripple = document.createElement('span');
    const rect = button.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;

    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${x}px`;
    ripple.style.top = `${y}px`;
    ripple.classList.add('ripple');

    button.style.position = 'relative';
    button.style.overflow = 'hidden';
    button.appendChild(ripple);

    setTimeout(() => ripple.remove(), 600);
}

/**
 * Initialize count-up animations for numbers
 */
function initCountUp() {
    const countUpElements = document.querySelectorAll('[data-countup]');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.dataset.counted) {
                const target = parseInt(entry.target.dataset.countup);
                const duration = parseInt(entry.target.dataset.duration || 2000);
                animateValue(entry.target, 0, target, duration);
                entry.target.dataset.counted = 'true';
            }
        });
    }, { threshold: 0.5 });

    countUpElements.forEach(el => observer.observe(el));
}

/**
 * Animate number value
 */
function animateValue(element, start, end, duration) {
    const startTime = performance.now();
    const prefix = element.dataset.prefix || '';
    const suffix = element.dataset.suffix || '';

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Easing function (easeOutQuart)
        const easeProgress = 1 - Math.pow(1 - progress, 4);
        const current = Math.floor(start + (end - start) * easeProgress);

        element.textContent = prefix + current.toLocaleString() + suffix;

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }

    requestAnimationFrame(update);
}

/**
 * Initialize parallax scrolling effects
 */
function initParallax() {
    const parallaxElements = document.querySelectorAll('[data-parallax]');

    if (parallaxElements.length > 0) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;

            parallaxElements.forEach(el => {
                const speed = parseFloat(el.dataset.parallax || 0.5);
                const yPos = -(scrolled * speed);
                el.style.transform = `translateY(${yPos}px)`;
            });
        });
    }
}

/**
 * Handle form submission with loading states
 */
function handleFormSubmit(event) {
    const form = event.target;
    const submitButton = form.querySelector('[type="submit"]');

    if (submitButton && !submitButton.disabled) {
        submitButton.disabled = true;
        submitButton.classList.add('loading');

        const originalText = submitButton.textContent;
        submitButton.innerHTML = `
            <svg class="animate-spin h-5 w-5 inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Processing...</span>
        `;

        // Reset after 10 seconds if no response
        setTimeout(() => {
            if (submitButton.disabled) {
                submitButton.disabled = false;
                submitButton.classList.remove('loading');
                submitButton.textContent = originalText;
            }
        }, 10000);
    }
}

/**
 * Initialize theme toggle functionality
 */
function initThemeToggle() {
    const themeToggle = document.querySelector('[data-theme-toggle]');

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');

            // Add smooth transition
            document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        });
    }

    // Check for saved theme preference or default to system preference
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.documentElement.classList.add('dark');
    }
}

/**
 * Toast notification system
 */
window.showToast = function(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();

    const toast = document.createElement('div');
    toast.className = `toast toast-${type} scale-in`;
    toast.innerHTML = `
        <div class="flex items-center space-x-3">
            ${getToastIcon(type)}
            <span class="flex-1">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="text-current opacity-70 hover:opacity-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
};

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'fixed top-4 right-4 z-50 space-y-2';
    document.body.appendChild(container);
    return container;
}

function getToastIcon(type) {
    const icons = {
        success: '<svg class="h-5 w-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
        error: '<svg class="h-5 w-5 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
        warning: '<svg class="h-5 w-5 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
        info: '<svg class="h-5 w-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
    };
    return icons[type] || icons.info;
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    .animate-in {
        animation-fill-mode: forwards;
    }

    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }

    @keyframes ripple-animation {
        to {
            transform: scale(2.5);
            opacity: 0;
        }
    }

    .toast {
        @apply bg-white dark:bg-zinc-800 rounded-xl shadow-xl p-4 border border-zinc-200 dark:border-zinc-700 min-w-[300px] max-w-md;
        transition: all 0.3s ease;
    }

    .toast-success {
        @apply border-l-4 border-l-success-500;
    }

    .toast-error {
        @apply border-l-4 border-l-error-500;
    }

    .toast-warning {
        @apply border-l-4 border-l-warning-500;
    }

    .toast-info {
        @apply border-l-4 border-l-brand-500;
    }
`;
document.head.appendChild(style);
