/**
 * RealSatisfied Testimonial Marquee Block - Frontend Script
 * 
 * Handles advanced animation control and performance optimization
 */

(function() {
    'use strict';

    /**
     * Initialize testimonial marquee functionality
     */
    function initTestimonialMarquee() {
        const marquees = document.querySelectorAll('.rs-testimonial-marquee');
        
        marquees.forEach(function(marquee) {
            const tracks = marquee.querySelectorAll('.rs-marquee-track');
            const speed = marquee.dataset.speed || 30;
            const pauseOnHover = marquee.dataset.pauseHover === 'true';
            
            // Set animation duration based on speed attribute
            tracks.forEach(function(track) {
                track.style.animationDuration = speed + 's';
            });
            
            // Intersection Observer for performance
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    const marquee = entry.target;
                    const tracks = marquee.querySelectorAll('.rs-marquee-track');
                    
                    if (entry.isIntersecting) {
                        // Start animation when visible
                        tracks.forEach(function(track) {
                            track.style.animationPlayState = 'running';
                        });
                    } else {
                        // Pause animation when not visible to save resources
                        tracks.forEach(function(track) {
                            track.style.animationPlayState = 'paused';
                        });
                    }
                });
            }, {
                threshold: 0.1, // Trigger when 10% visible
                rootMargin: '50px' // Start loading 50px before becoming visible
            });
            
            observer.observe(marquee);
            
            // No pause on hover - continuous flow
            
            // Handle reduced motion preference
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                tracks.forEach(function(track) {
                    track.style.animationDuration = (speed * 3) + 's'; // Slow down significantly
                });
            }
            
            // Performance optimization: Throttle any potential DOM updates
            let rafId;
            function performanceOptimization() {
                if (rafId) return;
                rafId = requestAnimationFrame(function() {
                    // Any future DOM updates can be batched here
                    rafId = null;
                });
            }
        });
    }

    /**
     * Initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTestimonialMarquee);
    } else {
        initTestimonialMarquee();
    }

})();
