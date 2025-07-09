<?php
/**
 * RealSatisfied Agent Testimonials Block
 * 
 * Displays customer testimonials for a specific agent using the ACF vanity field
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RealSatisfied Agent Testimonials Block Class
 */
class RealSatisfied_Agent_Testimonials_Block {
    
    /**
     * Block name
     *
     * @var string
     */
    private $block_name = 'realsatisfied-blocks/agent-testimonials';

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register block is now called directly from main plugin
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        // Frontend assets are handled by the main plugin file
    }

    /**
     * Register the block
     */
    public function register_block() {
        // Check if required classes exist
        if (!class_exists('RealSatisfied_Agent_RSS_Parser') || 
            !class_exists('RealSatisfied_Custom_Fields')) {
            // Still register the block to test
        }

        $result = register_block_type($this->block_name, array(
            'render_callback' => array($this, 'render_block'),
            'supports' => array(
                'html' => false,
                'align' => array('left', 'center', 'right', 'wide', 'full'),
                'alignWide' => true,
                'anchor' => true,
                'customClassName' => true,
                'interactivity' => true, // Add Interactivity API support
                'color' => array(
                    'gradients' => true,
                    'link' => true,
                    'text' => true,
                    'background' => true
                ),
                'typography' => array(
                    'fontSize' => true,
                    'lineHeight' => true,
                    'fontFamily' => true,
                    'fontWeight' => true,
                    'fontStyle' => true,
                    'textTransform' => true,
                    'textDecoration' => true,
                    'letterSpacing' => true
                ),
                'spacing' => array(
                    'margin' => true,
                    'padding' => true,
                    'blockGap' => true
                ),
                'border' => array(
                    'color' => true,
                    'radius' => true,
                    'style' => true,
                    'width' => true
                ),
                'dimensions' => array(
                    'minHeight' => true
                )
            ),
            'attributes' => array(
                'useCustomField' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'customFieldName' => array(
                    'type' => 'string',
                    'default' => 'realsatified-agent-vanity'
                ),
                'manualVanityKey' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'layout' => array(
                    'type' => 'string',
                    'default' => 'grid'
                ),
                'columns' => array(
                    'type' => 'number',
                    'default' => 2
                ),
                'testimonialCount' => array(
                    'type' => 'number',
                    'default' => 6
                ),
                'enablePagination' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'itemsPerPage' => array(
                    'type' => 'number',
                    'default' => 3
                ),
                'showCustomerName' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showDate' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showRatings' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'showSatisfactionRating' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showRecommendationRating' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showPerformanceRating' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showRatingValues' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showQuotationMarks' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showCustomerType' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'excerptLength' => array(
                    'type' => 'number',
                    'default' => 150
                ),
                'sortBy' => array(
                    'type' => 'string',
                    'default' => 'date'
                ),
                'sortOrder' => array(
                    'type' => 'string',
                    'default' => 'desc'
                ),
                'backgroundColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'textColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'borderColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'borderRadius' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'paginationBackgroundColor' => array(
                    'type' => 'string',
                    'default' => '#007cba'
                ),
                'paginationTextColor' => array(
                    'type' => 'string',
                    'default' => '#ffffff'
                ),
                'paginationHoverBackgroundColor' => array(
                    'type' => 'string',
                    'default' => '#005a87'
                ),
                'paginationBorderRadius' => array(
                    'type' => 'string',
                    'default' => '5px'
                )
            )
        ));
    }

    /**
     * Render the block
     *
     * @param array $attributes Block attributes
     * @return string Block HTML
     */
    public function render_block($attributes) {
        // Get vanity key
        $vanity_key = $this->get_vanity_key($attributes);
        
        if (empty($vanity_key)) {
            return $this->render_error(__('No agent vanity key found. Please set the "realsatified-agent-vanity" ACF field for this page, or enter a manual vanity key in the block settings.', 'realsatisfied-blocks'));
        }

        // Get RSS parser instance
        $rss_parser = RealSatisfied_Agent_RSS_Parser::get_instance();
        
        // Fetch agent data
        $agent_data = $rss_parser->fetch_agent_data($vanity_key);
        
        if (is_wp_error($agent_data)) {
            return $this->render_error($agent_data->get_error_message());
        }

        // Get testimonials
        $testimonials = $agent_data['testimonials'];
        
        if (empty($testimonials)) {
            return $this->render_error(__('No testimonials found for this agent.', 'realsatisfied-blocks'));
        }

        // Filter and sort testimonials
        $filtered_testimonials = $this->filter_and_sort_testimonials($testimonials, $attributes);
        
        // Process testimonials for display (format dates, add quotation marks, etc.)
        $processed_testimonials = array();
        foreach ($filtered_testimonials as $testimonial) {
            $excerpt_length = intval($attributes['excerptLength'] ?? 150);
            $description = $this->clean_rss_text($testimonial['description'] ?? '');
            
            // Truncate description if needed
            if ($excerpt_length > 0 && strlen($description) > $excerpt_length) {
                $description = substr($description, 0, $excerpt_length) . '...';
            }
            
            // Format date
            $formatted_date = '';
            if (!empty($testimonial['pubDate'])) {
                try {
                    $date = new DateTime($testimonial['pubDate']);
                    $formatted_date = $date->format('F j, Y');
                } catch (Exception $e) {
                    $formatted_date = $testimonial['pubDate']; // Fallback to original
                }
            }
            
            // Create processed testimonial
            $processed_testimonial = array(
                'title' => $this->clean_rss_text($testimonial['title'] ?? ''),
                'description' => $description,
                'quotedDescription' => '"' . $description . '"',
                'customer_type' => $testimonial['customer_type'] ?? '',
                'pubDate' => $testimonial['pubDate'] ?? '',
                'formattedDate' => $formatted_date,
                'satisfaction' => $testimonial['satisfaction'] ?? '',
                'recommendation' => $testimonial['recommendation'] ?? '',
                'performance' => $testimonial['performance'] ?? '',
                'display_name' => $this->clean_rss_text($testimonial['display_name'] ?? ''),
                'avatar' => $testimonial['avatar'] ?? '',
                'hasRatings' => !empty($testimonial['satisfaction']) || !empty($testimonial['recommendation']) || !empty($testimonial['performance']),
                'satisfactionStars' => $this->generate_stars($testimonial['satisfaction'] ?? ''),
                'recommendationStars' => $this->generate_stars($testimonial['recommendation'] ?? ''),
                'performanceStars' => $this->generate_stars($testimonial['performance'] ?? ''),
                'satisfactionValue' => !empty($testimonial['satisfaction']) ? '(' . $testimonial['satisfaction'] . '%)' : '',
                'recommendationValue' => !empty($testimonial['recommendation']) ? '(' . $testimonial['recommendation'] . '%)' : '',
                'performanceValue' => !empty($testimonial['performance']) ? '(' . $testimonial['performance'] . '%)' : ''
            );
            
            $processed_testimonials[] = $processed_testimonial;
        }

        // Handle pagination or simple count limit
        $enable_pagination = $attributes['enablePagination'] ?? false;
        $total_testimonials = count($processed_testimonials);
        
        if ($enable_pagination) {
            $items_per_page = intval($attributes['itemsPerPage'] ?? 6);
            $current_page = 1; // Default to first page for initial render
            $total_pages = ceil($total_testimonials / $items_per_page);
            $offset = ($current_page - 1) * $items_per_page;
            $paged_testimonials = array_slice($processed_testimonials, $offset, $items_per_page);
        } else {
            // Simple count limit (original behavior)
            $testimonial_count = intval($attributes['testimonialCount'] ?? 6);
            if ($testimonial_count > 0) {
                $processed_testimonials = array_slice($processed_testimonials, 0, $testimonial_count);
            }
            $paged_testimonials = $processed_testimonials;
            $total_pages = 1;
        }

        // Get block wrapper attributes
        $wrapper_attributes = get_block_wrapper_attributes(array(
            'class' => 'realsatisfied-agent-testimonials layout-' . esc_attr($attributes['layout'] ?? 'grid')
        ));

        // Build wrapper styles
        $wrapper_styles = $this->build_wrapper_styles($attributes);
        $style_attr = !empty($wrapper_styles) ? 'style="' . implode('; ', $wrapper_styles) . '"' : '';

        // Start building output
        ob_start();
        
        // Prepare minimal attributes for frontend (only what's needed for pagination)
        $frontend_attributes = array(
            'layout' => $attributes['layout'] ?? 'grid',
            'columns' => $attributes['columns'] ?? 2,
            'showCustomerName' => $attributes['showCustomerName'] ?? true,
            'showDate' => $attributes['showDate'] ?? true,
            'showRatings' => $attributes['showRatings'] ?? false,
            'showCustomerType' => $attributes['showCustomerType'] ?? true,
            'showQuotationMarks' => $attributes['showQuotationMarks'] ?? true,
            'showSatisfactionRating' => $attributes['showSatisfactionRating'] ?? true,
            'showRecommendationRating' => $attributes['showRecommendationRating'] ?? true,
            'showPerformanceRating' => $attributes['showPerformanceRating'] ?? true,
            'showRatingValues' => $attributes['showRatingValues'] ?? true,
            'excerptLength' => $attributes['excerptLength'] ?? 150,
            'backgroundColor' => $attributes['backgroundColor'] ?? '',
            'textColor' => $attributes['textColor'] ?? '',
            'borderColor' => $attributes['borderColor'] ?? '',
            'borderRadius' => $attributes['borderRadius'] ?? ''
        );
        
        ?>
        <div <?php echo $wrapper_attributes; ?> <?php echo $style_attr; ?>
             data-wp-interactive="realsatisfied-agent-testimonials"
             <?php echo wp_interactivity_data_wp_context(array(
                 'testimonials' => $processed_testimonials,
                 'currentPage' => 1,
                 'totalPages' => $total_pages,
                 'itemsPerPage' => $enable_pagination ? ($attributes['itemsPerPage'] ?? 6) : $total_testimonials,
                 'enablePagination' => $enable_pagination,
                 'layout' => $attributes['layout'] ?? 'grid',
                 'columns' => $attributes['columns'] ?? 2,
                 'loading' => false,
                 'error' => null,
                 'expandedTestimonials' => array()
             )); ?>
             data-wp-init="callbacks.initTestimonials">
            
            <div class="testimonials-container">
                <?php
                $layout = $attributes['layout'] ?? 'grid';
                $columns = $attributes['columns'] ?? 2;
                
                if ($layout === 'grid'):
                ?>
                    <div class="testimonials-grid columns-<?php echo esc_attr($columns); ?>">
                        <template data-wp-each="state.currentTestimonials">
                            <?php echo $this->render_testimonial_template($attributes); ?>
                        </template>
                    </div>
                <?php elseif ($layout === 'list'): ?>
                    <div class="testimonials-list">
                        <template data-wp-each="state.currentTestimonials">
                            <?php echo $this->render_testimonial_template($attributes); ?>
                        </template>
                    </div>
                <?php elseif ($layout === 'slider'): ?>
                    <div class="testimonials-slider flexslider">
                        <ul class="slides">
                            <template data-wp-each="state.currentTestimonials">
                                <li><?php echo $this->render_testimonial_template($attributes); ?></li>
                            </template>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="testimonials-grid columns-<?php echo esc_attr($columns); ?>">
                        <template data-wp-each="state.currentTestimonials">
                            <?php echo $this->render_testimonial_template($attributes); ?>
                        </template>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($enable_pagination && $total_pages > 1): ?>
                <div class="testimonials-pagination" style="<?php echo $this->get_pagination_style($attributes); ?>">
                    <button class="pagination-btn pagination-prev" 
                            data-wp-on--click="actions.prevPage"
                            data-wp-bind--disabled="!state.canGoPrev">
                        &larr; <?php _e('Previous', 'realsatisfied-blocks'); ?>
                    </button>
                    <div class="pagination-numbers">
                        <span class="pagination-info" data-wp-text="state.pageInfo"></span>
                    </div>
                    <button class="pagination-btn pagination-next"
                            data-wp-on--click="actions.nextPage" 
                            data-wp-bind--disabled="!state.canGoNext">
                        <?php _e('Next', 'realsatisfied-blocks'); ?> &rarr;
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Get vanity key from attributes or custom field
     *
     * @param array $attributes Block attributes
     * @return string Vanity key
     */
    private function get_vanity_key($attributes) {
        // If manual vanity key is provided, use it
        if (!empty($attributes['manualVanityKey'])) {
            return trim($attributes['manualVanityKey']);
        }

        // Use custom field if enabled
        if ($attributes['useCustomField'] ?? true) {
            global $post;
            
            if ($post) {
                $field_name = $attributes['customFieldName'] ?? 'realsatified-agent-vanity';
                
                // Try ACF function first
                if (function_exists('get_field')) {
                    $vanity_key = get_field($field_name, $post->ID);
                    
                    if (!empty($vanity_key)) {
                        return trim($vanity_key);
                    }
                }
                
                // Fallback to get_post_meta
                $vanity_key = get_post_meta($post->ID, $field_name, true);
                
                if (!empty($vanity_key)) {
                    return trim($vanity_key);
                }
            }
        }

        return '';
    }

    /**
     * Filter and sort testimonials
     *
     * @param array $testimonials Array of testimonials
     * @param array $attributes Block attributes
     * @return array Filtered and sorted testimonials
     */
    private function filter_and_sort_testimonials($testimonials, $attributes) {
        // Sort testimonials
        $sort_by = $attributes['sortBy'] ?? 'date';
        $sort_order = $attributes['sortOrder'] ?? 'desc';

        usort($testimonials, function($a, $b) use ($sort_by, $sort_order) {
            $result = 0;
            
            switch ($sort_by) {
                case 'date':
                    $result = strtotime($a['pubDate']) - strtotime($b['pubDate']);
                    break;
                case 'rating':
                    $rating_a = ($a['satisfaction'] + $a['recommendation'] + $a['performance']) / 3;
                    $rating_b = ($b['satisfaction'] + $b['recommendation'] + $b['performance']) / 3;
                    $result = $rating_a - $rating_b;
                    break;
            }
            
            return $sort_order === 'asc' ? $result : -$result;
        });

        return $testimonials;
    }

    /**
     * Render testimonials based on layout
     *
     * @param array $testimonials Array of testimonials
     * @param array $attributes Block attributes
     * @return string Testimonials HTML
     */
    private function render_testimonials($testimonials, $attributes) {
        $layout = $attributes['layout'] ?? 'grid';
        $columns = intval($attributes['columns'] ?? 2);
        
        $html = '';
        
        switch ($layout) {
            case 'slider':
                $html .= $this->render_slider_layout($testimonials, $attributes);
                break;
            case 'list':
                $html .= $this->render_list_layout($testimonials, $attributes);
                break;
            case 'grid':
            default:
                $html .= $this->render_grid_layout($testimonials, $attributes, $columns);
                break;
        }
        
        return $html;
    }

    /**
     * Render slider layout
     *
     * @param array $testimonials Array of testimonials
     * @param array $attributes Block attributes
     * @return string Slider HTML
     */
    private function render_slider_layout($testimonials, $attributes) {
        $html = '<div class="testimonials-slider flexslider">';
        $html .= '<ul class="slides">';
        
        foreach ($testimonials as $testimonial) {
            $html .= '<li>' . $this->render_testimonial_item($testimonial, $attributes) . '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render grid layout
     *
     * @param array $testimonials Array of testimonials
     * @param array $attributes Block attributes
     * @param int $columns Number of columns
     * @return string Grid HTML
     */
    private function render_grid_layout($testimonials, $attributes, $columns) {
        $html = '<div class="testimonials-grid columns-' . esc_attr($columns) . '">';
        
        foreach ($testimonials as $testimonial) {
            $html .= $this->render_testimonial_item($testimonial, $attributes);
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Render list layout
     *
     * @param array $testimonials Array of testimonials
     * @param array $attributes Block attributes
     * @return string List HTML
     */
    private function render_list_layout($testimonials, $attributes) {
        $html = '<div class="testimonials-list">';
        
        foreach ($testimonials as $testimonial) {
            $html .= $this->render_testimonial_item($testimonial, $attributes);
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Render individual testimonial item
     *
     * @param array $testimonial Testimonial data
     * @param array $attributes Block attributes
     * @return string Testimonial HTML
     */
    private function render_testimonial_item($testimonial, $attributes) {
        $show_customer_name = $attributes['showCustomerName'] ?? true;
        $show_date = $attributes['showDate'] ?? true;
        $show_ratings = $attributes['showRatings'] ?? false;
        $show_customer_type = $attributes['showCustomerType'] ?? true;
        $show_quotation_marks = $attributes['showQuotationMarks'] ?? true;
        $excerpt_length = intval($attributes['excerptLength'] ?? 150);

        // Build individual card styles
        $card_styles = $this->build_card_styles($attributes);
        $card_style_attr = !empty($card_styles) ? 'style="' . implode('; ', $card_styles) . '"' : '';

        $html = '<div class="testimonial-item testimonial-card" ' . $card_style_attr . '>';
        
        // Testimonial text with optional quotation marks
        $content = $testimonial['description'];
        if ($excerpt_length > 0 && strlen($content) > $excerpt_length) {
            $content = substr($content, 0, $excerpt_length) . '...';
        }
        
        // Build text styles
        $text_styles = $this->build_text_styles($attributes);
        $text_style_attr = !empty($text_styles) ? 'style="' . implode('; ', $text_styles) . '"' : '';
        
        if ($show_quotation_marks) {
            $html .= '<div class="testimonial-text" ' . $text_style_attr . '>"' . esc_html($content) . '"</div>';
        } else {
            $html .= '<div class="testimonial-text" ' . $text_style_attr . '>' . esc_html($content) . '</div>';
        }

        // Meta section
        $html .= '<div class="testimonial-meta">';
        
        // Customer info section
        $html .= '<div class="testimonial-details">';
        
        if ($show_customer_name && !empty($testimonial['title'])) {
            $html .= '<div class="customer-name">' . esc_html($testimonial['title']) . '</div>';
        }
        
        if ($show_customer_type && !empty($testimonial['customer_type'])) {
            $customer_type_color = $attributes['paginationBackgroundColor'] ?? '#007cba';
            $html .= '<div class="customer-type" style="color: ' . esc_attr($customer_type_color) . ';">' . esc_html($testimonial['customer_type']) . '</div>';
        }
        
        if ($show_date && !empty($testimonial['pubDate'])) {
            $formatted_date = date('F j, Y', strtotime($testimonial['pubDate']));
            $html .= '<div class="testimonial-date">' . esc_html($formatted_date) . '</div>';
        }
        
        // Detailed ratings section
        if ($show_ratings && isset($testimonial['satisfaction'])) {
            $html .= '<div class="testimonial-ratings">';
            $html .= $this->render_testimonial_ratings($testimonial, $attributes);
            $html .= '</div>';
        }
        
        $html .= '</div>'; // testimonial-details
        $html .= '</div>'; // testimonial-meta
        $html .= '</div>'; // testimonial-item
        
        return $html;
    }

    /**
     * Render detailed testimonial ratings
     *
     * @param array $testimonial Testimonial data
     * @param array $attributes Block attributes
     * @return string Ratings HTML
     */
    private function render_testimonial_ratings($testimonial, $attributes) {
        $satisfaction = intval($testimonial['satisfaction'] ?? 0);
        $recommendation = intval($testimonial['recommendation'] ?? 0);
        $performance = intval($testimonial['performance'] ?? 0);
        
        $show_satisfaction = $attributes['showSatisfactionRating'] ?? true;
        $show_recommendation = $attributes['showRecommendationRating'] ?? true;
        $show_performance = $attributes['showPerformanceRating'] ?? true;
        $show_values = $attributes['showRatingValues'] ?? true;
        
        $html = '<div class="ratings-grid">';
        
        if ($show_satisfaction && $satisfaction > 0) {
            $html .= '<div class="rating-item">';
            $html .= '<span class="rating-label">Satisfaction:</span>';
            $html .= '<span class="rating-stars">' . $this->render_stars($satisfaction / 20) . '</span>';
            if ($show_values) {
                $html .= '<span class="rating-value">(' . $satisfaction . '%)</span>';
            }
            $html .= '</div>';
        }
        
        if ($show_recommendation && $recommendation > 0) {
            $html .= '<div class="rating-item">';
            $html .= '<span class="rating-label">Recommendation:</span>';
            $html .= '<span class="rating-stars">' . $this->render_stars($recommendation / 20) . '</span>';
            if ($show_values) {
                $html .= '<span class="rating-value">(' . $recommendation . '%)</span>';
            }
            $html .= '</div>';
        }
        
        if ($show_performance && $performance > 0) {
            $html .= '<div class="rating-item">';
            $html .= '<span class="rating-label">Performance:</span>';
            $html .= '<span class="rating-stars">' . $this->render_stars($performance / 20) . '</span>';
            if ($show_values) {
                $html .= '<span class="rating-value">(' . $performance . '%)</span>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render star rating
     *
     * @param float $rating Rating value
     * @return string Stars HTML
     */
    private function render_stars($rating) {
        $html = '';
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $html .= '<span class="star star-full">★</span>';
        }

        // Half star
        if ($half_star) {
            $html .= '<span class="star star-half">☆</span>';
        }

        // Empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            $html .= '<span class="star star-empty">☆</span>';
        }

        return $html;
    }

    /**
     * Generate star rating display
     *
     * @param string|int $rating Percentage rating
     * @return string Star rating HTML
     */
    private function generate_stars($rating) {
        if (empty($rating)) {
            return '';
        }
        
        $star_rating = intval($rating) / 20; // Convert percentage to 5-star scale
        $full_stars = floor($star_rating);
        $half_star = ($star_rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
        
        $html = '';
        
        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $html .= '★';
        }
        
        // Half star
        if ($half_star) {
            $html .= '☆';
        }
        
        // Empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            $html .= '☆';
        }

        return $html;
    }

    /**
     * Build wrapper styles
     *
     * @param array $attributes Block attributes
     * @return array Array of CSS styles
     */
    private function build_wrapper_styles($attributes) {
        $styles = array();

        if (!empty($attributes['backgroundColor'])) {
            $styles[] = 'background-color: ' . esc_attr($attributes['backgroundColor']);
        }

        if (!empty($attributes['textColor'])) {
            $styles[] = 'color: ' . esc_attr($attributes['textColor']);
        }

        if (!empty($attributes['borderColor'])) {
            $styles[] = 'border-color: ' . esc_attr($attributes['borderColor']);
        }

        if (!empty($attributes['borderRadius'])) {
            $styles[] = 'border-radius: ' . esc_attr($attributes['borderRadius']);
        }

        return $styles;
    }

    /**
     * Build individual card styles
     *
     * @param array $attributes Block attributes
     * @return array Array of CSS styles
     */
    private function build_card_styles($attributes) {
        $styles = array();

        if (!empty($attributes['backgroundColor'])) {
            $styles[] = 'background-color: ' . esc_attr($attributes['backgroundColor']);
        }

        if (!empty($attributes['borderColor'])) {
            $styles[] = 'border-color: ' . esc_attr($attributes['borderColor']);
        }

        if (!empty($attributes['borderRadius'])) {
            $styles[] = 'border-radius: ' . esc_attr($attributes['borderRadius']);
        }

        return $styles;
    }

    /**
     * Build text styles
     *
     * @param array $attributes Block attributes
     * @return array Array of CSS styles
     */
    private function build_text_styles($attributes) {
        $styles = array();

        if (!empty($attributes['textColor'])) {
            $styles[] = 'color: ' . esc_attr($attributes['textColor']);
        }

        return $styles;
    }

    /**
     * Get pagination style
     *
     * @param array $attributes Block attributes
     * @return string CSS styles
     */
    private function get_pagination_style($attributes) {
        $styles = array();

        if (!empty($attributes['paginationBackgroundColor'])) {
            $styles[] = '--pagination-bg-color: ' . esc_attr($attributes['paginationBackgroundColor']);
        }

        if (!empty($attributes['paginationTextColor'])) {
            $styles[] = '--pagination-text-color: ' . esc_attr($attributes['paginationTextColor']);
        }

        if (!empty($attributes['paginationHoverBackgroundColor'])) {
            $styles[] = '--pagination-hover-bg-color: ' . esc_attr($attributes['paginationHoverBackgroundColor']);
        }

        if (!empty($attributes['paginationBorderRadius'])) {
            $styles[] = '--pagination-border-radius: ' . esc_attr($attributes['paginationBorderRadius']);
        }

        return implode('; ', $styles);
    }

    /**
     * Render error message
     *
     * @param string $message Error message
     * @return string Error HTML
     */
    private function render_error($message) {
        return '<div class="realsatisfied-error"><p>' . esc_html($message) . '</p></div>';
    }

    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'realsatisfied-agent-testimonials-editor',
            plugin_dir_url(__FILE__) . 'agent-testimonials-editor.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render'),
            '1.0.0'
        );
    }

    /**
     * Render testimonial template for Interactivity API
     *
     * @param array $attributes Block attributes
     * @return string Template HTML
     */
    private function render_testimonial_template($attributes) {
        // Build card styles
        $card_styles = $this->build_card_styles($attributes);
        $card_style_attr = !empty($card_styles) ? 'style="' . implode('; ', $card_styles) . '"' : '';
        
        // Build text styles
        $text_styles = $this->build_text_styles($attributes);
        $text_style_attr = !empty($text_styles) ? 'style="' . implode('; ', $text_styles) . '"' : '';
        
        ob_start();
        ?>
        <div class="testimonial-item testimonial-card" <?php echo $card_style_attr; ?>>
            <?php if ($attributes['showQuotationMarks'] ?? true): ?>
                <div class="testimonial-text" <?php echo $text_style_attr; ?> data-wp-text="context.item.quotedDescription"></div>
            <?php else: ?>
                <div class="testimonial-text" <?php echo $text_style_attr; ?> data-wp-text="context.item.description"></div>
            <?php endif; ?>
            
            <div class="testimonial-meta">
                <div class="testimonial-details">
                    <?php if ($attributes['showCustomerName'] ?? true): ?>
                        <div class="customer-name" data-wp-text="context.item.title"></div>
                    <?php endif; ?>
                    
                    <?php if ($attributes['showCustomerType'] ?? true): ?>
                        <div class="customer-type" data-wp-text="context.item.customer_type" style="color: <?php echo esc_attr($attributes['paginationBackgroundColor'] ?? '#007cba'); ?>;"></div>
                    <?php endif; ?>
                    
                    <?php if ($attributes['showDate'] ?? true): ?>
                        <div class="testimonial-date" data-wp-text="context.item.formattedDate"></div>
                    <?php endif; ?>
                    
                    <?php if ($attributes['showRatings'] ?? false): ?>
                        <div class="testimonial-ratings" data-wp-show="context.item.hasRatings">
                            <div class="ratings-grid">
                                <?php if ($attributes['showSatisfactionRating'] ?? true): ?>
                                    <div class="rating-item" data-wp-show="context.item.satisfaction">
                                        <span class="rating-label"><?php _e('Satisfaction:', 'realsatisfied-blocks'); ?></span>
                                        <span class="rating-stars" data-wp-text="context.item.satisfactionStars"></span>
                                        <?php if ($attributes['showRatingValues'] ?? true): ?>
                                            <span class="rating-value" data-wp-text="context.item.satisfactionValue"></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($attributes['showRecommendationRating'] ?? true): ?>
                                    <div class="rating-item" data-wp-show="context.item.recommendation">
                                        <span class="rating-label"><?php _e('Recommendation:', 'realsatisfied-blocks'); ?></span>
                                        <span class="rating-stars" data-wp-text="context.item.recommendationStars"></span>
                                        <?php if ($attributes['showRatingValues'] ?? true): ?>
                                            <span class="rating-value" data-wp-text="context.item.recommendationValue"></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($attributes['showPerformanceRating'] ?? true): ?>
                                    <div class="rating-item" data-wp-show="context.item.performance">
                                        <span class="rating-label"><?php _e('Performance:', 'realsatisfied-blocks'); ?></span>
                                        <span class="rating-stars" data-wp-text="context.item.performanceStars"></span>
                                        <?php if ($attributes['showRatingValues'] ?? true): ?>
                                            <span class="rating-value" data-wp-text="context.item.performanceValue"></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Clean RSS text content by decoding HTML entities and removing unwanted characters
     *
     * @param string $text The text to clean
     * @return string The cleaned text
     */
    private function clean_rss_text($text) {
        if (empty($text)) {
            return '';
        }
        
        // First decode HTML entities multiple times to handle nested encoding
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Handle any remaining common encoded characters
        $replacements = array(
            '&amp;' => '&',
            '&lt;' => '<',
            '&gt;' => '>',
            '&quot;' => '"',
            '&#39;' => "'",
            '&#8217;' => "'", // Right single quotation mark
            '&#8216;' => "'", // Left single quotation mark
            '&#8220;' => '"', // Left double quotation mark
            '&#8221;' => '"', // Right double quotation mark
            '&#8211;' => '–', // En dash
            '&#8212;' => '—', // Em dash
            '&#8230;' => '…', // Horizontal ellipsis
            // Additional common problematic sequences
            '&lsquo;' => "'",
            '&rsquo;' => "'",
            '&ldquo;' => '"',
            '&rdquo;' => '"',
            '&ndash;' => '–',
            '&mdash;' => '—',
            '&hellip;' => '…',
            // Fix common encoding issues
            'â€™' => "'", // Common UTF-8 encoding issue for apostrophe
            'â€œ' => '"', // Common UTF-8 encoding issue for left quote
            'â€' => '"',  // Common UTF-8 encoding issue for right quote
            'â€"' => '—', // Common UTF-8 encoding issue for em dash
            'â€"' => '–', // Common UTF-8 encoding issue for en dash
        );
        
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
        
        // Remove any remaining HTML tags
        $text = strip_tags($text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // Final pass to remove any remaining weird characters
        $text = preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '', $text);
        
        return $text;
    }

}

// Initialize the block
new RealSatisfied_Agent_Testimonials_Block();
