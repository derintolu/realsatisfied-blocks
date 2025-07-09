/**
 * WordPress Interactivity API store for Agent Testimonials block
 * 
 * This replaces the legacy jQuery/vanilla JS implementations and provides:
 * - Pagination functionality (next/previous page navigation)
 * - Dynamic testimonial display based on current page
 * - Testimonial expansion/collapse (if needed for future features)
 * - Context-aware state management for testimonial data
 * - Grid layout support with responsive columns
 * - Error handling and loading states
 * 
 * @since 1.0.0 Migrated to WordPress Interactivity API
 */

import { store, getContext } from '@wordpress/interactivity';

store('realsatisfied-agent-testimonials', {
    actions: {
        /**
         * Navigate to next page
         */
        nextPage: () => {
            const context = getContext();
            if (context.currentPage < context.totalPages) {
                context.currentPage += 1;
            }
        },

        /**
         * Navigate to previous page
         */
        prevPage: () => {
            const context = getContext();
            if (context.currentPage > 1) {
                context.currentPage -= 1;
            }
        },

        /**
         * Navigate to specific page
         */
        goToPage: (event) => {
            const context = getContext();
            const pageNumber = parseInt(event.target.getAttribute('data-page'));
            if (pageNumber >= 1 && pageNumber <= context.totalPages) {
                context.currentPage = pageNumber;
            }
        },

        /**
         * Toggle testimonial details/expansion
         */
        toggleTestimonial: (event) => {
            const context = getContext();
            const testimonialId = event.target.getAttribute('data-testimonial-id');
            
            if (!context.expandedTestimonials) {
                context.expandedTestimonials = {};
            }
            
            context.expandedTestimonials[testimonialId] = !context.expandedTestimonials[testimonialId];
        }
    },

    callbacks: {
        /**
         * Initialize the testimonials component
         */
        initTestimonials: () => {
            const context = getContext();
            
            // Initialize expanded testimonials state if not present
            if (!context.expandedTestimonials) {
                context.expandedTestimonials = {};
            }

            // Set up any initial calculations
            if (context.testimonials && context.itemsPerPage) {
                const totalPages = Math.ceil(context.testimonials.length / context.itemsPerPage);
                if (context.totalPages !== totalPages) {
                    context.totalPages = totalPages;
                }
            }
        },

        /**
         * Handle testimonial visibility animations
         */
        onTestimonialVisible: () => {
            // Add any animation or visibility logic here if needed
        }
    },

    state: {
        /**
         * Get testimonials for current page
         */
        get currentTestimonials() {
            const context = getContext();
            
            if (!context.testimonials || !Array.isArray(context.testimonials)) {
                return [];
            }

            const start = (context.currentPage - 1) * context.itemsPerPage;
            const end = start + context.itemsPerPage;
            
            return context.testimonials.slice(start, end);
        },

        /**
         * Check if previous page button should be enabled
         */
        get canGoPrev() {
            const context = getContext();
            return context.currentPage > 1;
        },

        /**
         * Check if next page button should be enabled
         */
        get canGoNext() {
            const context = getContext();
            return context.currentPage < context.totalPages;
        },

        /**
         * Get array of page numbers for pagination
         */
        get pageNumbers() {
            const context = getContext();
            const pages = [];
            
            for (let i = 1; i <= context.totalPages; i++) {
                pages.push(i);
            }
            
            return pages;
        },

        /**
         * Get current page info text
         */
        get pageInfo() {
            const context = getContext();
            return `Page ${context.currentPage} of ${context.totalPages}`;
        },

        /**
         * Check if a specific testimonial is expanded
         */
        get isTestimonialExpanded() {
            const context = getContext();
            return (testimonialId) => {
                return context.expandedTestimonials && context.expandedTestimonials[testimonialId];
            };
        },

        /**
         * Get CSS classes for testimonial grid based on layout
         */
        get gridClasses() {
            const context = getContext();
            let classes = 'rs-testimonials-grid';
            
            if (context.layout === 'grid') {
                classes += ` rs-grid-columns-${context.columns || 2}`;
            } else if (context.layout === 'list') {
                classes += ' rs-list-layout';
            } else if (context.layout === 'carousel') {
                classes += ' rs-carousel-layout';
            }
            
            return classes;
        },

        /**
         * Check if testimonials are loading
         */
        get isLoading() {
            const context = getContext();
            return context.loading || false;
        },

        /**
         * Check if there are any testimonials to display
         */
        get hasTestimonials() {
            const context = getContext();
            return context.testimonials && context.testimonials.length > 0;
        },

        /**
         * Get error message if any
         */
        get errorMessage() {
            const context = getContext();
            return context.error || null;
        }
    }
});
