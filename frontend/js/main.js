/* ========================================
   MAIN - LANDING PAGE
   ======================================== */

// Smooth animations on scroll
document.addEventListener('DOMContentLoaded', () => {
    // Add animation classes when elements come into view
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe all feature items
    document.querySelectorAll('.feature-item').forEach(item => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        item.style.transition = 'all 0.6s ease';
        observer.observe(item);
    });
    
    // Initialize connection monitor UI
    if (Utils.isOnline()) {
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
