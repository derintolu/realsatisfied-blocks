<?php
/**
 * RealSatisfied Office Overall Ratings Block
 * 
 * Displays office-wide satisfaction, recommendation, performance percentages
 * with total review count and visual rating displays
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RealSatisfied Office Ratings Block Class
 */
class RealSatisfied_Office_Ratings_Block {
    
    /**
     * Block name
     *
     * @var string
     */
    private $block_name = 'realsatisfied-blocks/office-ratings';

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
                'alignWide' => false,
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
                ),
                'layout' => array(
                    'default' => array(
                        'type' => 'flex',
                        'orientation' => 'vertical'
                    )
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
                'showOfficeName' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showOverallRating' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showStars' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showReviewCount' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showDetailedRatings' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'showTrustBadge' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'linkToProfile' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'starColorFilled' => array(
                    'type' => 'string',
                    'default' => '#FFD700'
                ),
                'starColorEmpty' => array(
                    'type' => 'string',
                    'default' => '#CCCCCC'
                ),
                'textColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'backgroundColor' => array(
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
            return $this->render_error(__('No vanity key specified for office ratings.', 'realsatisfied-blocks'));
        }

        // Get RSS parser instance
        $rss_parser = RealSatisfied_Office_RSS_Parser::get_instance();
        
        // Fetch office data
        $office_data = $rss_parser->fetch_office_data($vanity_key);
        
        if (is_wp_error($office_data)) {
            return $this->render_error($office_data->get_error_message());
        }

        // Extract channel data
        $channel = $office_data['channel'];
        
        // Calculate overall rating
        $overall_rating = $rss_parser->calculate_overall_rating(
            $channel['overall_satisfaction'],
            $channel['recommendation_rating'],
            $channel['performance_rating']
        );

        // Debug: Show what dynamic data is being pulled from RSS feed
        // Office: {$channel['office']}
        // Overall Rating: {$overall_rating}/5.0 (calculated from {$channel['overall_satisfaction']}, {$channel['recommendation_rating']}, {$channel['performance_rating']})
        // Reviews: {$channel['response_count']}

        // Get color attributes with defaults
        $star_color_filled = $attributes['starColorFilled'] ?? '#FFD700';
        $star_color_empty = $attributes['starColorEmpty'] ?? '#CCCCCC';
        $text_color = $attributes['textColor'] ?? '';
        $background_color = $attributes['backgroundColor'] ?? '';

        // Build wrapper styles
        $wrapper_styles = array();
        if (!empty($text_color)) {
            $wrapper_styles[] = '--text-color: ' . esc_attr($text_color);
        }
        if (!empty($background_color)) {
            $wrapper_styles[] = '--background-color: ' . esc_attr($background_color);
            $wrapper_styles[] = 'background-color: var(--background-color)';
        }
        $wrapper_styles[] = '--star-color-filled: ' . esc_attr($star_color_filled);
        $wrapper_styles[] = '--star-color-empty: ' . esc_attr($star_color_empty);
        
        $style_attr = !empty($wrapper_styles) ? 'style="' . implode('; ', $wrapper_styles) . '"' : '';

        // Get block wrapper attributes
        $wrapper_attributes = get_block_wrapper_attributes(array(
            'class' => 'realsatisfied-office-ratings'
        ));

        // Start building output
        ob_start();
        ?>
        <div <?php echo $wrapper_attributes; ?> <?php echo $style_attr; ?>>
            <div class="parent">
                <!-- div1: Large rating number -->
                <div class="div1">
                    <?php if ($attributes['showOverallRating'] ?? true): ?>
                        <?php echo esc_html(number_format($overall_rating, 1)); ?>
                    <?php endif; ?>
                </div>
                
                <!-- div2: Stars -->
                <div class="div2">
                    <?php if ($attributes['showStars'] ?? true): ?>
                        <?php echo $this->render_gold_stars($overall_rating); ?>
                    <?php endif; ?>
                </div>
                
                <!-- div3: Office name -->
                <div class="div3">
                    <?php if ($attributes['showOfficeName'] ?? true): ?>
                        <?php echo esc_html($channel['office']); ?>
                    <?php endif; ?>
                </div>
                
                <!-- div4: Review count -->
                <div class="div4">
                    <?php if ($attributes['showReviewCount'] ?? true): ?>
                        <?php if ($attributes['linkToProfile'] ?? true): ?>
                            <a href="https://www.realsatisfied.com/office/<?php echo esc_attr($vanity_key); ?>" target="_blank" rel="noopener noreferrer" class="realsatisfied-review-link">
                                <?php echo esc_html($channel['response_count']); ?> reviews
                            </a>
                        <?php else: ?>
                            <?php echo esc_html($channel['response_count']); ?> reviews
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- div5: Trust badge -->
                <div class="div5">
                    <?php if ($attributes['showTrustBadge'] ?? false): ?>
                        <a href="https://www.realsatisfied.com/" target="_blank" rel="noopener noreferrer" class="realsatisfied-trust-link">
                            <img src="<?php echo esc_url(RSOB_PLUGIN_URL . 'assets/images/RealSatisfied-Trust-Seal-80pix.png'); ?>" alt="<?php echo esc_attr(__('RealSatisfied Trust Seal', 'realsatisfied-blocks')); ?>" class="realsatisfied-trust-image" />
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- div6: Satisfaction label -->
                <div class="div6">
                    <?php if ($attributes['showDetailedRatings'] ?? false): ?>
                        Satisfaction
                    <?php endif; ?>
                </div>
                
                <!-- div7: Satisfaction rating -->
                <div class="div7">
                    <?php if ($attributes['showDetailedRatings'] ?? false): ?>
                        <?php echo esc_html(number_format($this->round_to_website_style($channel['overall_satisfaction'] / 20), 1)); ?>
                    <?php endif; ?>
                </div>
                
                <!-- div8: Satisfaction stars -->
                <div class="div8">
                    <?php if ($attributes['showDetailedRatings'] ?? false): ?>
                        <?php echo $this->render_gold_stars($this->round_to_website_style($channel['overall_satisfaction'] / 20)); ?>
                    <?php endif; ?>
                </div>
                
                <!-- div9: Recommendation label -->
                <div class="div9">
                    <?php if ($attributes['showDetailedRatings'] ?? false): ?>
                        Recommendation
                    <?php endif; ?>
                </div>
                
                <!-- div10: Recommendation rating -->
                <div class="div10">
                    <?php if ($attributes['showDetailedRatings'] ?? false): ?>
                        <?php echo esc_html(number_format($this->round_to_website_style($channel['recommendation_rating'] / 20), 1)); ?>
                    <?php endif; ?>
                </div>
                
                <!-- div11: Recommendation stars -->
                <div class="div11">
                    <?php if ($attributes['showDetailedRatings'] ?? false): ?>
                        <?php echo $this->render_gold_stars($this->round_to_website_style($channel['recommendation_rating'] / 20)); ?>
                    <?php endif; ?>
                </div>
                
                <!-- div12: Performance label -->
                <div class="div12">
                    <?php if ($attributes['showDetailedRatings'] ?? false): ?>
                        Performance
                    <?php endif; ?>
                </div>
                
                <!-- div13: Performance rating -->
                <div class="div13">
                    <?php if ($attributes['showDetailedRatings'] ?? false): ?>
                        <?php echo esc_html(number_format($this->round_to_website_style($channel['performance_rating'] / 20), 1)); ?>
                    <?php endif; ?>
                </div>
                
                <!-- div14: Performance stars -->
                <div class="div14">
                    <?php if ($attributes['showDetailedRatings'] ?? false): ?>
                        <?php echo $this->render_gold_stars($this->round_to_website_style($channel['performance_rating'] / 20)); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
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
     * Render SVG star rating HTML
     *
     * @param float $rating Rating value (0.0-5.0)
     * @return string Star rating HTML
     */
    private function render_svg_star_rating($rating) {
        $width = min(max($rating * 20, 0), 100);
        
        $html = '<div class="realsatisfied-svg-stars">';
        
        // Background (empty) stars
        $html .= '<svg viewBox="0 0 80 16" class="realsatisfied-svg-stars-bg">';
        $html .= '<path fill="#A0A0A0" d="M6.00682 0.92556C6.25927 0.16202 7.35844 0.162021 7.61088 0.92556L8.51285 3.65365C8.62575 3.99511 8.94954 4.2263 9.31488 4.2263H12.2337C13.0506 4.2263 13.3903 5.25364 12.7294 5.72553L10.368 7.41159C10.0724 7.62262 9.94877 7.99669 10.0617 8.33816L10.9636 11.0663C11.2161 11.8298 10.3268 12.4647 9.66592 11.9928L7.30453 10.3068C7.00897 10.0957 6.60874 10.0957 6.31317 10.3068L3.95178 11.9928C3.29087 12.4647 2.40162 11.8298 2.65407 11.0662L3.55604 8.33816C3.66894 7.99669 3.54526 7.62262 3.24969 7.41159L0.888301 5.72553C0.227393 5.25364 0.567055 4.2263 1.38398 4.2263H4.30282C4.66816 4.2263 4.99195 3.99511 5.10485 3.65365L6.00682 0.92556Z" />';
        $html .= '<path fill="#A0A0A0" d="M22.7822 0.92556C23.0347 0.16202 24.1338 0.162021 24.3863 0.92556L25.2882 3.65365C25.4011 3.99511 25.7249 4.2263 26.0903 4.2263H29.0091C29.826 4.2263 30.1657 5.25364 29.5048 5.72553L27.1434 7.41159C26.8478 7.62262 26.7242 7.99669 26.8371 8.33816L27.739 11.0662C27.9915 11.8298 27.1022 12.4647 26.4413 11.9928L24.0799 10.3068C23.7844 10.0957 23.3841 10.0957 23.0886 10.3068L20.7272 11.9928C20.0663 12.4647 19.177 11.8298 19.4295 11.0662L20.3314 8.33816C20.4443 7.99669 20.3206 7.62262 20.0251 7.41159L17.6637 5.72553C17.0028 5.25364 17.3424 4.2263 18.1594 4.2263H21.0782C21.4436 4.2263 21.7673 3.99511 21.8802 3.65365L22.7822 0.92556Z" />';
        $html .= '<path fill="#A0A0A0" d="M39.5575 0.92556C39.81 0.16202 40.9091 0.162021 41.1616 0.92556L42.0635 3.65365C42.1764 3.99511 42.5002 4.2263 42.8656 4.2263H45.7844C46.6014 4.2263 46.941 5.25364 46.2801 5.72553L43.9187 7.41159C43.6232 7.62262 43.4995 7.99669 43.6124 8.33816L44.5144 11.0662C44.7668 11.8298 43.8775 12.4647 43.2166 11.9928L40.8552 10.3068C40.5597 10.0957 40.1594 10.0957 39.8639 10.3068L37.5025 11.9928C36.8416 12.4647 35.9523 11.8298 36.2048 11.0662L37.1067 8.33816C37.2196 7.99669 37.0959 7.62262 36.8004 7.41159L34.439 5.72553C33.7781 5.25364 34.1177 4.2263 34.9347 4.2263H37.8535C38.2189 4.2263 38.5426 3.99511 38.6555 3.65365L39.5575 0.92556Z" />';
        $html .= '<path fill="#A0A0A0" d="M56.3329 0.92556C56.5853 0.16202 57.6845 0.162021 57.9369 0.92556L58.8389 3.65365C58.9518 3.99511 59.2756 4.2263 59.6409 4.2263H62.5597C63.3767 4.2263 63.7163 5.25364 63.0554 5.72553L60.694 7.41159C60.3985 7.62262 60.2748 7.99669 60.3877 8.33816L61.2897 11.0662C61.5421 11.8298 60.6528 12.4647 59.9919 11.9928L57.6305 10.3068C57.335 10.0957 56.9347 10.0957 56.6392 10.3068L54.2778 11.9928C53.6169 12.4647 52.7276 11.8298 52.9801 11.0662L53.882 8.33816C53.9949 7.99669 53.8712 7.62262 53.5757 7.41159L51.2143 5.72553C50.5534 5.25364 50.893 4.2263 51.71 4.2263H54.6288C54.9942 4.2263 55.3179 3.99511 55.4308 3.65365L56.3329 0.92556Z" />';
        $html .= '<path fill="#A0A0A0" d="M73.1103 0.92556C73.3628 0.16202 74.462 0.162021 74.7144 0.92556L75.6164 3.65365C75.7293 3.99511 76.0531 4.2263 76.4184 4.2263H79.3372C80.1542 4.2263 80.4938 5.25364 79.8329 5.72553L77.4715 7.41159C77.176 7.62262 77.0523 7.99669 77.1652 8.33816L78.0672 11.0662C78.3196 11.8298 77.4303 12.4647 76.7694 11.9928L74.408 10.3068C74.1125 10.0957 73.7123 10.0957 73.4167 10.3068L71.0553 11.9928C70.3944 12.4647 69.5051 11.8298 69.7576 11.0662L70.6596 8.33816C70.7725 7.99669 70.6488 7.62262 70.3532 7.41159L67.9918 5.72553C67.3309 5.25364 67.6706 4.2263 68.4875 4.2263H71.4063C71.7717 4.2263 72.0955 3.99511 72.2084 3.65365L73.1103 0.92556Z" />';
        $html .= '</svg>';
        
        // Filled (gold) stars with proper clipping
        $html .= '<svg viewBox="0 0 80 16" class="realsatisfied-svg-stars-fill" data-width="' . $width . '">';
        $html .= '<path fill="#F0B64F" d="M6.00682 0.92556C6.25927 0.16202 7.35844 0.162021 7.61088 0.92556L8.51285 3.65365C8.62575 3.99511 8.94954 4.2263 9.31488 4.2263H12.2337C13.0506 4.2263 13.3903 5.25364 12.7294 5.72553L10.368 7.41159C10.0724 7.62262 9.94877 7.99669 10.0617 8.33816L10.9636 11.0663C11.2161 11.8298 10.3268 12.4647 9.66592 11.9928L7.30453 10.3068C7.00897 10.0957 6.60874 10.0957 6.31317 10.3068L3.95178 11.9928C3.29087 12.4647 2.40162 11.8298 2.65407 11.0662L3.55604 8.33816C3.66894 7.99669 3.54526 7.62262 3.24969 7.41159L0.888301 5.72553C0.227393 5.25364 0.567055 4.2263 1.38398 4.2263H4.30282C4.66816 4.2263 4.99195 3.99511 5.10485 3.65365L6.00682 0.92556Z" />';
        $html .= '<path fill="#F0B64F" d="M22.7822 0.92556C23.0347 0.16202 24.1338 0.162021 24.3863 0.92556L25.2882 3.65365C25.4011 3.99511 25.7249 4.2263 26.0903 4.2263H29.0091C29.826 4.2263 30.1657 5.25364 29.5048 5.72553L27.1434 7.41159C26.8478 7.62262 26.7242 7.99669 26.8371 8.33816L27.739 11.0662C27.9915 11.8298 27.1022 12.4647 26.4413 11.9928L24.0799 10.3068C23.7844 10.0957 23.3841 10.0957 23.0886 10.3068L20.7272 11.9928C20.0663 12.4647 19.177 11.8298 19.4295 11.0662L20.3314 8.33816C20.4443 7.99669 20.3206 7.62262 20.0251 7.41159L17.6637 5.72553C17.0028 5.25364 17.3424 4.2263 18.1594 4.2263H21.0782C21.4436 4.2263 21.7673 3.99511 21.8802 3.65365L22.7822 0.92556Z" />';
        $html .= '<path fill="#F0B64F" d="M39.5575 0.92556C39.81 0.16202 40.9091 0.162021 41.1616 0.92556L42.0635 3.65365C42.1764 3.99511 42.5002 4.2263 42.8656 4.2263H45.7844C46.6014 4.2263 46.941 5.25364 46.2801 5.72553L43.9187 7.41159C43.6232 7.62262 43.4995 7.99669 43.6124 8.33816L44.5144 11.0662C44.7668 11.8298 43.8775 12.4647 43.2166 11.9928L40.8552 10.3068C40.5597 10.0957 40.1594 10.0957 39.8639 10.3068L37.5025 11.9928C36.8416 12.4647 35.9523 11.8298 36.2048 11.0662L37.1067 8.33816C37.2196 7.99669 37.0959 7.62262 36.8004 7.41159L34.439 5.72553C33.7781 5.25364 34.1177 4.2263 34.9347 4.2263H37.8535C38.2189 4.2263 38.5426 3.99511 38.6555 3.65365L39.5575 0.92556Z" />';
        $html .= '<path fill="#F0B64F" d="M56.3329 0.92556C56.5853 0.16202 57.6845 0.162021 57.9369 0.92556L58.8389 3.65365C58.9518 3.99511 59.2756 4.2263 59.6409 4.2263H62.5597C63.3767 4.2263 63.7163 5.25364 63.0554 5.72553L60.694 7.41159C60.3985 7.62262 60.2748 7.99669 60.3877 8.33816L61.2897 11.0662C61.5421 11.8298 60.6528 12.4647 59.9919 11.9928L57.6305 10.3068C57.335 10.0957 56.9347 10.0957 56.6392 10.3068L54.2778 11.9928C53.6169 12.4647 52.7276 11.8298 52.9801 11.0662L53.882 8.33816C53.9949 7.99669 53.8712 7.62262 53.5757 7.41159L51.2143 5.72553C50.5534 5.25364 50.893 4.2263 51.71 4.2263H54.6288C54.9942 4.2263 55.3179 3.99511 55.4308 3.65365L56.3329 0.92556Z" />';
        $html .= '<path fill="#F0B64F" d="M73.1103 0.92556C73.3628 0.16202 74.462 0.162021 74.7144 0.92556L75.6164 3.65365C75.7293 3.99511 76.0531 4.2263 76.4184 4.2263H79.3372C80.1542 4.2263 80.4938 5.25364 79.8329 5.72553L77.4715 7.41159C77.176 7.62262 77.0523 7.99669 77.1652 8.33816L78.0672 11.0662C78.3196 11.8298 77.4303 12.4647 76.7694 11.9928L74.408 10.3068C74.1125 10.0957 73.7123 10.0957 73.4167 10.3068L71.0553 11.9928C70.3944 12.4647 69.5051 11.8298 69.7576 11.0662L70.6596 8.33816C70.7725 7.99669 70.6488 7.62262 70.3532 7.41159L67.9918 5.72553C67.3309 5.25364 67.6706 4.2263 68.4875 4.2263H71.4063C71.7717 4.2263 72.0955 3.99511 72.2084 3.65365L73.1103 0.92556Z" />';
        $html .= '</svg>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Render gold stars for simple display
     *
     * @param float $rating Rating value (0.0-5.0)
     * @return string Gold star rating HTML
     */
    private function render_gold_stars($rating) {
        $html = '';
        
        for ($i = 1; $i <= 5; $i++) {
            $filled = $rating >= $i;
            $halfFilled = !$filled && $rating >= ($i - 0.5);
            
            if ($filled) {
                // Full gold star
                $html .= '<span class="realsatisfied-star filled">★</span>';
            } elseif ($halfFilled) {
                // Half gold star
                $html .= '<span class="realsatisfied-star half">★</span>';
            } else {
                // Empty star
                $html .= '<span class="realsatisfied-star empty">☆</span>';
            }
        }
        
        return $html;
    }

    /**
     * Render mini star rating HTML for compact display
     *
     * @param float $rating Rating value (0.0-5.0)
     * @return string Mini star rating HTML
     */
    private function render_mini_star_rating($rating) {
        $html = '';
        
        for ($i = 1; $i <= 5; $i++) {
            $filled = $rating >= $i;
            $halfFilled = !$filled && $rating >= ($i - 0.5);
            
            $html .= '<div class="realsatisfied-mini-star-container">';
            
            if ($filled) {
                // Full star
                $html .= '<div class="realsatisfied-mini-star filled"></div>';
            } elseif ($halfFilled) {
                // Half star
                $html .= '<div class="realsatisfied-mini-star half"></div>';
            } else {
                // Empty star - gray background
                $html .= '<div class="realsatisfied-mini-star empty"></div>';
            }
            
            $html .= '</div>';
        }
        
        return $html;
    }

    /**
     * Render RealSatisfied Trust Badge
     *
     * @return string Trust badge HTML
     */
    private function render_realsatisfied_trust_badge() {
        $badge_url = RSOB_PLUGIN_URL . 'assets/images/realsatisfied-trust-badge.svg';
        return '<img src="' . esc_url($badge_url) . '" alt="' . esc_attr(__('Verified with RealSatisfied', 'realsatisfied-blocks')) . '" class="realsatisfied-trust-badge-img" />';
    }

    /**
     * Render error message
     *
     * @param string $message Error message
     * @return string Error HTML
     */
    private function render_error($message) {
        return '<div class="realsatisfied-office-ratings-error notice notice-warning">' .
               '<p>' . esc_html($message) . '</p>' .
               '</div>';
    }
    
    /**
     * Round ratings to match website display style
     * Rounds to nearest 0.1, with 0.05 rounding up
     *
     * @param float $rating Rating to round
     * @return float Rounded rating
     */
    private function round_to_website_style($rating) {
        // Round to nearest 0.1, with bias toward rounding up like the website
        return round($rating * 10) / 10;
    }

    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'realsatisfied-office-ratings-editor',
            RSOB_PLUGIN_URL . 'blocks/office-ratings/office-ratings-editor.js',
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
            RSOB_PLUGIN_VERSION,
            true
        );

        // Localize script for plugin URL
        wp_localize_script(
            'realsatisfied-office-ratings-editor',
            'realsatisfiedBlocks',
            array(
                'pluginUrl' => RSOB_PLUGIN_URL,
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('realsatisfied_blocks_nonce')
            )
        );

        wp_enqueue_style(
            'realsatisfied-office-ratings-editor',
            RSOB_PLUGIN_URL . 'assets/realsatisfied-blocks.css',
            array(),
            RSOB_PLUGIN_VERSION
        );
    }
}

// Initialize the block
new RealSatisfied_Office_Ratings_Block(); 