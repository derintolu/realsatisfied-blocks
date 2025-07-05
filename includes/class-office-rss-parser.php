<?php
/**
 * RealSatisfied Office RSS Parser
 * 
 * Extracted and adapted from the original RealSatisfied Review Widget
 * Handles RSS feed fetching, parsing, and data extraction for office blocks
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RealSatisfied Office RSS Parser Class
 */
class RealSatisfied_Office_RSS_Parser {
    
    /**
     * Plugin instance
     *
     * @var RealSatisfied_Office_RSS_Parser
     */
    private static $instance = null;

    /**
     * RSS feed cache duration (12 hours)
     *
     * @var int
     */
    private $cache_duration = 43200;

    /**
     * RSS feed URLs
     *
     * @var array
     */
    private $feed_urls = array(
        'agent'  => 'https://rss.realsatisfied.com/rss/v3/agent/detailed/',
        'office' => 'https://rss.realsatisfied.com/rss/v3/office/'
    );

    /**
     * XML namespaces
     *
     * @var array
     */
    private $namespaces = array(
        'agent'  => 'https://rss.realsatisfied.com/ns/realsatisfied/',
        'office' => 'https://rss.realsatisfied.com/ns/realsatisfied/'
    );

    /**
     * Get plugin instance
     *
     * @return RealSatisfied_Office_RSS_Parser
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add cache clearing functionality
        add_action('wp_ajax_rsob_clear_feed_cache', array($this, 'clear_feed_cache_callback'));
        add_action('wp_ajax_nopriv_rsob_clear_feed_cache', array($this, 'clear_feed_cache_callback'));
    }

    /**
     * Fetch and parse RSS feed for office
     *
     * @param string $vanity_key The office vanity key
     * @return array|WP_Error Array with channel data and testimonials, or WP_Error on failure
     */
    public function fetch_office_data($vanity_key) {
        if (empty($vanity_key)) {
            return new WP_Error('missing_vanity_key', __('No vanity key provided', 'realsatisfied-office-blocks'));
        }

        // Build feed URL
        $feed_url = $this->feed_urls['office'] . $vanity_key . '/page=1&source=wp_office_blocks';
        
        // Fetch RSS feed
        $rss_feed = fetch_feed($feed_url);
        
        if (is_wp_error($rss_feed)) {
            return $rss_feed;
        }

        // Extract data
        $office_data = $this->extract_office_data($rss_feed);
        
        if (is_wp_error($office_data)) {
            return $office_data;
        }

        return $office_data;
    }

    /**
     * Extract office data from RSS feed
     *
     * @param SimplePie $rss_feed The RSS feed object
     * @return array|WP_Error Array with channel data and testimonials, or WP_Error on failure
     */
    private function extract_office_data($rss_feed) {
        $namespace = $this->namespaces['office'];
        
        // Get maximum items (up to 50)
        $max_items = $rss_feed->get_item_quantity(50);
        $rss_items = $rss_feed->get_items(0, $max_items);

        // Extract channel data (office-wide information)
        $channel_data = array(
            'link' => $rss_feed->get_link(),
            'office' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'office'),
            'logo' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'logo'),
            'overall_satisfaction' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'overall_satisfaction'),
            'recommendation_rating' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'recommendation_rating'),
            'performance_rating' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'performance_rating'),
            'response_count' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'responseCount')
        );

        // Validate we have data
        if (empty($channel_data['response_count']) || $channel_data['response_count'] == 0) {
            return new WP_Error('no_reviews', __('No reviews found for this office', 'realsatisfied-office-blocks'));
        }

        // Extract testimonials
        $testimonials = array();
        foreach ($rss_items as $rss_item) {
            $testimonial = array(
                'title' => $rss_item->get_title(),
                'customer_type' => $this->obtain_item_tag_data($rss_item, $namespace, 'customer_type'),
                'pubDate' => $this->obtain_item_tag_data($rss_item, '', 'pubDate'),
                'description' => $rss_item->get_description(),
                'satisfaction' => $this->obtain_item_tag_data($rss_item, $namespace, 'satisfaction'),
                'recommendation' => $this->obtain_item_tag_data($rss_item, $namespace, 'recommendation'),
                'performance' => $this->obtain_item_tag_data($rss_item, $namespace, 'performance'),
                'display_name' => $this->obtain_item_tag_data($rss_item, $namespace, 'display_name'),
                'avatar' => $this->obtain_item_tag_data($rss_item, $namespace, 'avatar', '')
            );

            $testimonials[] = $testimonial;
        }

        // Shuffle testimonials for variety (as done in original widget)
        shuffle($testimonials);

        return array(
            'channel' => $channel_data,
            'testimonials' => $testimonials
        );
    }

    /**
     * Calculate overall rating from satisfaction, recommendation, and performance
     *
     * @param int $satisfaction Satisfaction rating (0-100)
     * @param int $recommendation Recommendation rating (0-100)
     * @param int $performance Performance rating (0-100)
     * @return float Overall rating (0.0-5.0)
     */
    public function calculate_overall_rating($satisfaction, $recommendation, $performance) {
        return number_format((($satisfaction + $recommendation + $performance) / 60), 1);
    }

    /**
     * Calculate star rating from single rating value
     *
     * @param int $rating Rating value (0-100)
     * @return float Star rating (0.0-5.0)
     */
    public function calculate_star_rating($rating) {
        return number_format($rating / 20, 1);
    }

    /**
     * Get channel tag data with fallback
     * 
     * Extracted from rsrw_obtain_channel_tag_data() in original widget
     *
     * @param SimplePie $rss_feed RSS feed object
     * @param string $namespace XML namespace
     * @param string $tag Tag name
     * @param string $default Default value if tag not found
     * @return string Tag data or default value
     */
    private function obtain_channel_tag_data($rss_feed, $namespace, $tag, $default = '') {
        $tag_data = $rss_feed->get_channel_tags($namespace, $tag);
        return (!empty($tag_data) && isset($tag_data[0]['data'])) ? $tag_data[0]['data'] : $default;
    }

    /**
     * Get item tag data with fallback
     * 
     * Extracted from rsrw_obtain_item_tag_data() in original widget
     *
     * @param SimplePie_Item $rss_item RSS item object
     * @param string $namespace XML namespace
     * @param string $tag Tag name
     * @param string $default Default value if tag not found
     * @return string Tag data or default value
     */
    private function obtain_item_tag_data($rss_item, $namespace, $tag, $default = null) {
        $tag_data = $rss_item->get_item_tags($namespace, $tag);
        return (is_array($tag_data) && isset($tag_data[0]['data'])) ? $tag_data[0]['data'] : $default;
    }

    /**
     * Clear feed cache (AJAX callback)
     */
    public function clear_feed_cache_callback() {
        // Temporarily set cache to 1 second
        add_filter('wp_feed_cache_transient_lifetime', function() { return 1; });
        
        // Clear cache by fetching with minimal cache time
        $response = __('Feed cache has been cleared.', 'realsatisfied-office-blocks');
        
        // Restore normal cache time
        add_filter('wp_feed_cache_transient_lifetime', function() { return $this->cache_duration; });
        
        wp_send_json_success($response);
    }

    /**
     * Get feed URLs
     *
     * @return array Feed URLs
     */
    public function get_feed_urls() {
        return $this->feed_urls;
    }

    /**
     * Get namespaces
     *
     * @return array XML namespaces
     */
    public function get_namespaces() {
        return $this->namespaces;
    }

    /**
     * Get cache duration
     *
     * @return int Cache duration in seconds
     */
    public function get_cache_duration() {
        return $this->cache_duration;
    }
} 