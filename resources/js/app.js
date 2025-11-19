/**
 * TogetherPay - Modern UI Animations & Interactions
 * Professional, responsive, and elegant design system
 */

// Initialize animations on page load
document.addEventListener('DOMContentLoaded', () => {
    initAnimations();
    initInteractions();
    initThemeToggle();
    initSmoothScroll();
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

                // Add specific animation classes if needed
                if (entry.target.dataset.animation) {
                    entry.target.classList.add(entry.target.dataset.animation);
                }
            }
        });
    }, observerOptions);

    // Observe all elements with animation classes
    document.querySelectorAll('.animate-fade-in-up, .fade-in, .slide-in-bottom, .slide-in-right, .scale-in').forEach(el => {
        // Only hide if not already visible (to prevent flashing on reload)
        // el.classList.add('opacity-0'); 
        // Note: We're handling initial opacity in CSS for some elements, but let's ensure it here for others
        if (!el.classList.contains('animate-in')) {
            // el.classList.add('opacity-0'); // Optional: depending on CSS strategy
        }
        observer.observe(el);
    });

    // Stagger animations for lists
    document.querySelectorAll('[data-stagger]').forEach((container) => {
        const items = container.querySelectorAll('[data-stagger-item]');
        items.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            item.classList.add('animate-fade-in-up');
            item.style.opacity = '0'; // Ensure hidden initially
            observer.observe(item);
        });
    });
}

/**
 * Initialize interactive elements
 */
function initInteractions() {
    // Add ripple effect to buttons
    document.querySelectorAll('.btn-primary, .btn-secondary, .btn-primary-glow, [data-ripple]').forEach(button => {
        button.addEventListener('click', createRipple);
    });

    // Add smooth hover effects to cards
    document.querySelectorAll('.glass-card, .card-elevated, [data-card]').forEach(card => {
        card.addEventListener('mouseenter', function (e) {
            this.style.transform = 'translateY(-8px) scale(1.01)';
        });

        card.addEventListener('mouseleave', function (e) {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Initialize parallax effects
    initParallax();
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

    // Ensure button has relative positioning
    const computedStyle = window.getComputedStyle(button);
    if (computedStyle.position === 'static') {
        button.style.position = 'relative';
    }
    button.style.overflow = 'hidden';

    button.appendChild(ripple);

    setTimeout(() => ripple.remove(), 600);
}

/**
 * Initialize parallax scrolling effects
 */
function initParallax() {
    const parallaxElements = document.querySelectorAll('[data-parallax]');
    const heroShapes = document.querySelectorAll('.animate-pulse-slow');

    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;

        // Parallax for specific elements
        parallaxElements.forEach(el => {
            const speed = parseFloat(el.dataset.parallax || 0.5);
            const yPos = -(scrolled * speed);
            el.style.transform = `translateY(${yPos}px)`;
        });

        // Subtle parallax for hero background shapes
        heroShapes.forEach((shape, index) => {
            const speed = (index + 1) * 0.05;
            const yPos = scrolled * speed;
            shape.style.transform = `translateY(${yPos}px)`;
        });
    });
}

/**
 * Initialize theme toggle functionality
 */
function initThemeToggle() {
    // Check for saved theme preference or default to system preference
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

/**
 * Initialize smooth scrolling for anchor links
 */
function initSmoothScroll() {
    // Use event delegation to handle dynamically added links (Livewire)
    document.addEventListener('click', function (e) {
        // Find the closest anchor element
        const anchor = e.target.closest('a[href^="#"]');

        if (anchor) {
            const href = anchor.getAttribute('href');

            // Skip if it's just "#" or empty
            if (!href || href === '#') return;

            const targetId = href.substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                e.preventDefault();

                // Smooth scroll to the target
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                // Update URL without jumping
                if (history.pushState) {
                    history.pushState(null, null, href);
                }
            }
        }
    });
}

// Re-initialize animations on Livewire navigation
document.addEventListener('livewire:navigated', () => {
    initAnimations();
    initInteractions();
    initThemeToggle();
});


// Add CSS for dynamic animations
const style = document.createElement('style');
style.textContent = `
    .animate-in {
        animation-fill-mode: forwards !important;
        opacity: 1 !important;
    }

    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.4);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }

    @keyframes ripple-animation {
        to {
            transform: scale(2.5);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
