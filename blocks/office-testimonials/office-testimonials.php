<?php
/**
 * RealSatisfied Office Testimonials Block
 * 
 * Displays customer testimonials with multiple agents' reviews, layout options
 * (slider, grid, list), and filtering by agent/date/rating
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RealSatisfied Office Testimonials Block Class
 */
class RealSatisfied_Office_Testimonials_Block {
    
    /**
     * Block name
     *
     * @var string
     */
    private $block_name = 'realsatisfied-blocks/office-testimonials';

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
        add_action('init', array($this, 'register_block'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        // Frontend assets are handled by the main plugin file
    }

    /**
     * Register the block
     */
    public function register_block() {
        // Check if required classes exist
        if (!class_exists('RealSatisfied_Office_RSS_Parser') || 
            !class_exists('RealSatisfied_Custom_Fields')) {
            return;
        }

        register_block_type($this->block_name, array(
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
                    'default' => 'realsatisfied_feed'
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
                    'default' => false
                ),
                'itemsPerPage' => array(
                    'type' => 'number',
                    'default' => 6
                ),
                'showAgentPhoto' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showAgentName' => array(
                    'type' => 'boolean',
                    'default' => true
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
                'showCustomerType' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showQuotationMarks' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'excerptLength' => array(
                    'type' => 'number',
                    'default' => 150
                ),
                'filterByAgent' => array(
                    'type' => 'string',
                    'default' => ''
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
            return $this->render_error(__('No vanity key specified for office testimonials.', 'realsatisfied-blocks'));
        }

        // Get RSS parser instance
        $rss_parser = RealSatisfied_Office_RSS_Parser::get_instance();
        
        // Fetch office data
        $office_data = $rss_parser->fetch_office_data($vanity_key);
        
        if (is_wp_error($office_data)) {
            return $this->render_error($office_data->get_error_message());
        }

        // Get testimonials
        $testimonials = $office_data['testimonials'];
        
        if (empty($testimonials)) {
            return $this->render_error(__('No testimonials found for this office.', 'realsatisfied-blocks'));
        }

        // Filter and sort testimonials
        $filtered_testimonials = $this->filter_and_sort_testimonials($testimonials, $attributes);
        
        // Process testimonials for display (format dates, add quotation marks, etc.)
        $processed_testimonials = array();
        foreach ($filtered_testimonials as $testimonial) {
            $excerpt_length = intval($attributes['excerptLength'] ?? 150);
            $description = $testimonial['description'] ?? '';
            
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
            
            // Create agent photo URL - if no avatar, create initials-based placeholder
            $agent_photo = '';
            if (!empty($testimonial['avatar'])) {
                $agent_photo = $testimonial['avatar'];
            } else {
                // Generate initials-based placeholder
                $display_name = $testimonial['display_name'] ?? '';
                $initials = '';
                if (!empty($display_name)) {
                    $name_parts = explode(' ', trim($display_name));
                    $initials = '';
                    foreach ($name_parts as $part) {
                        if (!empty($part)) {
                            $initials .= strtoupper(substr($part, 0, 1));
                            if (strlen($initials) >= 2) break; // Limit to 2 initials
                        }
                    }
                }
                if (empty($initials)) {
                    $initials = '?';
                }
                
                // Create a colored background - use button color as primary, with variations for multiple agents
                $button_color = $attributes['paginationBackgroundColor'] ?? '#007cba';
                $colors = [
                    $button_color, // Primary button color
                    $this->adjust_color_brightness($button_color, -20), // Darker variant
                    $this->adjust_color_brightness($button_color, 20),  // Lighter variant
                    $this->adjust_color_hue($button_color, 30),         // Hue shifted variant
                    $this->adjust_color_hue($button_color, -30),        // Hue shifted variant (other direction)
                    '#e74c3c', '#2ecc71', '#9b59b6' // Fallback colors
                ];
                $color_index = crc32($display_name) % count($colors);
                $bg_color = $colors[$color_index];
                
                $agent_photo = "data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'%3E%3Ccircle cx='30' cy='30' r='30' fill='" . urlencode($bg_color) . "'/%3E%3Ctext x='30' y='37' text-anchor='middle' fill='white' font-size='18' font-weight='bold' font-family='Arial'%3E" . urlencode($initials) . "%3C/text%3E%3C/svg%3E";
            }

            // Create processed testimonial
            $processed_testimonial = array(
                'title' => $testimonial['title'] ?? '',
                'description' => $description,
                'quotedDescription' => '"' . $description . '"',
                'customer_type' => $testimonial['customer_type'] ?? '',
                'pubDate' => $testimonial['pubDate'] ?? '',
                'formattedDate' => $formatted_date,
                'satisfaction' => $testimonial['satisfaction'] ?? '',
                'recommendation' => $testimonial['recommendation'] ?? '',
                'performance' => $testimonial['performance'] ?? '',
                'display_name' => $testimonial['display_name'] ?? '',
                'agent_id' => $testimonial['display_name'] ?? '', // Use display_name as agent_id for filtering
                'agent_name' => $testimonial['display_name'] ?? '',
                'agent_photo' => $agent_photo, // Use computed agent photo
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
            'class' => 'realsatisfied-office-testimonials layout-' . esc_attr($attributes['layout'] ?? 'grid')
        ));

        // Build wrapper styles
        $wrapper_styles = $this->build_wrapper_styles($attributes);
        
        // Add button color as CSS custom property for agent photo borders
        $button_color = $attributes['paginationBackgroundColor'] ?? '#007cba';
        $wrapper_styles[] = '--rs-button-color: ' . esc_attr($button_color);
        
        $style_attr = !empty($wrapper_styles) ? 'style="' . implode('; ', $wrapper_styles) . '"' : '';

        // Start building output
        ob_start();
        ?>
        <div <?php echo $wrapper_attributes; ?> <?php echo $style_attr; ?>
             data-wp-interactive="realsatisfied-office-testimonials"
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
                 'expandedTestimonials' => array(),
                 'activeFilter' => null,
                 'sortBy' => $attributes['sortBy'] ?? 'date',
                 'buttonColor' => $attributes['paginationBackgroundColor'] ?? '#007cba'
             )); ?>
             data-wp-init="callbacks.initTestimonials">
            
