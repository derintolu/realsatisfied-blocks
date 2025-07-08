// RealSatisfied Agent Testimonials Frontend Script - Vanilla JavaScript Version
console.log('RealSatisfied Agent Testimonials Frontend Script Loaded (Vanilla JS)');

(function() {
    'use strict';

    // Utility functions to replace jQuery methods
    const utils = {
        // Replace $(document).ready()
        ready: function(callback) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback);
            } else {
                callback();
            }
        },

        // Replace $(selector)
        find: function(selector, context = document) {
            return context.querySelectorAll(selector);
        },

        // Replace $.find() for single element
        findOne: function(selector, context = document) {
            return context.querySelector(selector);
        },

        // Replace $.each()
        each: function(elements, callback) {
            Array.from(elements).forEach(callback);
        },

        // Replace $.data()
        getData: function(element, key) {
            const dataValue = element.dataset[this.camelCase(key)];
            try {
                return JSON.parse(dataValue);
            } catch (e) {
                return dataValue;
            }
        },

        // Replace $.hasClass()
        hasClass: function(element, className) {
            return element.classList.contains(className);
        },

        // Replace $.text()
        setText: function(element, text) {
            element.textContent = text;
        },

        // Replace $.prop()
        setProp: function(element, property, value) {
            element[property] = value;
        },

        // Convert kebab-case to camelCase for dataset
        camelCase: function(str) {
            return str.replace(/-([a-z])/g, function(match, letter) {
                return letter.toUpperCase();
            });
        },

        // Replace $.on()
        on: function(element, event, callback) {
            element.addEventListener(event, callback);
        }
    };

    // Initialize pagination when document is ready
    utils.ready(function() {
        initAgentTestimonialsPagination();
    });

    function initAgentTestimonialsPagination() {
        const containers = utils.find('.realsatisfied-agent-testimonials');
        
        utils.each(containers, function(container) {
            const testimonialsContainer = utils.findOne('.testimonials-container', container);
            const pagination = utils.findOne('.testimonials-pagination', container);
            
            if (!testimonialsContainer || !pagination) {
                return; // No pagination needed
            }

            try {
                const testimonials_data = utils.getData(testimonialsContainer, 'testimonials');
                const attributes_data = utils.getData(testimonialsContainer, 'attributes');
                let allTestimonials = [];
                let blockAttributes = {};
                
                // Parse testimonials data
                if (typeof testimonials_data === 'string') {
                    allTestimonials = JSON.parse(testimonials_data);
                } else if (Array.isArray(testimonials_data)) {
                    allTestimonials = testimonials_data;
                } else {
                    return;
                }

                // Parse attributes data
                if (typeof attributes_data === 'string') {
                    blockAttributes = JSON.parse(attributes_data);
                } else if (typeof attributes_data === 'object') {
                    blockAttributes = attributes_data;
                } else {
                    blockAttributes = {}; // Use defaults
                }
                
                const itemsPerPage = parseInt(utils.getData(testimonialsContainer, 'items-per-page')) || 6;
                const totalPages = parseInt(utils.getData(testimonialsContainer, 'total-pages')) || 1;
                let currentPage = 1;

                // Ensure we have testimonials to paginate
                if (!allTestimonials || allTestimonials.length === 0) {
                    return;
                }

                // Get layout and columns from the container
                const layout = utils.hasClass(container, 'layout-grid') ? 'grid' : 
                              utils.hasClass(container, 'layout-list') ? 'list' : 
                              utils.hasClass(container, 'layout-slider') ? 'slider' : 'grid';
                
                const gridContainer = utils.findOne('.testimonials-grid', testimonialsContainer);
                let columns = 2; // Default
                if (gridContainer) {
                    if (utils.hasClass(gridContainer, 'columns-1')) columns = 1;
                    else if (utils.hasClass(gridContainer, 'columns-2')) columns = 2;
                    else if (utils.hasClass(gridContainer, 'columns-3')) columns = 3;
                    else if (utils.hasClass(gridContainer, 'columns-4')) columns = 4;
                }

                // Bind pagination events
                const prevBtn = utils.findOne('.pagination-prev', pagination);
                const nextBtn = utils.findOne('.pagination-next', pagination);

                if (prevBtn) {
                    utils.on(prevBtn, 'click', function(e) {
                        e.preventDefault();
                        if (currentPage > 1) {
                            currentPage--;
                            updateTestimonials();
                        }
                    });
                }

                if (nextBtn) {
                    utils.on(nextBtn, 'click', function(e) {
                        e.preventDefault();
                        if (currentPage < totalPages) {
                            currentPage++;
                            updateTestimonials();
                        }
                    });
                }
                
            } catch (error) {
                console.error('RealSatisfied Agent Testimonials: Error initializing pagination', error);
                return;
            }

            function updateTestimonials() {
                const startIndex = (currentPage - 1) * itemsPerPage;
                const endIndex = startIndex + itemsPerPage;
                const pageTestimonials = allTestimonials.slice(startIndex, endIndex);

                // Generate new HTML for current page
                const html = generateTestimonialsHTML(pageTestimonials, layout, columns, blockAttributes);
                
                // Update the testimonials display
                const testimonialsDisplay = utils.findOne('.testimonials-grid, .testimonials-list, .testimonials-slider', testimonialsContainer);
                if (testimonialsDisplay) {
                    testimonialsDisplay.outerHTML = html;
                }

                // Update pagination controls
                updatePaginationControls();
            }

            function updatePaginationControls() {
                const prevBtn = utils.findOne('.pagination-prev', pagination);
                const nextBtn = utils.findOne('.pagination-next', pagination);
                const currentPageSpan = utils.findOne('.current-page', pagination);

                // Update page number
                if (currentPageSpan) {
                    utils.setText(currentPageSpan, currentPage);
                }

                // Update button states
                if (prevBtn) {
                    utils.setProp(prevBtn, 'disabled', currentPage <= 1);
                }
                if (nextBtn) {
                    utils.setProp(nextBtn, 'disabled', currentPage >= totalPages);
                }
            }

            function generateTestimonialsHTML(testimonials, layout, columns, attributes) {
                let html = '';
                let containerClass = '';
                let wrapperStart = '';
                let wrapperEnd = '';
                
                switch (layout) {
                    case 'grid':
                        containerClass = 'testimonials-grid columns-' + columns;
                        
                        // Apply custom grid columns if needed
                        const containerStyles = [];
                        if (columns && columns !== 2) {
                            containerStyles.push('grid-template-columns: repeat(' + columns + ', 1fr)');
                        }
                        
                        wrapperStart = '<div class="' + containerClass + '"' + 
                                     (containerStyles.length > 0 ? ' style="' + containerStyles.join('; ') + '"' : '') + '>';
                        wrapperEnd = '</div>';
                        break;
                    case 'list':
                        containerClass = 'testimonials-list';
                        wrapperStart = '<div class="' + containerClass + '">';
                        wrapperEnd = '</div>';
                        break;
                    case 'slider':
                        containerClass = 'testimonials-slider flexslider';
                        wrapperStart = '<div class="' + containerClass + '"><ul class="slides">';
                        wrapperEnd = '</ul></div>';
                        break;
                    default:
                        containerClass = 'testimonials-grid columns-' + columns;
                        wrapperStart = '<div class="' + containerClass + '">';
                        wrapperEnd = '</div>';
                        break;
                }
                
                html += wrapperStart;
                
                testimonials.forEach(function(testimonial) {
                    const testimonialHTML = generateTestimonialItemHTML(testimonial, attributes);
                    
                    if (layout === 'slider') {
                        html += '<li>' + testimonialHTML + '</li>';
                    } else {
                        html += testimonialHTML;
                    }
                });

                html += wrapperEnd;
                
                return html;
            }

            function generateTestimonialItemHTML(testimonial, attributes) {
                attributes = attributes || {};
                
                // Get display options with defaults
                const showCustomerName = attributes.showCustomerName !== false;
                const showDate = attributes.showDate !== false;
                const showRatings = attributes.showRatings === true;
                const showCustomerType = attributes.showCustomerType !== false;
                const showQuotationMarks = attributes.showQuotationMarks !== false;
                const showSatisfactionRating = attributes.showSatisfactionRating !== false;
                const showRecommendationRating = attributes.showRecommendationRating !== false;
                const showPerformanceRating = attributes.showPerformanceRating !== false;
                const showRatingValues = attributes.showRatingValues !== false;
                const excerptLength = parseInt(attributes.excerptLength) || 150;
                
                // Build card styles (matching PHP build_card_styles function)
                const cardStyles = [];
                if (attributes.backgroundColor) {
                    cardStyles.push('background-color: ' + attributes.backgroundColor);
                }
                if (attributes.borderColor) {
                    cardStyles.push('border-color: ' + attributes.borderColor);
                }
                if (attributes.borderRadius) {
                    cardStyles.push('border-radius: ' + attributes.borderRadius);
                }
                const cardStyleAttr = cardStyles.length > 0 ? ' style="' + cardStyles.join('; ') + '"' : '';
                
                let html = '<div class="testimonial-item testimonial-card"' + cardStyleAttr + '>';
                
                // Build text styles (matching PHP build_text_styles function)
                const textStyles = [];
                if (attributes.textColor) {
                    textStyles.push('color: ' + attributes.textColor);
                }
                const textStyleAttr = textStyles.length > 0 ? ' style="' + textStyles.join('; ') + '"' : '';
                
                // Testimonial text with optional quotation marks
                let description = testimonial.description || '';
                if (excerptLength > 0 && description.length > excerptLength) {
                    description = description.substring(0, excerptLength) + '...';
                }
                
                if (showQuotationMarks) {
                    html += '<div class="testimonial-text"' + textStyleAttr + '>"' + escapeHtml(description) + '"</div>';
                } else {
                    html += '<div class="testimonial-text"' + textStyleAttr + '>' + escapeHtml(description) + '</div>';
                }
                
                // Meta section
                html += '<div class="testimonial-meta">';
                html += '<div class="testimonial-details">';
                
                // Customer name
                if (showCustomerName && testimonial.title) {
                    html += '<div class="customer-name">' + escapeHtml(testimonial.title) + '</div>';
                }
                
                // Customer type
                if (showCustomerType && testimonial.customer_type) {
                    html += '<div class="customer-type">' + escapeHtml(testimonial.customer_type) + '</div>';
                }
                
                // Date
                if (showDate && testimonial.pubDate) {
                    const date = new Date(testimonial.pubDate);
                    const dateString = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    html += '<div class="testimonial-date">' + escapeHtml(dateString) + '</div>';
                }
                
                // Detailed ratings section
                if (showRatings && (testimonial.satisfaction || testimonial.recommendation || testimonial.performance)) {
                    html += '<div class="testimonial-ratings">';
                    html += '<div class="ratings-grid">';
                    
                    if (showSatisfactionRating && testimonial.satisfaction) {
                        html += '<div class="rating-item">';
                        html += '<span class="rating-label">Satisfaction:</span>';
                        html += '<span class="rating-stars">' + generateStarsHTML(testimonial.satisfaction / 20) + '</span>';
                        if (showRatingValues) {
                            html += '<span class="rating-value">(' + testimonial.satisfaction + '%)</span>';
                        }
                        html += '</div>';
                    }
                    
                    if (showRecommendationRating && testimonial.recommendation) {
                        html += '<div class="rating-item">';
                        html += '<span class="rating-label">Recommendation:</span>';
                        html += '<span class="rating-stars">' + generateStarsHTML(testimonial.recommendation / 20) + '</span>';
                        if (showRatingValues) {
                            html += '<span class="rating-value">(' + testimonial.recommendation + '%)</span>';
                        }
                        html += '</div>';
                    }
                    
                    if (showPerformanceRating && testimonial.performance) {
                        html += '<div class="rating-item">';
                        html += '<span class="rating-label">Performance:</span>';
                        html += '<span class="rating-stars">' + generateStarsHTML(testimonial.performance / 20) + '</span>';
                        if (showRatingValues) {
                            html += '<span class="rating-value">(' + testimonial.performance + '%)</span>';
                        }
                        html += '</div>';
                    }
                    
                    html += '</div>'; // ratings-grid
                    html += '</div>'; // testimonial-ratings
                }
                
                html += '</div>'; // testimonial-details
                html += '</div>'; // testimonial-meta
                html += '</div>'; // testimonial-item
                
                return html;
            }

            function generateStarsHTML(rating) {
                let html = '';
                const fullStars = Math.floor(rating);
                const halfStar = (rating - fullStars) >= 0.5;
                const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

                // Full stars
                for (let i = 0; i < fullStars; i++) {
                    html += '<span class="star star-full">★</span>';
                }

                // Half star
                if (halfStar) {
                    html += '<span class="star star-half">☆</span>';
                }

                // Empty stars
                for (let i = 0; i < emptyStars; i++) {
                    html += '<span class="star star-empty">☆</span>';
                }

                return html;
            }

            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return (text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
            }
        });
    }

})();
