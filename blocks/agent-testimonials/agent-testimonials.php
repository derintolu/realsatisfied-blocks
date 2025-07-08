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
        add_action('init', array($this, 'register_block'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    /**
     * Register the block
     */
    public function register_block() {
        // Check if required classes exist
        if (!class_exists('RealSatisfied_Agent_RSS_Parser') || 
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
                    'default' => 'realsatisfied-agent-vanity'
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
            return $this->render_error(__('No vanity key specified for agent testimonials.', 'realsatisfied-blocks'));
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
        
        // Handle pagination or simple count limit
        $enable_pagination = $attributes['enablePagination'] ?? false;
        $total_testimonials = count($filtered_testimonials);
        
        if ($enable_pagination) {
            $items_per_page = intval($attributes['itemsPerPage'] ?? 6);
            $current_page = 1; // Default to first page for initial render
            $total_pages = ceil($total_testimonials / $items_per_page);
            $offset = ($current_page - 1) * $items_per_page;
            $paged_testimonials = array_slice($filtered_testimonials, $offset, $items_per_page);
        } else {
            // Simple count limit (original behavior)
            $testimonial_count = intval($attributes['testimonialCount'] ?? 6);
            if ($testimonial_count > 0) {
                $filtered_testimonials = array_slice($filtered_testimonials, 0, $testimonial_count);
            }
            $paged_testimonials = $filtered_testimonials;
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
        ?>
        <div <?php echo $wrapper_attributes; ?> <?php echo $style_attr; ?>>
            <div class="testimonials-container" 
                 data-testimonials="<?php echo esc_attr(json_encode($filtered_testimonials)); ?>"
                 data-items-per-page="<?php echo esc_attr($enable_pagination ? ($attributes['itemsPerPage'] ?? 6) : 0); ?>"
                 data-total-pages="<?php echo esc_attr($total_pages); ?>">
                <?php echo $this->render_testimonials($paged_testimonials, $attributes); ?>
            </div>
            
            <?php if ($enable_pagination && $total_pages > 1): ?>
                <div class="testimonials-pagination" style="<?php echo $this->get_pagination_style($attributes); ?>">
                    <button class="pagination-btn pagination-prev" disabled>&larr; <?php _e('Previous', 'realsatisfied-blocks'); ?></button>
                    <div class="pagination-numbers">
                        <span class="pagination-info">
                            <?php printf(__('Page <span class="current-page">1</span> of <span class="total-pages">%d</span>', 'realsatisfied-blocks'), $total_pages); ?>
                        </span>
                    </div>
                    <button class="pagination-btn pagination-next" <?php echo $total_pages <= 1 ? 'disabled' : ''; ?>><?php _e('Next', 'realsatisfied-blocks'); ?> &rarr;</button>
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
                $field_name = $attributes['customFieldName'] ?? 'realsatisfied-agent-vanity';
                $vanity_key = get_field($field_name, $post->ID);
                
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
        $excerpt_length = intval($attributes['excerptLength'] ?? 150);

        $html = '<div class="testimonial-item">';
        
        // Content
        $content = $testimonial['description'];
        if ($excerpt_length > 0 && strlen($content) > $excerpt_length) {
            $content = substr($content, 0, $excerpt_length) . '...';
        }
        $html .= '<div class="testimonial-content">' . esc_html($content) . '</div>';

        // Meta
        $html .= '<div class="testimonial-meta">';
        
        if ($show_customer_name && !empty($testimonial['title'])) {
            $html .= '<div class="customer-name">' . esc_html($testimonial['title']) . '</div>';
        }
        
        if ($show_customer_type && !empty($testimonial['customer_type'])) {
            $html .= '<div class="customer-type">' . esc_html($testimonial['customer_type']) . '</div>';
        }
        
        if ($show_date && !empty($testimonial['pubDate'])) {
            $formatted_date = date('F j, Y', strtotime($testimonial['pubDate']));
            $html .= '<div class="testimonial-date">' . esc_html($formatted_date) . '</div>';
        }
        
        if ($show_ratings && isset($testimonial['satisfaction'])) {
            $avg_rating = ($testimonial['satisfaction'] + $testimonial['recommendation'] + $testimonial['performance']) / 3;
            $html .= '<div class="testimonial-rating">';
            $html .= '<div class="stars">' . $this->render_stars($avg_rating) . '</div>';
            $html .= '<span class="rating-text">' . number_format($avg_rating, 1) . '/5</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // testimonial-meta
        $html .= '</div>'; // testimonial-item
        
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
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only enqueue on pages that have the block
        if (has_block($this->block_name)) {
            wp_enqueue_style(
                'realsatisfied-agent-testimonials',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/realsatisfied-blocks.css',
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'realsatisfied-agent-testimonials-frontend',
                plugin_dir_url(__FILE__) . 'agent-testimonials-frontend.js',
                array('jquery'),
                '1.0.0',
                true
            );
        }
    }
}

// Initialize the block
new RealSatisfied_Agent_Testimonials_Block();
