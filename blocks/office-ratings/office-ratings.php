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
    private $block_name = 'realsatisfied-office-blocks/office-ratings';

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
                'showPhoto' => array(
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
                'linkToProfile' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'displaySize' => array(
                    'type' => 'string',
                    'default' => 'medium' // small, medium, large
                ),
                'displayStyle' => array(
                    'type' => 'string',
                    'default' => 'minimal' // minimal, card, bordered
                ),
                'textAlignment' => array(
                    'type' => 'string',
                    'default' => 'center' // left, center, right
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
            return $this->render_error(__('No vanity key specified for office ratings.', 'realsatisfied-office-blocks'));
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

        // Build CSS classes
        $css_classes = array(
            'realsatisfied-office-ratings',
            'size-' . $attributes['displaySize'],
            'style-' . $attributes['displayStyle'],
            'align-' . $attributes['textAlignment']
        );

        // Start building output
        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $css_classes)); ?>">
            
            <?php if ($attributes['showPhoto'] && !empty($channel['logo'])): ?>
                <div class="office-logo">
                    <img src="<?php echo esc_url($channel['logo']); ?>" 
                         alt="<?php echo esc_attr($channel['office']); ?> Logo" 
                         class="office-logo-img" />
                </div>
            <?php endif; ?>

            <div class="office-info">
                <?php if ($attributes['showOfficeName']): ?>
                    <h3 class="office-name"><?php echo esc_html($channel['office']); ?></h3>
                <?php endif; ?>
                
                <?php if ($attributes['showOverallRating']): ?>
                    <div class="overall-rating">
                        <span class="rating-score"><?php echo esc_html($overall_rating); ?></span>
                        <?php if ($attributes['showStars']): ?>
                            <div class="star-rating" data-rating="<?php echo esc_attr($overall_rating); ?>">
                                <?php echo $this->render_star_rating($overall_rating); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($attributes['showReviewCount']): ?>
                    <div class="review-count">
                        <?php if ($attributes['linkToProfile']): ?>
                            <a href="<?php echo esc_url($channel['link']); ?>" 
                               target="_blank" 
                               class="review-count-link">
                                <?php 
                                printf(
                                    _n('%d review', '%d reviews', $channel['response_count'], 'realsatisfied-office-blocks'),
                                    $channel['response_count']
                                );
                                ?>
                            </a>
                        <?php else: ?>
                            <span class="review-count-text">
                                <?php 
                                printf(
                                    _n('%d review', '%d reviews', $channel['response_count'], 'realsatisfied-office-blocks'),
                                    $channel['response_count']
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($attributes['showDetailedRatings']): ?>
                    <div class="detailed-ratings">
                        <div class="rating-item">
                            <span class="rating-label"><?php esc_html_e('Satisfaction', 'realsatisfied-office-blocks'); ?></span>
                            <span class="rating-value"><?php echo esc_html($rss_parser->calculate_star_rating($channel['overall_satisfaction'])); ?></span>
                            <div class="rating-stars">
                                <?php echo $this->render_star_rating($rss_parser->calculate_star_rating($channel['overall_satisfaction'])); ?>
                            </div>
                        </div>
                        <div class="rating-item">
                            <span class="rating-label"><?php esc_html_e('Recommendation', 'realsatisfied-office-blocks'); ?></span>
                            <span class="rating-value"><?php echo esc_html($rss_parser->calculate_star_rating($channel['recommendation_rating'])); ?></span>
                            <div class="rating-stars">
                                <?php echo $this->render_star_rating($rss_parser->calculate_star_rating($channel['recommendation_rating'])); ?>
                            </div>
                        </div>
                        <div class="rating-item">
                            <span class="rating-label"><?php esc_html_e('Performance', 'realsatisfied-office-blocks'); ?></span>
                            <span class="rating-value"><?php echo esc_html($rss_parser->calculate_star_rating($channel['performance_rating'])); ?></span>
                            <div class="rating-stars">
                                <?php echo $this->render_star_rating($rss_parser->calculate_star_rating($channel['performance_rating'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
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
            return $custom_fields->get_vanity_key();
        } elseif (!empty($attributes['manualVanityKey'])) {
            return $attributes['manualVanityKey'];
        }
        
        return false;
    }

    /**
     * Render star rating HTML
     *
     * @param float $rating Rating value (0.0-5.0)
     * @return string Star rating HTML
     */
    private function render_star_rating($rating) {
        $full_stars = floor($rating);
        $partial_star = $rating - $full_stars;
        $empty_stars = 5 - ceil($rating);
        
        $html = '<div class="stars-container">';
        
        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $html .= '<span class="star star-full">★</span>';
        }
        
        // Partial star
        if ($partial_star > 0) {
            $percentage = $partial_star * 100;
            $html .= '<span class="star star-partial" style="--partial: ' . $percentage . '%">★</span>';
        }
        
        // Empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            $html .= '<span class="star star-empty">☆</span>';
        }
        
        $html .= '</div>';
        
        return $html;
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
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'realsatisfied-office-ratings-editor',
            RSOB_PLUGIN_URL . 'blocks/office-ratings/office-ratings-editor.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            RSOB_PLUGIN_VERSION,
            true
        );

        wp_enqueue_style(
            'realsatisfied-office-ratings-editor',
            RSOB_PLUGIN_URL . 'blocks/office-ratings/office-ratings-editor.css',
            array(),
            RSOB_PLUGIN_VERSION
        );
    }
}

// Initialize the block
new RealSatisfied_Office_Ratings_Block(); 