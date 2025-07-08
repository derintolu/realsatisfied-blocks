(function($) {
    'use strict';

    // Initialize pagination when document is ready
    $(document).ready(function() {
        initAgentTestimonialsPagination();
    });

    function initAgentTestimonialsPagination() {
        $('.realsatisfied-agent-testimonials').each(function() {
            var $container = $(this);
            var $testimonialsContainer = $container.find('.testimonials-container');
            var $pagination = $container.find('.testimonials-pagination');
            
            if ($testimonialsContainer.length === 0 || $pagination.length === 0) {
                return; // No pagination needed
            }

            try {
                var testimonials_data = $testimonialsContainer.data('testimonials');
                var attributes_data = $testimonialsContainer.data('attributes');
                var allTestimonials = [];
                var blockAttributes = {};
                
                // Parse testimonials data
                if (typeof testimonials_data === 'string') {
                    allTestimonials = JSON.parse(testimonials_data);
                } else if (Array.isArray(testimonials_data)) {
                    allTestimonials = testimonials_data;
                } else {
                    console.warn('RealSatisfied Agent Testimonials: Invalid testimonials data format');
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
                
                var itemsPerPage = parseInt($testimonialsContainer.data('items-per-page')) || 6;
                var totalPages = parseInt($testimonialsContainer.data('total-pages')) || 1;
                var currentPage = 1;

                // Ensure we have testimonials to paginate
                if (!allTestimonials || allTestimonials.length === 0) {
                    console.warn('RealSatisfied Agent Testimonials: No testimonials data found');
                    return;
                }

                // Get layout and columns from the container
                var layout = $container.hasClass('layout-grid') ? 'grid' : 
                            $container.hasClass('layout-list') ? 'list' : 
                            $container.hasClass('layout-slider') ? 'slider' : 'grid';
                
                var $gridContainer = $testimonialsContainer.find('.testimonials-grid');
                var columns = 2; // Default
                if ($gridContainer.length > 0) {
                    if ($gridContainer.hasClass('columns-1')) columns = 1;
                    else if ($gridContainer.hasClass('columns-2')) columns = 2;
                    else if ($gridContainer.hasClass('columns-3')) columns = 3;
                    else if ($gridContainer.hasClass('columns-4')) columns = 4;
                }

                // Bind pagination events
                $pagination.find('.pagination-prev').on('click', function(e) {
                    e.preventDefault();
                    if (currentPage > 1) {
                        currentPage--;
                        updateTestimonials();
                    }
                });

                $pagination.find('.pagination-next').on('click', function(e) {
                    e.preventDefault();
                    if (currentPage < totalPages) {
                        currentPage++;
                        updateTestimonials();
                    }
                });
            } catch (error) {
                console.error('RealSatisfied Agent Testimonials: Error initializing pagination', error);
                return;
            }

            function updateTestimonials() {
                var startIndex = (currentPage - 1) * itemsPerPage;
                var endIndex = startIndex + itemsPerPage;
                var pageTestimonials = allTestimonials.slice(startIndex, endIndex);

                // Generate new HTML for current page
                var html = generateTestimonialsHTML(pageTestimonials, layout, columns, blockAttributes);
                
                // Update the testimonials display
                var $testimonialsDisplay = $testimonialsContainer.find('.testimonials-grid, .testimonials-list, .testimonials-slider');
                $testimonialsDisplay.fadeOut(200, function() {
                    $(this).replaceWith(html);
                    $testimonialsContainer.find('.testimonials-grid, .testimonials-list, .testimonials-slider').fadeIn(200);
                });

                // Update pagination controls
                updatePaginationControls();
            }

            function updatePaginationControls() {
                var $prevBtn = $pagination.find('.pagination-prev');
                var $nextBtn = $pagination.find('.pagination-next');
                var $currentPageSpan = $pagination.find('.current-page');

                // Update page number
                $currentPageSpan.text(currentPage);

                // Update button states
                $prevBtn.prop('disabled', currentPage <= 1);
                $nextBtn.prop('disabled', currentPage >= totalPages);
            }

            function generateTestimonialsHTML(testimonials, layout, columns, attributes) {
                var html = '';
                var containerClass = '';
                var wrapperStart = '';
                var wrapperEnd = '';
                
                switch (layout) {
                    case 'grid':
                        containerClass = 'testimonials-grid columns-' + columns;
                        wrapperStart = '<div class="' + containerClass + '">';
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
                    var testimonialHTML = generateTestimonialItemHTML(testimonial, attributes);
                    
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
                var showCustomerName = attributes.showCustomerName !== false;
                var showDate = attributes.showDate !== false;
                var showRatings = attributes.showRatings === true;
                var showCustomerType = attributes.showCustomerType !== false;
                var showQuotationMarks = attributes.showQuotationMarks !== false;
                var showSatisfactionRating = attributes.showSatisfactionRating !== false;
                var showRecommendationRating = attributes.showRecommendationRating !== false;
                var showPerformanceRating = attributes.showPerformanceRating !== false;
                var showRatingValues = attributes.showRatingValues !== false;
                var excerptLength = parseInt(attributes.excerptLength) || 150;
                
                var html = '<div class="testimonial-item testimonial-card">';
                
                // Testimonial text with optional quotation marks
                var description = testimonial.description || '';
                if (excerptLength > 0 && description.length > excerptLength) {
                    description = description.substring(0, excerptLength) + '...';
                }
                
                if (showQuotationMarks) {
                    html += '<div class="testimonial-text">"' + escapeHtml(description) + '"</div>';
                } else {
                    html += '<div class="testimonial-text">' + escapeHtml(description) + '</div>';
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
                    var date = new Date(testimonial.pubDate);
                    var dateString = date.toLocaleDateString('en-US', { 
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
                var html = '';
                var fullStars = Math.floor(rating);
                var halfStar = (rating - fullStars) >= 0.5;
                var emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

                // Full stars
                for (var i = 0; i < fullStars; i++) {
                    html += '<span class="star star-full">★</span>';
                }

                // Half star
                if (halfStar) {
                    html += '<span class="star star-half">☆</span>';
                }

                // Empty stars
                for (var i = 0; i < emptyStars; i++) {
                    html += '<span class="star star-empty">☆</span>';
                }

                return html;
            }

            function escapeHtml(text) {
                var map = {
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

})(jQuery);
