<?php
/**
 * RealSatisfied Agent Testimonials Block
 * 
 * Clean implementation using agent vanity key from ACF field "realsatisfied-agent-vanity"
 * Fetches testimonials directly from agent RSS feed
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
        // Check if required class exists
        if (!class_exists('RealSatisfied_Agent_RSS_Parser')) {
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
            ),
            'attributes' => array(
                'agentId' => array(
                    'type' => 'number',
                    'default' => null
                ),
                'contextAware' => array(
                    'type' => 'boolean',
                    'default' => true
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
        // Get agent ID and vanity key
        $agent_id = $this->get_agent_id($attributes);
        
        if (!$agent_id) {
            return $this->render_error(__('No agent specified or found in current context.', 'realsatisfied-blocks'));
        }

        // Get agent vanity key from ACF field
        $agent_vanity_key = get_post_meta($agent_id, 'realsatisfied-agent-vanity', true);
        
        if (empty($agent_vanity_key)) {
            return $this->render_error(__('No agent vanity key found. Please set the "realsatisfied-agent-vanity" field for this agent.', 'realsatisfied-blocks'));
        }

        // Get agent RSS parser instance
        $agent_parser = RealSatisfied_Agent_RSS_Parser::get_instance();
        
        // Fetch agent data
        $agent_data = $agent_parser->fetch_agent_data($agent_vanity_key);
        
        if (is_wp_error($agent_data)) {
            return $this->render_error($agent_data->get_error_message());
        }

        if (empty($agent_data['testimonials'])) {
            return $this->render_error(__('No testimonials found for this agent.', 'realsatisfied-blocks'));
        }

        // Process testimonials
        $testimonials = $this->process_testimonials($agent_data['testimonials'], $attributes);
        
        // Generate HTML output
        return $this->generate_html_output($testimonials, $attributes, $agent_data['channel']);
    }

    /**
     * Get agent ID from attributes or current context
     *
     * @param array $attributes Block attributes
     * @return int|null Agent ID
     */
    private function get_agent_id($attributes) {
        // First check if agent ID is explicitly set in block attributes
        if (!empty($attributes['agentId'])) {
            return intval($attributes['agentId']);
        }

        // If context-aware is disabled, return null
        if (isset($attributes['contextAware']) && !$attributes['contextAware']) {
            return null;
        }

        // Try to get agent ID from current context
        global $post;
        
        // Check if we're on a single agent page
        if (is_singular('agent') && $post) {
            return $post->ID;
        }

        // Check if we're in a query loop context
        if (in_the_loop() || is_main_query()) {
            $current_post = get_post();
            if ($current_post && $current_post->post_type === 'agent') {
                return $current_post->ID;
            }
        }

        return null;
    }

    /**
     * Process testimonials based on attributes
     *
     * @param array $testimonials Raw testimonials data
     * @param array $attributes Block attributes
     * @return array Processed testimonials
     */
    private function process_testimonials($testimonials, $attributes) {
        // Sort testimonials
        $sort_by = isset($attributes['sortBy']) ? $attributes['sortBy'] : 'date';
        $sort_order = isset($attributes['sortOrder']) ? $attributes['sortOrder'] : 'desc';
        
        if ($sort_by === 'date') {
            usort($testimonials, function($a, $b) use ($sort_order) {
                $date_a = strtotime($a['pubDate']);
                $date_b = strtotime($b['pubDate']);
                return $sort_order === 'desc' ? $date_b - $date_a : $date_a - $date_b;
            });
        }

        // Limit testimonials count
        $count = isset($attributes['testimonialCount']) ? intval($attributes['testimonialCount']) : 6;
        if ($count > 0) {
            $testimonials = array_slice($testimonials, 0, $count);
        }

        // Truncate excerpts
        $excerpt_length = isset($attributes['excerptLength']) ? intval($attributes['excerptLength']) : 150;
        foreach ($testimonials as &$testimonial) {
            if (strlen($testimonial['description']) > $excerpt_length) {
                $testimonial['description'] = substr($testimonial['description'], 0, $excerpt_length) . '...';
            }
        }

        return $testimonials;
    }

    /**
     * Generate HTML output
     *
     * @param array $testimonials Processed testimonials
     * @param array $attributes Block attributes
     * @param array $channel_data Agent channel data
     * @return string HTML output
     */
    private function generate_html_output($testimonials, $attributes, $channel_data) {
        $layout = isset($attributes['layout']) ? $attributes['layout'] : 'grid';
        $columns = isset($attributes['columns']) ? intval($attributes['columns']) : 2;
        $show_date = isset($attributes['showDate']) ? $attributes['showDate'] : true;
        $show_ratings = isset($attributes['showRatings']) ? $attributes['showRatings'] : false;
        $show_customer_type = isset($attributes['showCustomerType']) ? $attributes['showCustomerType'] : true;

        $output = '<div class="realsatisfied-agent-testimonials layout-' . esc_attr($layout) . '">';
        
        if ($layout === 'grid') {
            $output .= '<div class="testimonials-grid columns-' . esc_attr($columns) . '">';
        } else {
            $output .= '<div class="testimonials-list">';
        }

        foreach ($testimonials as $testimonial) {
            $output .= '<div class="testimonial-item">';
            
            // Customer name/title
            if (!empty($testimonial['title'])) {
                $output .= '<h4 class="customer-name">' . esc_html($testimonial['title']) . '</h4>';
            }
            
            // Customer type
            if ($show_customer_type && !empty($testimonial['customer_type'])) {
                $output .= '<p class="customer-type">' . esc_html($testimonial['customer_type']) . '</p>';
            }
            
            // Testimonial content
            if (!empty($testimonial['description'])) {
                $output .= '<div class="testimonial-content">' . wp_kses_post($testimonial['description']) . '</div>';
            }
            
            // Ratings
            if ($show_ratings) {
                $output .= '<div class="testimonial-ratings">';
                if (!empty($testimonial['satisfaction'])) {
                    $satisfaction_stars = $this->calculate_stars($testimonial['satisfaction']);
                    $output .= '<div class="rating satisfaction">Satisfaction: ' . $this->render_stars($satisfaction_stars) . '</div>';
                }
                if (!empty($testimonial['recommendation'])) {
                    $recommendation_stars = $this->calculate_stars($testimonial['recommendation']);
                    $output .= '<div class="rating recommendation">Recommendation: ' . $this->render_stars($recommendation_stars) . '</div>';
                }
                if (!empty($testimonial['performance'])) {
                    $performance_stars = $this->calculate_stars($testimonial['performance']);
                    $output .= '<div class="rating performance">Performance: ' . $this->render_stars($performance_stars) . '</div>';
                }
                $output .= '</div>';
            }
            
            // Date
            if ($show_date && !empty($testimonial['pubDate'])) {
                $date = date('F j, Y', strtotime($testimonial['pubDate']));
                $output .= '<p class="testimonial-date">' . esc_html($date) . '</p>';
            }
            
            $output .= '</div>';
        }

        $output .= '</div></div>';

        return $output;
    }

    /**
     * Calculate star rating from percentage
     *
     * @param int $percentage Rating percentage (0-100)
     * @return float Star rating (0-5)
     */
    private function calculate_stars($percentage) {
        return round(($percentage / 100) * 5, 1);
    }

    /**
     * Render star rating
     *
     * @param float $rating Star rating (0-5)
     * @return string Star HTML
     */
    private function render_stars($rating) {
        $output = '<span class="stars">';
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $full_stars) {
                $output .= '<span class="star full">★</span>';
            } elseif ($i == $full_stars + 1 && $half_star) {
                $output .= '<span class="star half">☆</span>';
            } else {
                $output .= '<span class="star empty">☆</span>';
            }
        }
        
        $output .= '</span>';
        return $output;
    }

    /**
     * Render error message
     *
     * @param string $message Error message
     * @return string Error HTML
     */
    private function render_error($message) {
        return '<div class="realsatisfied-agent-testimonials-error notice notice-warning">' .
               '<p>' . esc_html($message) . '</p>' .
               '</div>';
    }

    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'realsatisfied-agent-testimonials-editor',
            plugin_dir_url(__FILE__) . 'agent-testimonials-editor.js',
            array('wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-data'),
            '1.0.0'
        );

        wp_localize_script('realsatisfied-agent-testimonials-editor', 'realSatisfiedAgentTestimonials', array(
            'pluginUrl' => plugin_dir_url(dirname(dirname(__FILE__))),
            'nonce' => wp_create_nonce('realsatisfied_nonce')
        ));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Basic CSS for the block
        wp_add_inline_style(
            'wp-block-library',
            '
            .realsatisfied-agent-testimonials {
                margin: 20px 0;
            }
            .realsatisfied-agent-testimonials .testimonials-grid {
                display: grid;
                gap: 20px;
            }
            .realsatisfied-agent-testimonials .testimonials-grid.columns-1 {
                grid-template-columns: 1fr;
            }
            .realsatisfied-agent-testimonials .testimonials-grid.columns-2 {
                grid-template-columns: repeat(2, 1fr);
            }
            .realsatisfied-agent-testimonials .testimonials-grid.columns-3 {
                grid-template-columns: repeat(3, 1fr);
            }
            .realsatisfied-agent-testimonials .testimonial-item {
                border: 1px solid #ddd;
                padding: 20px;
                border-radius: 5px;
                background: #f9f9f9;
            }
            .realsatisfied-agent-testimonials .customer-name {
                margin: 0 0 10px 0;
                font-size: 1.1em;
                font-weight: bold;
            }
            .realsatisfied-agent-testimonials .customer-type {
                margin: 0 0 10px 0;
                font-style: italic;
                color: #666;
            }
            .realsatisfied-agent-testimonials .testimonial-content {
                margin: 10px 0;
                line-height: 1.5;
            }
            .realsatisfied-agent-testimonials .testimonial-date {
                margin: 10px 0 0 0;
                font-size: 0.9em;
                color: #888;
            }
            .realsatisfied-agent-testimonials .stars {
                color: #ffa500;
            }
            .realsatisfied-agent-testimonials-error {
                padding: 10px;
                margin: 10px 0;
                border-left: 4px solid #ffba00;
                background: #fff8e5;
            }
            @media (max-width: 768px) {
                .realsatisfied-agent-testimonials .testimonials-grid {
                    grid-template-columns: 1fr !important;
                }
            }
            '
        );
    }
}
