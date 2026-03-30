/* ========================================
   MAIN - LANDING PAGE
   ======================================== */

// Smooth animations on scroll
document.addEventListener('DOMContentLoaded', () => {
    // Enhanced observer options for better performance
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
                
                // Stagger animation for feature items
                if (entry.target.classList.contains('feature-item')) {
                    const items = Array.from(document.querySelectorAll('.feature-item'));
                    const index = items.indexOf(entry.target);
                    entry.target.style.animationDelay = `${index * 0.1}s`;
                }
            }
        });
    }, observerOptions);
    
    // Observe features section
    const featuresSection = document.querySelector('.features-section');
    if (featuresSection) {
        observer.observe(featuresSection);
    }
    
    // Observe all feature items individually
    document.querySelectorAll('.feature-item').forEach((item, index) => {
        item.style.transitionDelay = `${index * 0.1}s`;
        observer.observe(item);
    });
    
    // Add parallax effect to hero background on mouse move
    const heroBg = document.querySelector('.hero-bg');
    if (heroBg) {
        document.addEventListener('mousemove', (e) => {
            const x = (e.clientX / window.innerWidth - 0.5) * 20;
            const y = (e.clientY / window.innerHeight - 0.5) * 20;
            heroBg.style.transform = `translate(${x}px, ${y}px)`;
        });
    }
    
    // Initialize connection monitor UI
    if (typeof Utils !== 'undefined' && Utils.isOnline()) {
        console.log('Online mode');
    } else {
        console.log('Offline mode');
    }
});

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const authModal = document.getElementById('authModal');
        if (authModal && authModal.classList.contains('active')) {
            closeAuth();
        }
    }
});

// Prevent form submission on enter in text inputs (except in forms)
document.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'submit') {
        const form = e.target.closest('form');
        if (!form) {
            e.preventDefault();
        }
    }
});

// Log version
console.log('%cTaskFlow v1.0', 'color: #E63946; font-size: 20px; font-weight: bold;');
console.log('%cBuilt for informal workers in Kenya', 'color: #2A9D8F; font-size: 12px;');
