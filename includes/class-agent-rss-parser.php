<?php
/**
 * RealSatisfied Agent RSS Parser
 * 
 * Standalone parser for agent RSS feeds only
 * Completely separate from office functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RealSatisfied Agent RSS Parser Class
 */
class RealSatisfied_Agent_RSS_Parser {
    
    /**
     * Plugin instance
     *
     * @var RealSatisfied_Agent_RSS_Parser
     */
    private static $instance = null;

    /**
     * RSS feed cache duration (12 hours)
     *
     * @var int
     */
    private $cache_duration = 43200;

    /**
     * Agent RSS feed URL
     *
     * @var string
     */
    private $agent_feed_url = 'https://rss.realsatisfied.com/rss/v3/agent/detailed/';

    /**
     * Agent XML namespace
     *
     * @var string
     */
    private $agent_namespace = 'https://rss.realsatisfied.com/ns/realsatisfied/';

    /**
     * Get plugin instance
     *
     * @return RealSatisfied_Agent_RSS_Parser
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
        add_action('wp_ajax_rsab_clear_agent_cache', array($this, 'clear_agent_cache_callback'));
        add_action('wp_ajax_nopriv_rsab_clear_agent_cache', array($this, 'clear_agent_cache_callback'));
    }

    /**
     * Fetch and parse RSS feed for agent
     *
     * @param string $vanity_key The agent vanity key
     * @return array|WP_Error Array with channel data and testimonials, or WP_Error on failure
     */
    public function fetch_agent_data($vanity_key) {
        if (empty($vanity_key)) {
            return new WP_Error('missing_vanity_key', __('No agent vanity key provided', 'realsatisfied-blocks'));
        }

        // Build feed URL using original widget pattern
        $feed_url = $this->agent_feed_url . $vanity_key . '/page=1&source=wp_widget';
        
        // Fetch RSS feed
        $rss_feed = fetch_feed($feed_url);
        
        if (is_wp_error($rss_feed)) {
            return $rss_feed;
        }

        // Extract agent data
        $agent_data = $this->extract_agent_data($rss_feed);
        
        if (is_wp_error($agent_data)) {
            return $agent_data;
        }

        return $agent_data;
    }

    /**
     * Extract agent data from RSS feed
     *
     * @param SimplePie $rss_feed The RSS feed object
     * @return array|WP_Error Array with channel data and testimonials, or WP_Error on failure
     */
    private function extract_agent_data($rss_feed) {
        $namespace = $this->agent_namespace;
        
        // Get maximum items (up to 50, matching original widget)
        $max_items = $rss_feed->get_item_quantity(50);
        $rss_items = $rss_feed->get_items(0, $max_items);

        // Extract channel data (agent information) - matching original widget structure
        $channel_data = array(
            'link' => $rss_feed->get_link(),
            'display_name' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'display_name'),
            'avatar' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'avatar'),
            'office' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'office'),
            'overall_satisfaction' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'overall_satisfaction'),
            'recommendation_rating' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'recommendation_rating'),
            'performance_rating' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'performance_rating'),
            'response_count' => $this->obtain_channel_tag_data($rss_feed, $namespace, 'responseCount')
        );

        // Validate we have data
        if (empty($channel_data['response_count']) || $channel_data['response_count'] == 0) {
            return new WP_Error('no_reviews', __('No reviews found for this agent', 'realsatisfied-blocks'));
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
     * Uses original RSS feed calculation method
     *
     * @param int $satisfaction Satisfaction rating (0-100)
     * @param int $recommendation Recommendation rating (0-100)
     * @param int $performance Performance rating (0-100)
     * @return float Overall rating (0.0-5.0)
     */
    public function calculate_overall_rating($satisfaction, $recommendation, $performance) {
        // Use original RSS feed calculation: sum all ratings and divide by 60
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
     * Clear agent feed cache (AJAX callback)
     */
    public function clear_agent_cache_callback() {
        // Temporarily set cache to 1 second
        add_filter('wp_feed_cache_transient_lifetime', function() { return 1; });
        
        // Clear cache by fetching with minimal cache time
        $response = __('Agent feed cache has been cleared.', 'realsatisfied-blocks');
        
        // Restore normal cache time
        add_filter('wp_feed_cache_transient_lifetime', function() { return $this->cache_duration; });
        
        wp_send_json_success($response);
    }

    /**
     * Get agent feed URL
     *
     * @return string Agent feed URL
     */
    public function get_agent_feed_url() {
        return $this->agent_feed_url;
    }

    /**
     * Get agent namespace
     *
     * @return string Agent XML namespace
     */
    public function get_agent_namespace() {
        return $this->agent_namespace;
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