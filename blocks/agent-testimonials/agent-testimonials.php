<?php
/**
 * RealSatisfied Agent Testimonials Block
 * 
 * Displays customer testimonials for a specific agent with layout options
 * (slider, grid, list), and filtering capabilities
 */

// Ensure this file is executed within the WordPress context
if (!defined('ABSPATH')) {
    exit;
}

// Ensure WordPress functions are available
if (!function_exists('get_post_meta')) {
    require_once(ABSPATH . 'wp-includes/post.php');
}
if (!function_exists('is_wp_error')) {
    require_once(ABSPATH . 'wp-includes/class-wp-error.php');
}
if (!function_exists('esc_html')) {
    require_once(ABSPATH . 'wp-includes/formatting.php');
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
                    'default' => false
                ),
                'customFieldName' => array(
                    'type' => 'string',
                    'default' => 'realsatisfied_agent_key'
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
                'showAgentInfo' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showAgentPhoto' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showAgentName' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showOffice' => array(
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
            return $this->render_error(__('No agent vanity key specified.', 'realsatisfied-blocks'));
        }

        // Get agent RSS parser instance
        $agent_parser = RealSatisfied_Agent_RSS_Parser::get_instance();
        
        // Fetch agent data
        $agent_data = $agent_parser->fetch_agent_data($vanity_key);
        
        if (is_wp_error($agent_data)) {
            return $this->render_error($agent_data->get_error_message());
        }

        // Retrieve the related office post ID using the relationship field
        $office_post_id = get_post_meta($agent_post_id, 'related_office', true);

        // Check if an office is related
        if ($office_post_id) {
            // Fetch the office vanity key from the office post's custom fields
            $office_vanity_key = get_post_meta($office_post_id, 'office_vanity_key', true);

            // Fetch office data using the office vanity key
            if ($office_vanity_key) {
                $office_parser = RealSatisfied_Office_RSS_Parser::get_instance();
                $office_data = $office_parser->fetch_office_data($office_vanity_key);

                // Render agent and office data
                echo '<div class="agent-testimonials">';
                echo '<h3>' . esc_html($agent_data['channel']['display_name']) . '</h3>';
                echo '<p>Office: ' . esc_html($office_data['channel']['office_name']) . '</p>';
                // Display agent testimonials and office data...
                echo '</div>';
            } else {
                echo '<p>No office data available for this agent.</p>';
            }
        } else {
            echo '<p>No related office found for this agent.</p>';
        }

        // Extract channel and testimonials
        $channel = $agent_data['channel'];
        $testimonials = $agent_data['testimonials'];
        
        // Sort testimonials
        $testimonials = $this->sort_testimonials($testimonials, $attributes);
        
        // Build wrapper styles
        $wrapper_styles = $this->build_wrapper_styles($attributes);
        
        // Get block wrapper attributes
        $wrapper_attributes = get_block_wrapper_attributes(array(
            'class' => 'realsatisfied-agent-testimonials',
            'style' => implode('; ', $wrapper_styles)
        ));

        // Start output
        ob_start();
        ?>
        <div <?php echo $wrapper_attributes; ?>>
            
            <?php if ($attributes['showAgentInfo'] ?? true): ?>
            <!-- Agent Information Header -->
            <div class="agent-info-header">
                <?php if ($attributes['showAgentPhoto'] ?? true && !empty($channel['avatar'])): ?>
                    <div class="agent-photo">
                        <img src="<?php echo esc_url($channel['avatar']); ?>" alt="<?php echo esc_attr($channel['display_name']); ?>" />
                    </div>
                <?php endif; ?>
                
                <div class="agent-details">
                    <?php if ($attributes['showAgentName'] ?? true): ?>
                        <h3 class="agent-name"><?php echo esc_html($channel['display_name']); ?></h3>
                    <?php endif; ?>
                    
                    <?php if ($attributes['showOffice'] ?? true && !empty($channel['office'])): ?>
                        <p class="agent-office"><?php echo esc_html($channel['office']); ?></p>
                    <?php endif; ?>
                    
                    <p class="agent-review-count">
                        <?php echo esc_html($channel['response_count']); ?> <?php echo _n('review', 'reviews', $channel['response_count'], 'realsatisfied-blocks'); ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Testimonials -->
            <?php echo $this->render_testimonials($testimonials, $attributes); ?>
            
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Sort testimonials based on attributes
     *
     * @param array $testimonials Array of testimonials
     * @param array $attributes Block attributes
     * @return array Sorted testimonials
     */
    private function sort_testimonials($testimonials, $attributes) {
        $sort_by = $attributes['sortBy'] ?? 'date';
        $sort_order = $attributes['sortOrder'] ?? 'desc';
        
        usort($testimonials, function($a, $b) use ($sort_by, $sort_order) {
            switch ($sort_by) {
                case 'date':
                    $comparison = strtotime($a['pubDate']) - strtotime($b['pubDate']);
                    break;
                case 'rating':
                    $a_rating = ($a['satisfaction'] + $a['recommendation'] + $a['performance']) / 3;
                    $b_rating = ($b['satisfaction'] + $b['recommendation'] + $b['performance']) / 3;
                    $comparison = $a_rating - $b_rating;
                    break;
                case 'customer_type':
                    $comparison = strcmp($a['customer_type'], $b['customer_type']);
                    break;
                default:
                    $comparison = 0;
            }
            
            return $sort_order === 'desc' ? -$comparison : $comparison;
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
        $count = $attributes['testimonialCount'] ?? 6;
        
        // Limit testimonials if not using pagination
        if (!($attributes['enablePagination'] ?? false) && $count > 0) {
            $testimonials = array_slice($testimonials, 0, $count);
        }
        
        switch ($layout) {
            case 'list':
                return $this->render_list_layout($testimonials, $attributes);
            case 'slider':
                return $this->render_slider_layout($testimonials, $attributes);
            case 'grid':
            default:
                return $this->render_grid_layout($testimonials, $attributes);
        }
    }

    /**
     * Render grid layout
     *
     * @param array $testimonials Array of testimonials
     * @param array $attributes Block attributes
     * @return string Grid HTML
     */
    private function render_grid_layout($testimonials, $attributes) {
        $columns = $attributes['columns'] ?? 2;
        
        $html = '<div class="testimonials-grid" style="grid-template-columns: repeat(' . $columns . ', 1fr);">';
        
        foreach ($testimonials as $testimonial) {
            $html .= $this->render_testimonial_card($testimonial, $attributes);
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
            $html .= $this->render_testimonial_card($testimonial, $attributes);
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
        
        if (count($testimonials) > 1) {
            $html .= '<div class="slider-controls">';
            $html .= '<button class="slider-prev" type="button">❮</button>';
            $html .= '<button class="slider-next" type="button">❯</button>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render testimonial card
     *
     * @param array $testimonial Testimonial data
     * @param array $attributes Block attributes
     * @return string Testimonial card HTML
     */
    private function render_testimonial_card($testimonial, $attributes) {
        $excerpt_length = $attributes['excerptLength'] ?? 150;
        $description = $testimonial['description'];
        
        if ($excerpt_length > 0 && strlen($description) > $excerpt_length) {
            $description = substr($description, 0, $excerpt_length) . '...';
        }
        
        $html = '<div class="testimonial-card">';
        
        // Testimonial text
        $html .= '<div class="testimonial-text">' . esc_html($description) . '</div>';
        
        // Testimonial meta
        $html .= '<div class="testimonial-meta">';
        
        // Customer info
        $html .= '<div class="testimonial-details">';
        
        if ($attributes['showCustomerName'] ?? true) {
            $html .= '<div class="customer-name">' . esc_html($testimonial['title']) . '</div>';
        }
        
        if ($attributes['showCustomerType'] ?? true && !empty($testimonial['customer_type'])) {
            $html .= '<div class="customer-type">' . esc_html($testimonial['customer_type']) . '</div>';
        }
        
        if ($attributes['showDate'] ?? true) {
            $html .= '<div class="testimonial-date">' . esc_html(date('F j, Y', strtotime($testimonial['pubDate']))) . '</div>';
        }
        
        if ($attributes['showRatings'] ?? false) {
            $html .= $this->render_testimonial_ratings($testimonial);
        }
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render testimonial ratings
     *
     * @param array $testimonial Testimonial data
     * @return string Ratings HTML
     */
    private function render_testimonial_ratings($testimonial) {
        $html = '<div class="testimonial-ratings">';
        
        if (!empty($testimonial['satisfaction'])) {
            $html .= '<div class="rating-item">';
            $html .= '<span class="rating-label">Satisfaction:</span>';
            $html .= '<span class="rating-stars">' . $this->render_stars($testimonial['satisfaction'] / 20) . '</span>';
            $html .= '</div>';
        }
        
        if (!empty($testimonial['recommendation'])) {
            $html .= '<div class="rating-item">';
            $html .= '<span class="rating-label">Recommendation:</span>';
            $html .= '<span class="rating-stars">' . $this->render_stars($testimonial['recommendation'] / 20) . '</span>';
            $html .= '</div>';
        }
        
        if (!empty($testimonial['performance'])) {
            $html .= '<div class="rating-item">';
            $html .= '<span class="rating-label">Performance:</span>';
            $html .= '<span class="rating-stars">' . $this->render_stars($testimonial['performance'] / 20) . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render stars for rating
     *
     * @param float $rating Rating value (0-5)
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
     * @return array Style strings
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
            $vanity_key = $custom_fields->get_custom_field_value($attributes['customFieldName']);
            if ($vanity_key) {
                return trim($vanity_key);
            }
        } elseif (!empty($attributes['manualVanityKey'])) {
            return trim($attributes['manualVanityKey']);
        }
        
        return false;
    }

    /**
     * Render error message
     *
     * @param string $message Error message
     * @return string Error HTML
     */
    private function render_error($message) {
        return '<div class="realsatisfied-agent-testimonials-error">' . esc_html($message) . '</div>';
    }

    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'realsatisfied-agent-testimonials-editor',
            RSOB_PLUGIN_URL . 'blocks/agent-testimonials/agent-testimonials-editor.js',
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components'),
            filemtime(RSOB_PLUGIN_PATH . 'blocks/agent-testimonials/agent-testimonials-editor.js')
        );
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Frontend assets handled by main plugin
    }
} 