            <?php 
            // Show filtering and sorting controls if there are multiple testimonials
            $unique_agents = array();
            foreach ($processed_testimonials as $testimonial) {
                if (!empty($testimonial['display_name']) && !in_array($testimonial['display_name'], $unique_agents)) {
                    $unique_agents[] = $testimonial['display_name'];
                }
            }
            
            if (count($processed_testimonials) > 1 && count($unique_agents) > 1): 
            ?>
                <div class="testimonials-controls">
                    <div class="testimonials-filters">
                        <select data-wp-on--change="actions.filterByAgent" class="agent-filter">
                            <option value="all"><?php _e('All Agents', 'realsatisfied-blocks'); ?></option>
                            <?php foreach ($unique_agents as $agent): ?>
                                <option value="<?php echo esc_attr($agent); ?>"><?php echo esc_html($agent); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="testimonials-sorting">
                        <select data-wp-on--change="actions.sortTestimonials" class="sort-control">
                            <option value="date_desc"><?php _e('Newest First', 'realsatisfied-blocks'); ?></option>
                            <option value="date"><?php _e('Oldest First', 'realsatisfied-blocks'); ?></option>
                            <option value="rating"><?php _e('Highest Rated', 'realsatisfied-blocks'); ?></option>
                            <option value="agent"><?php _e('By Agent Name', 'realsatisfied-blocks'); ?></option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
            
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
            
            <?php if ($enable_pagination): ?>
                <div class="testimonials-pagination" style="<?php echo $this->get_pagination_style($attributes); ?>" data-wp-show="state.totalPages > 1">
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
     * Filter and sort testimonials
     *
     * @param array $testimonials Array of testimonials
     * @param array $attributes Block attributes
     * @return array Filtered and sorted testimonials
     */
    private function filter_and_sort_testimonials($testimonials, $attributes) {
        $filtered = $testimonials;

        // Filter by agent
        if (!empty($attributes['filterByAgent'])) {
            $filtered = array_filter($filtered, function($testimonial) use ($attributes) {
                return stripos($testimonial['display_name'], $attributes['filterByAgent']) !== false;
            });
        }

        // Sort testimonials
        $sort_by = $attributes['sortBy'] ?? 'date';
        $sort_order = $attributes['sortOrder'] ?? 'desc';

        usort($filtered, function($a, $b) use ($sort_by, $sort_order) {
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
                case 'agent':
                    $result = strcmp($a['display_name'], $b['display_name']);
                    break;
            }
            
            return $sort_order === 'asc' ? $result : -$result;
        });

