<?php
/**
 * RealSatisfied Testimonial Marquee Block
 * 
 * Displays customer testimonials in a scrolling marquee format with two rows
 * moving in opposite directions, pulling testimonials from across the company
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RealSatisfied Testimonial Marquee Block Class
 */
class RealSatisfied_Testimonial_Marquee_Block {
    
    /**
     * Block name
     *
     * @var string
     */
    private $block_name = 'realsatisfied-blocks/testimonial-marquee';

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
        // Hooks are now handled in register_block method
    }

    /**
     * Register the block
     */
    public function register_block() {
        
        // Register editor script
        $asset_file_path = plugin_dir_path(__FILE__) . 'testimonial-marquee-editor.asset.php';
        $asset_file = file_exists($asset_file_path) ? include $asset_file_path : false;
        
        // Fallback dependencies and version if asset file doesn't exist
        $dependencies = $asset_file ? $asset_file['dependencies'] : array(
            'wp-blocks',
            'wp-element', 
            'wp-block-editor',
            'wp-components',
            'wp-i18n',
            'wp-server-side-render'
        );
        $version = $asset_file ? $asset_file['version'] : '1.0.0';
        
        wp_register_script(
            'realsatisfied-testimonial-marquee-editor',
            plugin_dir_url(__FILE__) . 'testimonial-marquee-editor.js',
            $dependencies,
            $version
        );
        
        // Register frontend script
        wp_register_script(
            'realsatisfied-testimonial-marquee-view',
            plugin_dir_url(__FILE__) . 'view.js',
            array(),
            '1.0.0',
            true
        );
        
        // Register styles
        wp_register_style(
            'realsatisfied-testimonial-marquee-style',
            RSOB_PLUGIN_URL . 'assets/realsatisfied-blocks.css',
            array(),
            '1.0.0'
        );
        
        // Register block with proper assets
        $result = register_block_type($this->block_name, array(
            'attributes' => $this->get_block_attributes(),
            'render_callback' => array($this, 'render_block'),
            'editor_script' => 'realsatisfied-testimonial-marquee-editor',
            'script' => 'realsatisfied-testimonial-marquee-view',
            'style' => 'realsatisfied-testimonial-marquee-style'
        ));
        
        if (!$result) {
            error_log('RealSatisfied Testimonial Marquee: Block registration failed');
        }
        
        return $result;
    }

    /**
     * Get block attributes
     *
     * @return array Block attributes
     */
    private function get_block_attributes() {
        return array(
            'companyId' => array(
                'type' => 'string',
                'default' => ''
            ),
            'maxTestimonials' => array(
                'type' => 'number',
                'default' => 100
            ),
            'animationSpeed' => array(
                'type' => 'number',
                'default' => 60 // seconds for one complete scroll
            ),
            'pauseOnHover' => array(
                'type' => 'boolean',
                'default' => false
            ),
            'showAgentAvatar' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'showAgentName' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'showCustomerLocation' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'showCustomerType' => array(
                'type' => 'boolean',
                'default' => false
            ),
            'maxTestimonialLength' => array(
                'type' => 'number',
                'default' => 150
            ),
            'filterByRating' => array(
                'type' => 'boolean',
                'default' => true // Only show good testimonials
            ),
            'backgroundColor' => array(
                'type' => 'string',
                'default' => '#ffffff'
            ),
            'textColor' => array(
                'type' => 'string',
                'default' => '#333333'
            ),
            'cardBackgroundColor' => array(
                'type' => 'string',
                'default' => '#f8f9fa'
            ),
            'borderColor' => array(
                'type' => 'string',
                'default' => '#e9ecef'
            )
        );
    }


    /**
     * Render the block
     *
     * @param array $attributes Block attributes
     * @return string Block HTML
     */
    public function render_block($attributes) {
        // Merge with defaults
        $attributes = wp_parse_args($attributes, $this->get_default_attributes());

        // Check if company ID is provided
        if (empty($attributes['companyId'])) {
            return '<div class="rs-testimonial-marquee-empty"><p>' . __('Please configure a Company ID in the block settings.', 'realsatisfied-blocks') . '</p></div>';
        }

        // Get company testimonials
        $testimonials = $this->get_company_testimonials($attributes);
        
        if (empty($testimonials)) {
            return $this->render_no_testimonials_message();
        }

        // Filter testimonials (good ones only if enabled)
        if ($attributes['filterByRating']) {
            $testimonials = $this->filter_good_testimonials($testimonials);
        }

        // Limit testimonials
        if (count($testimonials) > $attributes['maxTestimonials']) {
            $testimonials = array_slice($testimonials, 0, $attributes['maxTestimonials']);
        }

        // Split testimonials between rows to ensure different content
        $row1_testimonials = $this->create_row_testimonial_set($testimonials, 'even');
        $row2_testimonials = $this->create_row_testimonial_set($testimonials, 'odd');

        // Generate unique ID for this block instance
        $block_id = 'rs-testimonial-marquee-' . wp_rand(1000, 9999);

        // Generate CSS
        $css = $this->generate_block_css($block_id, $attributes);

        // Build HTML
        $html = $css;
        $html .= '<div id="' . esc_attr($block_id) . '" class="rs-testimonial-marquee" data-speed="' . esc_attr($attributes['animationSpeed']) . '" data-pause-hover="' . esc_attr($attributes['pauseOnHover'] ? 'true' : 'false') . '">';
        
        // Row 1 (left to right) - seamless infinite scroll
        $html .= '<div class="rs-marquee-row rs-marquee-row-1">';
        $html .= '<div class="rs-marquee-track rs-marquee-left-to-right">';
        
        // Original content
        $html .= '<div class="rs-marquee-content rs-marquee-original">';
        foreach ($row1_testimonials as $testimonial) {
            $html .= $this->render_testimonial_card($testimonial, $attributes);
        }
        $html .= '</div>';
        
        // Duplicate content for seamless loop
        $html .= '<div class="rs-marquee-content rs-marquee-duplicate">';
        foreach ($row1_testimonials as $testimonial) {
            $html .= $this->render_testimonial_card($testimonial, $attributes);
        }
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';

        // Row 2 (right to left) - seamless infinite scroll
        $html .= '<div class="rs-marquee-row rs-marquee-row-2">';
        $html .= '<div class="rs-marquee-track rs-marquee-right-to-left">';
        
        // Original content
        $html .= '<div class="rs-marquee-content rs-marquee-original">';
        foreach ($row2_testimonials as $testimonial) {
            $html .= $this->render_testimonial_card($testimonial, $attributes);
        }
        $html .= '</div>';
        
        // Duplicate content for seamless loop
        $html .= '<div class="rs-marquee-content rs-marquee-duplicate">';
        foreach ($row2_testimonials as $testimonial) {
            $html .= $this->render_testimonial_card($testimonial, $attributes);
        }
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

        // Add JavaScript for animation control
        $html .= $this->generate_block_javascript($block_id, $attributes);

        return $html;
    }

    /**
     * Get company testimonials
     *
     * @param array $attributes Block attributes
     * @return array Testimonials array
     */
    private function get_company_testimonials($attributes) {        
        if (empty($attributes['companyId'])) {
            return array();
        }
        
        if (!class_exists('RealSatisfied_Company_RSS_Parser')) {
            error_log('RealSatisfied Testimonial Marquee: RealSatisfied_Company_RSS_Parser class not found');
            return array();
        }
        
        $parser = RealSatisfied_Company_RSS_Parser::get_instance();
        
        $options = array(
            'limit' => $attributes['maxTestimonials'] * 2, // Get more to filter from
            'preserve_order' => false // Allow shuffling
        );

        $company_data = $parser->fetch_company_data($attributes['companyId'], $options);
        
        if (is_wp_error($company_data)) {
            error_log('RealSatisfied Testimonial Marquee: Error fetching company data: ' . $company_data->get_error_message());
            return array();
        }
        
        if (empty($company_data['testimonials'])) {
            return array();
        }

        return $company_data['testimonials'];
    }

    /**
     * Create testimonial set for specific row with maximum diversity
     *
     * @param array $testimonials All available testimonials
     * @param string $type 'even' or 'odd' for row distribution
     * @return array Row-specific testimonial set with high variety
     */
    private function create_row_testimonial_set($testimonials, $type) {
        if (empty($testimonials)) {
            return array();
        }
        
        // First, group testimonials by agent to ensure diversity
        $testimonials_by_agent = array();
        foreach ($testimonials as $testimonial) {
            $agent_key = !empty($testimonial['agent_name']) ? $testimonial['agent_name'] : 'unknown';
            if (!isset($testimonials_by_agent[$agent_key])) {
                $testimonials_by_agent[$agent_key] = array();
            }
            $testimonials_by_agent[$agent_key][] = $testimonial;
        }
        
        // Shuffle each agent's testimonials
        foreach ($testimonials_by_agent as &$agent_testimonials) {
            shuffle($agent_testimonials);
        }
        
        // Create diverse set by rotating through agents
        $diverse_set = array();
        $agent_names = array_keys($testimonials_by_agent);
        shuffle($agent_names); // Randomize agent order
        
        $max_per_agent = 2; // Maximum testimonials per agent in the row
        $agent_counters = array_fill_keys($agent_names, 0);
        
        // Build diverse set with round-robin selection
        for ($round = 0; $round < $max_per_agent; $round++) {
            foreach ($agent_names as $agent_name) {
                if (isset($testimonials_by_agent[$agent_name][$round])) {
                    $diverse_set[] = $testimonials_by_agent[$agent_name][$round];
                }
            }
        }
        
        // Shuffle the diverse set
        shuffle($diverse_set);
        
        // Split between rows (even/odd)
        $row_testimonials = array();
        foreach ($diverse_set as $index => $testimonial) {
            if ($type === 'even' && $index % 2 === 0) {
                $row_testimonials[] = $testimonial;
            } elseif ($type === 'odd' && $index % 2 === 1) {
                $row_testimonials[] = $testimonial;
            }
        }
        
        // If we don't have enough testimonials, carefully add more without clustering
        $target_count = 25;
        if (count($row_testimonials) < $target_count && count($diverse_set) > 0) {
            $extended_set = $row_testimonials;
            $needed = $target_count - count($row_testimonials);
            
            // Add more testimonials while maintaining diversity
            $source_index = 0;
            $last_agent = '';
            
            for ($i = 0; $i < $needed; $i++) {
                $attempts = 0;
                do {
                    $candidate = $diverse_set[$source_index % count($diverse_set)];
                    $source_index++;
                    $attempts++;
                    
                    // Prevent same agent appearing consecutively
                    $candidate_agent = !empty($candidate['agent_name']) ? $candidate['agent_name'] : 'unknown';
                    
                } while ($candidate_agent === $last_agent && $attempts < 10);
                
                $extended_set[] = $candidate;
                $last_agent = $candidate_agent;
            }
            
            return $extended_set;
        }
        
        return $row_testimonials;
    }

    /**
     * Filter testimonials to show only good ones
     *
     * @param array $testimonials All testimonials
     * @return array Filtered testimonials
     */
    private function filter_good_testimonials($testimonials) {
        $good_keywords = array(
            'excellent', 'outstanding', 'amazing', 'fantastic', 'wonderful', 'great',
            'professional', 'recommend', 'best', 'perfect', 'awesome', 'superb',
            'exceptional', 'impressed', 'satisfied', 'pleased', 'helpful', 'knowledgeable'
        );

        $bad_keywords = array(
            'terrible', 'awful', 'worst', 'horrible', 'disappointing', 'unprofessional',
            'rude', 'incompetent', 'dissatisfied', 'complaint', 'problem', 'issue'
        );

        return array_filter($testimonials, function($testimonial) use ($good_keywords, $bad_keywords) {
            $description = strtolower($testimonial['text']);
            
            // Check for bad keywords first
            foreach ($bad_keywords as $bad_word) {
                if (strpos($description, $bad_word) !== false) {
                    return false;
                }
            }

            // Check for good keywords or if description is reasonably positive
            foreach ($good_keywords as $good_word) {
                if (strpos($description, $good_word) !== false) {
                    return true;
                }
            }

            // If no specific keywords, include if description is substantial (likely positive)
            return strlen(trim($testimonial['text'])) > 20;
        });
    }

    /**
     * Render individual testimonial card
     *
     * @param array $testimonial Testimonial data
     * @param array $attributes Block attributes
     * @return string Testimonial card HTML
     */
    private function render_testimonial_card($testimonial, $attributes) {
        $html = '<div class="rs-testimonial-card">';
        
        // Testimonial content - using 'text' field from parser
        $description = $testimonial['text'];
        if (strlen($description) > $attributes['maxTestimonialLength']) {
            $description = substr($description, 0, $attributes['maxTestimonialLength']) . '...';
        }
        
        $html .= '<div class="rs-testimonial-content">';
        $html .= '<p class="rs-testimonial-text">"' . esc_html($description) . '"</p>';
        $html .= '</div>';

        // Customer info
        $html .= '<div class="rs-testimonial-meta">';
        
        $html .= '<div class="rs-customer-info">';
        $html .= '<span class="rs-customer-name">' . esc_html($testimonial['customer_name']) . '</span>';
        
        if ($attributes['showCustomerLocation'] && !empty($testimonial['customer_location'])) {
            $html .= '<span class="rs-customer-location">' . esc_html($testimonial['customer_location']) . '</span>';
        }
        
        if ($attributes['showCustomerType'] && !empty($testimonial['customer_type'])) {
            $html .= '<span class="rs-customer-type">(' . esc_html($testimonial['customer_type']) . ')</span>';
        }
        $html .= '</div>';

        // Agent info
        if ($attributes['showAgentName'] || $attributes['showAgentAvatar']) {
            $html .= '<div class="rs-agent-info">';
            
            if ($attributes['showAgentAvatar'] && !empty($testimonial['agent_avatar'])) {
                $html .= '<img class="rs-agent-avatar" src="' . esc_url($testimonial['agent_avatar']) . '" alt="' . esc_attr($testimonial['agent_name']) . '" />';
            }
            
            if ($attributes['showAgentName'] && !empty($testimonial['agent_name'])) {
                $html .= '<span class="rs-agent-name">' . esc_html($testimonial['agent_name']) . '</span>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate block CSS
     *
     * @param string $block_id Block ID
     * @param array $attributes Block attributes
     * @return string CSS
     */
    private function generate_block_css($block_id, $attributes) {
        $animation_duration = $attributes['animationSpeed'];
        
        $css = '<style>';
        $css .= '#' . $block_id . ' {';
        $css .= 'background-color: ' . esc_attr($attributes['backgroundColor']) . ';';
        $css .= 'color: ' . esc_attr($attributes['textColor']) . ';';
        $css .= 'overflow: hidden;';
        $css .= 'padding: 20px 0;';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-marquee-row {';
        $css .= 'margin: 10px 0;';
        $css .= 'white-space: nowrap;';
        $css .= 'overflow: hidden;';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-marquee-track {';
        $css .= 'display: flex;';
        $css .= 'animation-duration: ' . $animation_duration . 's;';
        $css .= 'animation-timing-function: linear;';
        $css .= 'animation-iteration-count: infinite;';
        $css .= 'animation-fill-mode: both;';
        $css .= 'will-change: transform;';
        $css .= 'transform: translateZ(0);';
        $css .= 'white-space: nowrap;';
        $css .= '}';
        
        $css .= '#' . $block_id . ' .rs-marquee-content {';
        $css .= 'display: flex;';
        $css .= 'gap: 20px;';
        $css .= 'flex-shrink: 0;';
        $css .= 'min-width: 100%;';
        $css .= 'transform: translateZ(0);';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-marquee-left-to-right {';
        $css .= 'animation-name: rs-scroll-left-to-right;';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-marquee-right-to-left {';
        $css .= 'animation-name: rs-scroll-right-to-left;';
        $css .= '}';


        $css .= '#' . $block_id . ' .rs-testimonial-card {';
        $css .= 'background-color: ' . esc_attr($attributes['cardBackgroundColor']) . ';';
        $css .= 'border: 1px solid ' . esc_attr($attributes['borderColor']) . ';';
        $css .= 'border-radius: 8px;';
        $css .= 'padding: 20px;';
        $css .= 'min-width: 350px;';
        $css .= 'max-width: 400px;';
        $css .= 'height: 200px;';
        $css .= 'white-space: normal;';
        $css .= 'flex-shrink: 0;';
        $css .= 'display: flex;';
        $css .= 'flex-direction: column;';
        $css .= 'justify-content: space-between;';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-testimonial-content {';
        $css .= 'flex: 1;';
        $css .= 'display: flex;';
        $css .= 'align-items: center;';
        $css .= 'margin-bottom: 1rem;';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-testimonial-text {';
        $css .= 'font-style: italic;';
        $css .= 'font-size: 0.95rem;';
        $css .= 'margin: 0;';
        $css .= 'line-height: 1.6;';
        $css .= 'color: #000000;';
        $css .= 'position: relative;';
        $css .= 'overflow: hidden;';
        $css .= 'display: -webkit-box;';
        $css .= '-webkit-line-clamp: 4;';
        $css .= '-webkit-box-orient: vertical;';
        $css .= 'padding-left: 1.5rem;';
        $css .= 'padding-top: 0.5rem;';
        $css .= '}';
        
        $css .= '#' . $block_id . ' .rs-testimonial-text::before {';
        $css .= 'content: "\\201C";';
        $css .= 'font-size: 3rem;';
        $css .= 'color: #000000;';
        $css .= 'position: absolute;';
        $css .= 'left: 0;';
        $css .= 'top: -0.25rem;';
        $css .= 'font-family: Georgia, serif;';
        $css .= 'line-height: 1;';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-testimonial-meta {';
        $css .= 'display: flex;';
        $css .= 'justify-content: space-between;';
        $css .= 'align-items: center;';
        $css .= 'font-size: 14px;';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-customer-info {';
        $css .= 'flex: 1;';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-customer-name {';
        $css .= 'font-weight: bold;';
        $css .= 'display: block;';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-customer-location,';
        $css .= '#' . $block_id . ' .rs-customer-type {';
        $css .= 'color: #666;';
        $css .= 'font-size: 12px;';
        $css .= 'display: block;';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-agent-info {';
        $css .= 'display: flex;';
        $css .= 'align-items: center;';
        $css .= 'gap: 8px;';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-agent-avatar {';
        $css .= 'width: 32px;';
        $css .= 'height: 32px;';
        $css .= 'border-radius: 50%;';
        $css .= 'object-fit: cover;';
        $css .= '}';

        $css .= '#' . $block_id . ' .rs-agent-name {';
        $css .= 'font-size: 12px;';
        $css .= 'color: #666;';
        $css .= '}';

        // Keyframe animations for seamless infinite scroll with improved smoothness
        $css .= '@keyframes rs-scroll-left-to-right {';
        $css .= 'from { transform: translate3d(0%, 0, 0); }';
        $css .= 'to { transform: translate3d(-50%, 0, 0); }';
        $css .= '}';

        $css .= '@keyframes rs-scroll-right-to-left {';
        $css .= 'from { transform: translate3d(-50%, 0, 0); }';
        $css .= 'to { transform: translate3d(0%, 0, 0); }';
        $css .= '}';

        // Responsive design
        $css .= '@media (max-width: 768px) {';
        $css .= '#' . $block_id . ' .rs-testimonial-card {';
        $css .= 'min-width: 280px;';
        $css .= 'max-width: 320px;';
        $css .= 'height: 180px;';
        $css .= 'padding: 15px;';
        $css .= '}';
        $css .= '#' . $block_id . ' .rs-testimonial-text {';
        $css .= 'font-size: 0.9rem;';
        $css .= '-webkit-line-clamp: 3;';
        $css .= 'padding-left: 1.25rem;';
        $css .= '}';
        $css .= '#' . $block_id . ' .rs-testimonial-text::before {';
        $css .= 'font-size: 2.5rem;';
        $css .= '}';
        $css .= '}';

        $css .= '@media (max-width: 480px) {';
        $css .= '#' . $block_id . ' .rs-testimonial-card {';
        $css .= 'min-width: 240px;';
        $css .= 'max-width: 280px;';
        $css .= 'height: 160px;';
        $css .= 'padding: 14px;';
        $css .= '}';
        $css .= '#' . $block_id . ' .rs-testimonial-text {';
        $css .= 'font-size: 0.85rem;';
        $css .= '-webkit-line-clamp: 2;';
        $css .= 'padding-left: 1rem;';
        $css .= '}';
        $css .= '#' . $block_id . ' .rs-testimonial-text::before {';
        $css .= 'font-size: 2rem;';
        $css .= 'top: -0.125rem;';
        $css .= '}';
        $css .= '}';

        $css .= '</style>';

        return $css;
    }

    /**
     * Generate block JavaScript
     *
     * @param string $block_id Block ID
     * @param array $attributes Block attributes
     * @return string JavaScript
     */
    private function generate_block_javascript($block_id, $attributes) {
        $js = '<script>';
        $js .= '(function() {';
        $js .= 'const marquee = document.getElementById("' . $block_id . '");';
        $js .= 'if (marquee) {';
        
        // Add intersection observer for performance and glitch monitoring
        $js .= 'const observer = new IntersectionObserver((entries) => {';
        $js .= 'entries.forEach(entry => {';
        $js .= 'const tracks = entry.target.querySelectorAll(".rs-marquee-track");';
        $js .= 'if (entry.isIntersecting) {';
        $js .= 'tracks.forEach(track => {';
        $js .= 'track.style.animationPlayState = "running";';
        $js .= 'track.style.transform = track.style.transform || "";';
        $js .= '});';
        $js .= '} else {';
        $js .= 'tracks.forEach(track => track.style.animationPlayState = "paused");';
        $js .= '}';
        $js .= '});';
        $js .= '});';
        $js .= 'observer.observe(marquee);';
        
        // Add animation monitoring for glitch detection
        $js .= 'const tracks = marquee.querySelectorAll(".rs-marquee-track");';
        $js .= 'tracks.forEach((track, index) => {';
        $js .= 'track.addEventListener("animationiteration", function() {';
        $js .= 'track.style.transform = track.style.transform || "";';
        $js .= '});';
        $js .= '});';
        
        $js .= '}';
        $js .= '})();';
        $js .= '</script>';

        return $js;
    }

    /**
     * Get default attributes
     *
     * @return array Default attributes
     */
    private function get_default_attributes() {
        $attributes = $this->get_block_attributes();
        $defaults = array();
        
        foreach ($attributes as $key => $config) {
            $defaults[$key] = $config['default'];
        }
        
        return $defaults;
    }

    /**
     * Render no testimonials message
     *
     * @return string HTML message
     */
    private function render_no_testimonials_message() {
        return '<div class="rs-testimonial-marquee-empty">' .
               '<p>' . __('No testimonials available at the moment.', 'realsatisfied-blocks') . '</p>' .
               '</div>';
    }
}
