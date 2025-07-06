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
        
        // Limit testimonials count
        $testimonial_count = intval($attributes['testimonialCount'] ?? 6);
        if ($testimonial_count > 0) {
            $filtered_testimonials = array_slice($filtered_testimonials, 0, $testimonial_count);
        }

        // Get block wrapper attributes
        $wrapper_attributes = get_block_wrapper_attributes(array(
            'class' => 'realsatisfied-office-testimonials layout-' . esc_attr($attributes['layout'] ?? 'grid')
        ));

        // Build wrapper styles
        $wrapper_styles = $this->build_wrapper_styles($attributes);
        $style_attr = !empty($wrapper_styles) ? 'style="' . implode('; ', $wrapper_styles) . '"' : '';

        // Start building output
        ob_start();
        ?>
        <div <?php echo $wrapper_attributes; ?> <?php echo $style_attr; ?>>
            <?php echo $this->render_testimonials($filtered_testimonials, $attributes); ?>
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
}

// Initialize the block
new RealSatisfied_Office_Testimonials_Block(); 