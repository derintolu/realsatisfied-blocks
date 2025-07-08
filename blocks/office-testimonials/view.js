/**
 * WordPress Interactivity API store for Office Testimonials block
 * 
 * This replaces the legacy jQuery implementation and provides:
 * - Pagination functionality (next/previous page naviga        get totalPages() {
            const context = getContext();
            
            if (!context.enablePagination) {
                return 1;
            }
            
            // Calculate filtered testimonials count for pagination
            if (!context.testimonials || !Array.isArray(context.testimonials)) {
                return 1;
            }

            let filtered = [...context.testimonials];

            // Apply agent filter if active
            if (context.activeFilter && context.activeFilter.type === 'agent') {
                filtered = filtered.filter(testimonial => 
                    testimonial.display_name === context.activeFilter.value
                );
            }estimonial display based on current page
 * - Agent filtering and sorting capabilities
 * - Context-aware state management for office testimonial data
 * - Grid, list, and slider layout support
 * - Error handling and loading states
 * 
 * @since 1.0.0 Migrated to WordPress Interactivity API
 */

import { store, getContext } from '@wordpress/interactivity';

store('realsatisfied-office-testimonials', {
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
         * Filter testimonials by agent
         */
        filterByAgent: (event) => {
            const context = getContext();
            const agentId = event.target.value;
            
            if (agentId === 'all') {
                context.activeFilter = null;
            } else {
                context.activeFilter = {
                    type: 'agent',
                    value: agentId
                };
            }
            
            // Reset to first page when filtering
            context.currentPage = 1;
        },

        /**
         * Sort testimonials
         */
        sortTestimonials: (event) => {
            const context = getContext();
            const sortBy = event.target.value;
            
            context.sortBy = sortBy;
            
            // Reset to first page when sorting
            context.currentPage = 1;
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
         * Initialize the office testimonials component
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

            // Initialize filtering and sorting
            if (!context.activeFilter) {
                context.activeFilter = null;
            }
            
            if (!context.sortBy) {
                context.sortBy = 'date';
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

            let filtered = [...context.testimonials];

            // Apply agent filter if active
            if (context.activeFilter && context.activeFilter.type === 'agent') {
                filtered = filtered.filter(testimonial => 
                    testimonial.display_name === context.activeFilter.value
                );
            }

            // Apply sorting
            filtered.sort((a, b) => {
                let result = 0;
                
                switch (context.sortBy) {
                    case 'date':
                        result = new Date(a.pubDate) - new Date(b.pubDate);
                        break;
                    case 'date_desc':
                        result = new Date(b.pubDate) - new Date(a.pubDate);
                        break;
                    case 'rating':
                        const ratingA = (a.satisfaction + a.recommendation + a.performance) / 3;
                        const ratingB = (b.satisfaction + b.recommendation + b.performance) / 3;
                        result = ratingB - ratingA; // Higher ratings first
                        break;
                    case 'agent':
                        result = (a.display_name || '').localeCompare(b.display_name || '');
                        break;
                    default:
                        result = 0;
                }
                
                return result;
            });

            // Apply pagination if enabled
            if (!context.enablePagination) {
                return filtered;
            }

            const start = (context.currentPage - 1) * context.itemsPerPage;
            const end = start + context.itemsPerPage;
            
            return filtered.slice(start, end);
        },

        /**
         * Get total pages based on filtered results
         */
        get totalPages() {
            const context = getContext();
            
            if (!context.enablePagination) {
                return 1;
            }
            
            // Calculate filtered testimonials count for pagination
            if (!context.testimonials || !Array.isArray(context.testimonials)) {
                return 1;
            }

            let filtered = [...context.testimonials];

            // Apply agent filter if active
            if (context.activeFilter && context.activeFilter.type === 'agent') {
                filtered = filtered.filter(testimonial => 
                    testimonial.display_name === context.activeFilter.value
                );
            }
            
            return Math.ceil(filtered.length / context.itemsPerPage);
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
         * Get list of unique agents for filtering
         */
        get availableAgents() {
            const context = getContext();
            
            if (!context.testimonials || !Array.isArray(context.testimonials)) {
                return [];
            }

            const agents = [];
            const seenAgents = new Set();

            context.testimonials.forEach(testimonial => {
                if (testimonial.display_name && !seenAgents.has(testimonial.display_name)) {
                    seenAgents.add(testimonial.display_name);
                    agents.push({
                        id: testimonial.display_name,
                        name: testimonial.display_name,
                        photo: testimonial.agent_photo || ''
                    });
                }
            });

            // Sort agents by name
            agents.sort((a, b) => a.name.localeCompare(b.name));

            return agents;
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
            let classes = 'rs-office-testimonials-grid';
            
            if (context.layout === 'grid') {
                classes += ` rs-grid-columns-${context.columns || 2}`;
            } else if (context.layout === 'list') {
                classes += ' rs-list-layout';
            } else if (context.layout === 'slider') {
                classes += ' rs-slider-layout';
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
        },

        /**
         * Check if filtering is active
         */
        get hasActiveFilter() {
            const context = getContext();
            return context.activeFilter !== null;
        },

        /**
         * Get active filter display text
         */
        get activeFilterText() {
            const context = getContext();
            
            if (!context.activeFilter) {
                return '';
            }
            
            if (context.activeFilter.type === 'agent') {
                const agent = context.availableAgents.find(a => a.id === context.activeFilter.value);
                return agent ? `Filtered by: ${agent.name}` : 'Filtered by agent';
            }
            
            return '';
        }
    }
});
