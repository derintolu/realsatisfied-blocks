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

            var allTestimonials = $testimonialsContainer.data('testimonials') || [];
            var itemsPerPage = parseInt($testimonialsContainer.data('items-per-page')) || 6;
            var totalPages = parseInt($testimonialsContainer.data('total-pages')) || 1;
            var currentPage = 1;

            // Get layout and attributes from the container
            var layout = $container.hasClass('layout-grid') ? 'grid' : 
                        $container.hasClass('layout-list') ? 'list' : 
                        $container.hasClass('layout-slider') ? 'slider' : 'grid';

            // Bind pagination events
            $pagination.find('.pagination-prev').on('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    updateTestimonials();
                }
            });

            $pagination.find('.pagination-next').on('click', function() {
                if (currentPage < totalPages) {
                    currentPage++;
                    updateTestimonials();
                }
            });

            function updateTestimonials() {
                var startIndex = (currentPage - 1) * itemsPerPage;
                var endIndex = startIndex + itemsPerPage;
                var pageTestimonials = allTestimonials.slice(startIndex, endIndex);

                // Generate new HTML for current page
                var html = generateTestimonialsHTML(pageTestimonials, layout);
                
                // Update the testimonials display
                var $testimonialsDisplay = $testimonialsContainer.find('.testimonials-grid, .testimonials-list, .testimonials-slider');
                $testimonialsDisplay.fadeOut(200, function() {
                    $(this).html(html).fadeIn(200);
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

            function generateTestimonialsHTML(testimonials, layout) {
                var html = '';
                
                testimonials.forEach(function(testimonial) {
                    var testimonialHTML = generateTestimonialItemHTML(testimonial);
                    
                    if (layout === 'slider') {
                        html += '<li>' + testimonialHTML + '</li>';
                    } else {
                        html += testimonialHTML;
                    }
                });

                return html;
            }

            function generateTestimonialItemHTML(testimonial) {
                var html = '<div class="testimonial-item">';
                
                // Testimonial content
                var description = testimonial.description || '';
                var excerptLength = 150; // Default excerpt length
                if (excerptLength > 0 && description.length > excerptLength) {
                    description = description.substring(0, excerptLength) + '...';
                }
                
                html += '<div class="testimonial-content">' + escapeHtml(description) + '</div>';
                
                // Meta information
                html += '<div class="testimonial-meta">';
                
                // Customer name
                if (testimonial.title) {
                    html += '<div class="customer-name">' + escapeHtml(testimonial.title) + '</div>';
                }
                
                // Customer type
                if (testimonial.customer_type) {
                    html += '<div class="customer-type">' + escapeHtml(testimonial.customer_type) + '</div>';
                }
                
                // Date
                if (testimonial.pubDate) {
                    var date = new Date(testimonial.pubDate);
                    var dateString = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    html += '<div class="testimonial-date">' + escapeHtml(dateString) + '</div>';
                }
                
                // Ratings
                if (testimonial.satisfaction && testimonial.recommendation && testimonial.performance) {
                    var avgRating = (testimonial.satisfaction + testimonial.recommendation + testimonial.performance) / 3;
                    html += '<div class="testimonial-rating">';
                    html += '<div class="stars">' + generateStarsHTML(avgRating) + '</div>';
                    html += '<span class="rating-text">' + avgRating.toFixed(1) + '/5</span>';
                    html += '</div>';
                }
                
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
