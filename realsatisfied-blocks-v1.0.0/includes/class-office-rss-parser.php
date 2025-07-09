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
            return new WP_Error('missing_vanity_key', __('No vanity key provided', 'realsatisfied-blocks'));
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
            return new WP_Error('no_reviews', __('No reviews found for this office', 'realsatisfied-blocks'));
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

        // Extract unique agents from testimonials
        $agents = $this->extract_agents_from_testimonials($testimonials);

        return array(
            'channel' => $channel_data,
            'testimonials' => $testimonials,
            'agents' => $agents
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
     * Clear feed cache (AJAX callback)
     */
    public function clear_feed_cache_callback() {
        // Temporarily set cache to 1 second
        add_filter('wp_feed_cache_transient_lifetime', function() { return 1; });
        
        // Clear cache by fetching with minimal cache time
        $response = __('Feed cache has been cleared.', 'realsatisfied-blocks');
        
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

    /**
     * Extract unique agents from testimonials data
     *
     * @param array $testimonials Array of testimonials
     * @return array Array of unique agents with aggregated data
     */
    private function extract_agents_from_testimonials($testimonials) {
        $agents = array();
        $agent_stats = array();
        
        // Process each testimonial to extract agent data
        foreach ($testimonials as $testimonial) {
            $display_name = $testimonial['display_name'] ?? '';
            $avatar = $testimonial['avatar'] ?? '';
            
            if (empty($display_name)) {
                continue; // Skip testimonials without agent name
            }
            
            // Initialize agent if not seen before
            if (!isset($agent_stats[$display_name])) {
                $agent_stats[$display_name] = array(
                    'display_name' => $display_name,
                    'avatar' => $avatar,
                    'first_name' => $this->extract_first_name($display_name),
                    'last_name' => $this->extract_last_name($display_name),
                    'title' => '', // Not available from testimonials
                    'email' => '', // Not available from testimonials
                    'phone' => '', // Not available from testimonials
                    'mobile' => '', // Not available from testimonials
                    'vanity_id' => $this->generate_vanity_id($display_name),
                    'review_count' => 0,
                    'total_satisfaction' => 0,
                    'total_recommendation' => 0,
                    'total_performance' => 0,
                    'valid_ratings_count' => 0
                );
            }
            
            // Update review count
            $agent_stats[$display_name]['review_count']++;
            
            // Aggregate ratings if available
            $satisfaction = intval($testimonial['satisfaction'] ?? 0);
            $recommendation = intval($testimonial['recommendation'] ?? 0);
            $performance = intval($testimonial['performance'] ?? 0);
            
            if ($satisfaction > 0 || $recommendation > 0 || $performance > 0) {
                $agent_stats[$display_name]['total_satisfaction'] += $satisfaction;
                $agent_stats[$display_name]['total_recommendation'] += $recommendation;
                $agent_stats[$display_name]['total_performance'] += $performance;
                $agent_stats[$display_name]['valid_ratings_count']++;
            }
            
            // Use the latest avatar if available
            if (!empty($avatar)) {
                $agent_stats[$display_name]['avatar'] = $avatar;
            }
        }
        
        // Calculate overall ratings for each agent
        foreach ($agent_stats as $agent_name => $stats) {
            $overall_rating = 0;
            
            if ($stats['valid_ratings_count'] > 0) {
                $avg_satisfaction = $stats['total_satisfaction'] / $stats['valid_ratings_count'];
                $avg_recommendation = $stats['total_recommendation'] / $stats['valid_ratings_count'];
                $avg_performance = $stats['total_performance'] / $stats['valid_ratings_count'];
                
                // Calculate overall rating using the same method as the main office calculation
                $overall_rating = $this->calculate_overall_rating($avg_satisfaction, $avg_recommendation, $avg_performance);
            }
            
            $agents[] = array(
                'display_name' => $stats['display_name'],
                'first_name' => $stats['first_name'],
                'last_name' => $stats['last_name'],
                'title' => $stats['title'],
                'email' => $stats['email'],
                'phone' => $stats['phone'],
                'mobile' => $stats['mobile'],
                'avatar' => $stats['avatar'],
                'vanity_id' => $stats['vanity_id'],
                'overall_rating' => $overall_rating,
                'review_count' => $stats['review_count']
            );
        }
        
        return $agents;
    }
    
    /**
     * Extract first name from display name
     *
     * @param string $display_name Full display name
     * @return string First name
     */
    private function extract_first_name($display_name) {
        $parts = explode(' ', trim($display_name));
        return $parts[0] ?? '';
    }
    
    /**
     * Extract last name from display name
     *
     * @param string $display_name Full display name
     * @return string Last name
     */
    private function extract_last_name($display_name) {
        $parts = explode(' ', trim($display_name));
        if (count($parts) > 1) {
            return end($parts);
        }
        return '';
    }
    
    /**
     * Generate vanity ID from display name
     *
     * @param string $display_name Full display name
     * @return string Generated vanity ID
     */
    private function generate_vanity_id($display_name) {
        // Simple vanity ID generation: lowercase, replace spaces with hyphens, remove special characters
        $vanity_id = strtolower($display_name);
        $vanity_id = preg_replace('/[^a-z0-9\s-]/', '', $vanity_id);
        $vanity_id = preg_replace('/\s+/', '-', $vanity_id);
        $vanity_id = trim($vanity_id, '-');
        
        return $vanity_id;
    }
}