        return $filtered;
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
     * Render grid layout
     *
     * @param array $testimonials Array of testimonials
     * @param array $attributes Block attributes
     * @param int $columns Number of columns
     * @return string Grid HTML
     */
    private function render_grid_layout($testimonials, $attributes, $columns) {
        $html = '<div class="testimonials-grid" style="display: grid; grid-template-columns: repeat(' . $columns . ', 1fr); gap: 1.5rem;">';
        
        foreach ($testimonials as $testimonial) {
            $html .= '<div class="testimonial-card">';
            $html .= $this->render_testimonial_card($testimonial, $attributes);
            $html .= '</div>';
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
            $html .= '<div class="testimonial-item">';
            $html .= $this->render_testimonial_card($testimonial, $attributes);
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
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
        $html = '<div class="testimonials-slider">';
        $html .= '<div class="slider-container">';
        
        foreach ($testimonials as $index => $testimonial) {
            $active_class = $index === 0 ? ' active' : '';
            $html .= '<div class="slider-slide' . $active_class . '">';
            $html .= $this->render_testimonial_card($testimonial, $attributes);
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Add slider controls
        if (count($testimonials) > 1) {
            $html .= '<div class="slider-controls">';
            $html .= '<button class="slider-prev" onclick="previousSlide()">&lt;</button>';
            $html .= '<button class="slider-next" onclick="nextSlide()">&gt;</button>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render individual testimonial card
     *
     * @param array $testimonial Testimonial data
     * @param array $attributes Block attributes
     * @return string Testimonial HTML
     */
    private function render_testimonial_card($testimonial, $attributes) {
        $html = '<div class="testimonial-content">';
        
        // Testimonial text
        $description = $testimonial['description'];
        $excerpt_length = intval($attributes['excerptLength'] ?? 150);
        if ($excerpt_length > 0 && strlen($description) > $excerpt_length) {
            $description = substr($description, 0, $excerpt_length) . '...';
        }
        
        $html .= '<div class="testimonial-text">"' . esc_html($description) . '"</div>';
        
        // Customer info
        $html .= '<div class="testimonial-meta">';
        
        // Agent info
        if ($attributes['showAgentPhoto'] && !empty($testimonial['avatar'])) {
            $html .= '<div class="agent-photo">';
            $html .= '<img src="' . esc_url($testimonial['avatar']) . '" alt="' . esc_attr($testimonial['display_name']) . '" />';
            $html .= '</div>';
        }
        
        $html .= '<div class="testimonial-details">';
        
        // Customer name
        if ($attributes['showCustomerName']) {
            $html .= '<div class="customer-name">' . esc_html($testimonial['title']) . '</div>';
        }
        
        // Agent name
        if ($attributes['showAgentName']) {
            $html .= '<div class="agent-name">Agent: ' . esc_html($testimonial['display_name']) . '</div>';
        }
        
        // Customer type
        if ($attributes['showCustomerType'] && !empty($testimonial['customer_type'])) {
            $html .= '<div class="customer-type">' . esc_html($testimonial['customer_type']) . '</div>';
        }
        
        // Date
        if ($attributes['showDate']) {
            $date = date('F j, Y', strtotime($testimonial['pubDate']));
            $html .= '<div class="testimonial-date">' . esc_html($date) . '</div>';
        }
        
        // Ratings
        if ($attributes['showRatings']) {
            $html .= '<div class="testimonial-ratings">';
            $html .= $this->render_testimonial_ratings($testimonial);
            $html .= '</div>';
        }
        
        $html .= '</div>'; // testimonial-details
        $html .= '</div>'; // testimonial-meta
        $html .= '</div>'; // testimonial-content
        
        return $html;
    }

    /**
     * Render testimonial ratings
     *
     * @param array $testimonial Testimonial data
     * @return string Ratings HTML
     */
    private function render_testimonial_ratings($testimonial) {
        $satisfaction = intval($testimonial['satisfaction']);
        $recommendation = intval($testimonial['recommendation']);
        $performance = intval($testimonial['performance']);
        
        $html = '<div class="ratings-grid">';
        $html .= '<div class="rating-item">';
        $html .= '<span class="rating-label">Satisfaction:</span>';
        $html .= '<span class="rating-stars">' . $this->render_stars($satisfaction / 20) . '</span>';
        $html .= '</div>';
        
        $html .= '<div class="rating-item">';
        $html .= '<span class="rating-label">Recommendation:</span>';
        $html .= '<span class="rating-stars">' . $this->render_stars($recommendation / 20) . '</span>';
        $html .= '</div>';
        
        $html .= '<div class="rating-item">';
        $html .= '<span class="rating-label">Performance:</span>';
        $html .= '<span class="rating-stars">' . $this->render_stars($performance / 20) . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render stars for rating
     *
     * @param float $rating Rating value (0.0-5.0)
     * @return string Stars HTML
     */
    private function render_stars($rating) {
        $html = '';
        
        for ($i = 1; $i <= 5; $i++) {
            $filled = $rating >= $i;
            $half_filled = !$filled && $rating >= ($i - 0.5);
            
            if ($filled) {
                $html .= '<span class="star filled">★</span>';
            } elseif ($half_filled) {
                $html .= '<span class="star half">★</span>';
            } else {
                $html .= '<span class="star empty">☆</span>';
            }
        }
        
        return $html;
    }

    /**
     * Build wrapper styles
     *
     * @param array $attributes Block attributes
     * @return array CSS styles
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
     * Get pagination styles
     *
     * @param array $attributes Block attributes
     * @return string CSS styles for pagination
     */
    private function get_pagination_style($attributes) {
        $pagination_bg = $attributes['paginationBackgroundColor'] ?? '#007cba';
        $pagination_text = $attributes['paginationTextColor'] ?? '#ffffff';
        $pagination_hover_bg = $attributes['paginationHoverBackgroundColor'] ?? '#005a87';
        $pagination_border_radius = $attributes['paginationBorderRadius'] ?? '5px';
        
        // Generate CSS custom properties for pagination
        $styles = array(
            '--pagination-bg-color: ' . esc_attr($pagination_bg),
            '--pagination-text-color: ' . esc_attr($pagination_text),
            '--pagination-hover-bg-color: ' . esc_attr($pagination_hover_bg),
            '--pagination-border-radius: ' . esc_attr($pagination_border_radius)
        );
        
        return implode('; ', $styles);
    }

    /**
     * Get vanity key from attributes
     *
     * @param array $attributes Block attributes
     * @return string|false Vanity key or false if not found
     */
    private function get_vanity_key($attributes) {
        if ($attributes['useCustomField'] && !empty($attributes['customFieldName'])) {
            $custom_fields = RealSatisfied_Custom_Fields::get_instance();
            $vanity_key = $custom_fields->get_vanity_key();
            if ($vanity_key) {
                return $vanity_key;
            }
        } elseif (!empty($attributes['manualVanityKey'])) {
            return $attributes['manualVanityKey'];
        }
        
        // Default fallback for testing
        return 'CENTURY21-Masters-11';
    }

    /**
     * Render error message
     *
     * @param string $message Error message
     * @return string Error HTML
     */
    private function render_error($message) {
        return '<div class="realsatisfied-office-testimonials-error notice notice-warning">' .
               '<p>' . esc_html($message) . '</p>' .
               '</div>';
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
                <?php if ($attributes['showAgentPhoto'] ?? true || $attributes['showAgentName'] ?? true): ?>
                    <div class="agent-info">
                        <?php if ($attributes['showAgentPhoto'] ?? true): ?>
                            <div class="agent-photo">
                                <img data-wp-bind--src="context.item.agent_photo" 
                                     data-wp-bind--alt="context.item.agent_name" />
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($attributes['showAgentName'] ?? true): ?>
                            <div class="agent-name" data-wp-text="context.item.agent_name"></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
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
                                <div class="rating-item" data-wp-show="context.item.satisfaction">
                                    <span class="rating-label"><?php _e('Satisfaction:', 'realsatisfied-blocks'); ?></span>
                                    <span class="rating-stars" data-wp-text="context.item.satisfactionStars"></span>
                                    <span class="rating-value" data-wp-text="context.item.satisfactionValue"></span>
                                </div>
                                
                                <div class="rating-item" data-wp-show="context.item.recommendation">
                                    <span class="rating-label"><?php _e('Recommendation:', 'realsatisfied-blocks'); ?></span>
                                    <span class="rating-stars" data-wp-text="context.item.recommendationStars"></span>
                                    <span class="rating-value" data-wp-text="context.item.recommendationValue"></span>
                                </div>
                                
                                <div class="rating-item" data-wp-show="context.item.performance">
                                    <span class="rating-label"><?php _e('Performance:', 'realsatisfied-blocks'); ?></span>
                                    <span class="rating-stars" data-wp-text="context.item.performanceStars"></span>
                                    <span class="rating-value" data-wp-text="context.item.performanceValue"></span>
                                </div>
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
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'realsatisfied-office-testimonials-editor',
            RSOB_PLUGIN_URL . 'blocks/office-testimonials/office-testimonials-editor.js',
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
            RSOB_PLUGIN_VERSION,
            true
        );

        wp_enqueue_style(
            'realsatisfied-office-testimonials-editor',
            RSOB_PLUGIN_URL . 'assets/realsatisfied-blocks.css',
            array(),
            RSOB_PLUGIN_VERSION
        );
    }
    
    /**
     * Adjust color brightness
     * 
     * @param string $hex_color Hex color (e.g., #007cba)
     * @param int $percent Percentage to adjust (-100 to 100)
     * @return string Adjusted hex color
     */
    private function adjust_color_brightness($hex_color, $percent) {
        // Remove # if present
        $hex_color = ltrim($hex_color, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex_color, 0, 2));
        $g = hexdec(substr($hex_color, 2, 2));
        $b = hexdec(substr($hex_color, 4, 2));
        
        // Adjust brightness
        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));
        
        // Convert back to hex
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Adjust color hue
     * 
     * @param string $hex_color Hex color (e.g., #007cba)
     * @param int $degrees Degrees to shift hue (-360 to 360)
     * @return string Adjusted hex color
     */
    private function adjust_color_hue($hex_color, $degrees) {
        // Remove # if present
        $hex_color = ltrim($hex_color, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex_color, 0, 2)) / 255;
        $g = hexdec(substr($hex_color, 2, 2)) / 255;
        $b = hexdec(substr($hex_color, 4, 2)) / 255;
        
        // Convert RGB to HSL
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        
        if ($max == $min) {
            $h = $s = 0; // achromatic
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            
            switch ($max) {
                case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
                case $g: $h = ($b - $r) / $d + 2; break;
                case $b: $h = ($r - $g) / $d + 4; break;
            }
            $h /= 6;
        }
        
        // Adjust hue
        $h += $degrees / 360;
        if ($h > 1) $h -= 1;
        if ($h < 0) $h += 1;
        
        // Convert HSL back to RGB
        if ($s == 0) {
            $r = $g = $b = $l; // achromatic
        } else {
            $hue_to_rgb = function($p, $q, $t) {
                if ($t < 0) $t += 1;
                if ($t > 1) $t -= 1;
                if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
                if ($t < 1/2) return $q;
                if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
                return $p;
            };
            
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $hue_to_rgb($p, $q, $h + 1/3);
            $g = $hue_to_rgb($p, $q, $h);
            $b = $hue_to_rgb($p, $q, $h - 1/3);
        }
        
        // Convert back to hex
        return sprintf('#%02x%02x%02x', round($r * 255), round($g * 255), round($b * 255));
    }
}

// Initialize the block
new RealSatisfied_Office_Testimonials_Block();