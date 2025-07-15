<?php
/**
 * RealSatisfied RSS Cache Manager
 * 
 * Automatically prefetches and refreshes RSS caches to eliminate expiration delays.
 * Uses WordPress Cron to refresh caches every 11 hours, ensuring they never expire.
 * 
 * @since 1.2.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RealSatisfied RSS Cache Manager Class
 */
class RealSatisfied_RSS_Cache_Manager {
    
    /**
     * Plugin instance
     *
     * @var RealSatisfied_RSS_Cache_Manager
     */
    private static $instance = null;

    /**
     * Cache refresh interval (11 hours in seconds)
     * Set to 11 hours to refresh before 12-hour expiration
     *
     * @var int
     */
    private $refresh_interval = 39600; // 11 hours

    /**
     * WordPress Cron hook names
     *
     * @var array
     */
    private $cron_hooks = array(
        'realsatisfied_refresh_company_cache',
        'realsatisfied_refresh_office_cache',
        'realsatisfied_refresh_agent_cache'
    );

    /**
     * Get plugin instance
     *
     * @return RealSatisfied_RSS_Cache_Manager
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
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register cron hooks
        foreach ($this->cron_hooks as $hook) {
            add_action($hook, array($this, 'execute_cache_refresh'));
        }

        // Add custom cron interval early
        add_filter('cron_schedules', array($this, 'add_cron_interval'));

        // Schedule initial cron jobs on plugin activation and init
        add_action('init', array($this, 'schedule_cron_jobs'));
        add_action('wp_loaded', array($this, 'schedule_cron_jobs'));
        
        // Clean up cron jobs on plugin deactivation
        register_deactivation_hook(RSOB_PLUGIN_BASENAME, array($this, 'unschedule_cron_jobs'));
        
        // Add admin action to manually trigger cache refresh
        add_action('wp_ajax_refresh_realsatisfied_cache', array($this, 'manual_cache_refresh'));
        
        // Add admin notice for cache status
        add_action('admin_notices', array($this, 'display_cache_status'));
        
        // Force schedule on admin page load if not scheduled
        add_action('admin_menu', array($this, 'ensure_cron_scheduled'));
    }

    /**
     * Schedule WordPress Cron jobs for cache refresh
     */
    public function schedule_cron_jobs() {
        foreach ($this->cron_hooks as $hook) {
            if (!wp_next_scheduled($hook)) {
                // Schedule with random offset to spread load
                $offset = wp_rand(0, 3600); // Random offset up to 1 hour
                wp_schedule_event(time() + $offset, 'realsatisfied_cache_interval', $hook);
            }
        }
        
        // Add custom cron interval if not exists
        add_filter('cron_schedules', array($this, 'add_cron_interval'));
    }

    /**
     * Add custom cron interval
     *
     * @param array $schedules Existing cron schedules
     * @return array Modified cron schedules
     */
    public function add_cron_interval($schedules) {
        $schedules['realsatisfied_cache_interval'] = array(
            'interval' => $this->refresh_interval,
            'display'  => __('Every 11 Hours (RealSatisfied Cache)', 'realsatisfied-blocks')
        );
        return $schedules;
    }

    /**
     * Unschedule all cron jobs
     */
    public function unschedule_cron_jobs() {
        foreach ($this->cron_hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }

    /**
     * Execute cache refresh based on hook
     *
     * @param string $hook The cron hook being executed
     */
    public function execute_cache_refresh($hook = null) {
        // Determine current hook if not provided
        if (!$hook) {
            $hook = current_action();
        }
        
        $start_time = microtime(true);
        $refreshed_count = 0;
        
        try {
            switch ($hook) {
                case 'realsatisfied_refresh_company_cache':
                    $refreshed_count = $this->refresh_company_caches();
                    break;
                    
                case 'realsatisfied_refresh_office_cache':
                    $refreshed_count = $this->refresh_office_caches();
                    break;
                    
                case 'realsatisfied_refresh_agent_cache':
                    $refreshed_count = $this->refresh_agent_caches();
                    break;
                    
                default:
                    return;
            }
            
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            
            // Update last refresh timestamp
            update_option("realsatisfied_last_refresh_{$hook}", array(
                'timestamp' => time(),
                'count' => $refreshed_count,
                'execution_time' => $execution_time
            ));
            
        } catch (Exception $e) {
            // Log errors but don't expose them
            error_log("RealSatisfied Cache Manager: Error in {$hook} - " . $e->getMessage());
        }
    }

    /**
     * Refresh company RSS caches
     *
     * @return int Number of caches refreshed
     */
    private function refresh_company_caches() {
        if (!class_exists('RealSatisfied_Company_RSS_Parser')) {
            return 0;
        }
        
        $parser = RealSatisfied_Company_RSS_Parser::get_instance();
        $refreshed = 0;
        
        // Get list of company IDs that have been cached
        $company_ids = $this->get_cached_company_ids();
        
        foreach ($company_ids as $company_id) {
            try {
                // Force refresh by clearing cache first
                $cache_key = 'rsob_company_' . md5($company_id . serialize(array()));
                delete_transient($cache_key);
                
                // Fetch fresh data (this will create new cache)
                $options = array(
                    'limit' => 50,
                    'preserve_order' => false
                );
                
                $data = $parser->fetch_company_data($company_id, $options);
                
                if (!is_wp_error($data) && !empty($data['testimonials'])) {
                    $refreshed++;
                }
                
                // Small delay to prevent overwhelming the RSS server
                usleep(250000); // 0.25 seconds
                
            } catch (Exception $e) {
                error_log("RealSatisfied Cache Manager: Error refreshing company {$company_id} - " . $e->getMessage());
            }
        }
        
        return $refreshed;
    }

    /**
     * Refresh office RSS caches
     *
     * @return int Number of caches refreshed
     */
    private function refresh_office_caches() {
        if (!class_exists('RealSatisfied_Office_RSS_Parser')) {
            return 0;
        }
        
        $parser = RealSatisfied_Office_RSS_Parser::get_instance();
        $refreshed = 0;
        
        // Get list of office vanity keys that have been cached
        $vanity_keys = $this->get_cached_office_keys();
        
        foreach ($vanity_keys as $vanity_key) {
            try {
                // Office parser uses WordPress's fetch_feed() which has its own cache
                // We need to clear WordPress's feed cache
                $feed_url = 'https://rss.realsatisfied.com/rss/office/' . $vanity_key . '/page=1&source=wp_office_blocks';
                $cache_key = 'feed_' . md5($feed_url);
                delete_transient($cache_key);
                
                // Also clear any other potential cache variants
                wp_cache_delete($cache_key, 'default');
                
                // Fetch fresh data (this will create new cache)
                $data = $parser->fetch_office_data($vanity_key);
                
                if (!is_wp_error($data) && !empty($data['testimonials'])) {
                    $refreshed++;
                }
                
                // Small delay to prevent overwhelming the RSS server
                usleep(250000); // 0.25 seconds
                
            } catch (Exception $e) {
                error_log("RealSatisfied Cache Manager: Error refreshing office {$vanity_key} - " . $e->getMessage());
            }
        }
        
        return $refreshed;
    }

    /**
     * Refresh agent RSS caches
     *
     * @return int Number of caches refreshed
     */
    private function refresh_agent_caches() {
        if (!class_exists('RealSatisfied_Agent_RSS_Parser')) {
            return 0;
        }
        
        $parser = RealSatisfied_Agent_RSS_Parser::get_instance();
        $refreshed = 0;
        
        // Get list of agent vanity keys that have been cached
        $vanity_keys = $this->get_cached_agent_keys();
        
        foreach ($vanity_keys as $vanity_key) {
            try {
                // Agent parser also uses WordPress's fetch_feed() which has its own cache
                // We need to clear WordPress's feed cache
                $feed_url = 'https://rss.realsatisfied.com/rss/agent/' . $vanity_key . '/page=1&source=wp_agent_blocks';
                $cache_key = 'feed_' . md5($feed_url);
                delete_transient($cache_key);
                
                // Also clear any other potential cache variants
                wp_cache_delete($cache_key, 'default');
                
                // Fetch fresh data (this will create new cache)
                $data = $parser->fetch_agent_data($vanity_key);
                
                if (!is_wp_error($data) && !empty($data['testimonials'])) {
                    $refreshed++;
                }
                
                // Small delay to prevent overwhelming the RSS server
                usleep(250000); // 0.25 seconds
                
            } catch (Exception $e) {
                error_log("RealSatisfied Cache Manager: Error refreshing agent {$vanity_key} - " . $e->getMessage());
            }
        }
        
        return $refreshed;
    }

    /**
     * Get list of cached company IDs
     *
     * @return array Company IDs that have been cached
     */
    private function get_cached_company_ids() {
        global $wpdb;
        
        // Get all transients that match company pattern
        $results = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_rsob_company_%'",
            ARRAY_A
        );
        
        $company_ids = array();
        foreach ($results as $result) {
            // Extract the MD5 hash from the transient name
            $hash = str_replace('_transient_rsob_company_', '', $result['option_name']);
            
            // Try to find the original company ID from cache metadata
            $meta_key = 'realsatisfied_company_meta_' . $hash;
            $meta = get_option($meta_key);
            
            if ($meta && isset($meta['company_id'])) {
                $company_ids[] = $meta['company_id'];
            }
        }
        
        // Fallback: Add common company IDs if none found
        if (empty($company_ids)) {
            $company_ids = $this->get_default_company_ids();
        }
        
        return array_unique($company_ids);
    }

    /**
     * Get list of cached office vanity keys
     *
     * @return array Office vanity keys that have been cached
     */
    private function get_cached_office_keys() {
        global $wpdb;
        
        // Office parser uses WordPress's fetch_feed() which creates feed_ transients
        // Get all transients that match feed pattern for office URLs
        $results = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_feed_%' 
             AND option_value LIKE '%realsatisfied.com/rss/office%'",
            ARRAY_A
        );
        
        $vanity_keys = array();
        foreach ($results as $result) {
            // Try to extract vanity key from the cached URL
            if (preg_match('/office\/([^\/]+)/', $result['option_value'], $matches)) {
                $vanity_keys[] = $matches[1];
            }
        }
        
        // Fallback: Get keys from ACF fields or post meta
        if (empty($vanity_keys)) {
            $vanity_keys = $this->get_office_keys_from_posts();
        }
        
        return array_unique($vanity_keys);
    }

    /**
     * Get list of cached agent vanity keys
     *
     * @return array Agent vanity keys that have been cached
     */
    private function get_cached_agent_keys() {
        global $wpdb;
        
        // Agent parser uses WordPress's fetch_feed() which creates feed_ transients
        // Get all transients that match feed pattern for agent URLs
        $results = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_feed_%' 
             AND option_value LIKE '%realsatisfied.com/rss/agent%'",
            ARRAY_A
        );
        
        $vanity_keys = array();
        foreach ($results as $result) {
            // Try to extract vanity key from the cached URL
            if (preg_match('/agent\/([^\/]+)/', $result['option_value'], $matches)) {
                $vanity_keys[] = $matches[1];
            }
        }
        
        // Fallback: Get keys from ACF fields or post meta
        if (empty($vanity_keys)) {
            $vanity_keys = $this->get_agent_keys_from_posts();
        }
        
        return array_unique($vanity_keys);
    }

    /**
     * Get default company IDs to prefetch
     *
     * @return array Default company IDs
     */
    private function get_default_company_ids() {
        // Add common company IDs that should always be cached
        return array(
            'c21masters' // Add your company's default ID here
        );
    }

    /**
     * Get office vanity keys from post meta and ACF fields
     *
     * @return array Office vanity keys
     */
    private function get_office_keys_from_posts() {
        global $wpdb;
        
        $vanity_keys = array();
        
        // Get from standard post meta
        $results = $wpdb->get_results(
            "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
             WHERE meta_key = 'realsatisfied_feed' AND meta_value != ''",
            ARRAY_A
        );
        
        foreach ($results as $result) {
            if (!empty($result['meta_value'])) {
                $vanity_keys[] = $result['meta_value'];
            }
        }
        
        // Get from ACF fields if ACF is active
        if (function_exists('get_field')) {
            $acf_results = $wpdb->get_results(
                "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
                 WHERE meta_key LIKE '%realsatisfied_feed%' AND meta_value != ''",
                ARRAY_A
            );
            
            foreach ($acf_results as $result) {
                if (!empty($result['meta_value'])) {
                    $vanity_keys[] = $result['meta_value'];
                }
            }
        }
        
        return array_unique($vanity_keys);
    }

    /**
     * Get agent vanity keys from post meta and ACF fields
     *
     * @return array Agent vanity keys
     */
    private function get_agent_keys_from_posts() {
        global $wpdb;
        
        $vanity_keys = array();
        
        // Get from standard post meta (agent specific fields)
        $results = $wpdb->get_results(
            "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
             WHERE (meta_key = 'agent_realsatisfied_feed' OR meta_key = 'realsatisfied_agent_key') 
             AND meta_value != ''",
            ARRAY_A
        );
        
        foreach ($results as $result) {
            if (!empty($result['meta_value'])) {
                $vanity_keys[] = $result['meta_value'];
            }
        }
        
        return array_unique($vanity_keys);
    }

    /**
     * Manual cache refresh via AJAX
     */
    public function manual_cache_refresh() {
        // Verify user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'realsatisfied-blocks'));
        }
        
        // Verify nonce
        check_ajax_referer('realsatisfied_cache_refresh', 'nonce');
        
        $type = sanitize_text_field($_POST['type']);
        $refreshed = 0;
        
        switch ($type) {
            case 'company':
                $refreshed = $this->refresh_company_caches();
                break;
            case 'office':
                $refreshed = $this->refresh_office_caches();
                break;
            case 'agent':
                $refreshed = $this->refresh_agent_caches();
                break;
            case 'all':
                $refreshed += $this->refresh_company_caches();
                $refreshed += $this->refresh_office_caches();
                $refreshed += $this->refresh_agent_caches();
                break;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d caches refreshed successfully', 'realsatisfied-blocks'), $refreshed),
            'count' => $refreshed
        ));
    }

    /**
     * Display cache status in admin
     */
    public function display_cache_status() {
        $screen = get_current_screen();
        
        // Only show on relevant admin pages
        if (!$screen || !in_array($screen->id, array('dashboard', 'plugins', 'edit-post', 'edit-page'))) {
            return;
        }
        
        // Check if any cron jobs are scheduled
        $scheduled_jobs = 0;
        foreach ($this->cron_hooks as $hook) {
            if (wp_next_scheduled($hook)) {
                $scheduled_jobs++;
            }
        }
        
        if ($scheduled_jobs < count($this->cron_hooks)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>RealSatisfied Cache Manager:</strong> ';
            echo sprintf(__('%d of %d cache refresh jobs are scheduled. ', 'realsatisfied-blocks'), $scheduled_jobs, count($this->cron_hooks));
            echo '<a href="#" onclick="location.reload();">' . __('Refresh page to check status.', 'realsatisfied-blocks') . '</a>';
            echo '</p>';
            echo '</div>';
        }
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function get_cache_stats() {
        $stats = array();
        
        foreach ($this->cron_hooks as $hook) {
            $last_refresh = get_option("realsatisfied_last_refresh_{$hook}");
            $next_scheduled = wp_next_scheduled($hook);
            
            $stats[$hook] = array(
                'last_refresh' => $last_refresh,
                'next_scheduled' => $next_scheduled,
                'is_scheduled' => (bool) $next_scheduled
            );
        }
        
        return $stats;
    }

    /**
     * Ensure cron jobs are scheduled (force scheduling if needed)
     */
    public function ensure_cron_scheduled() {
        $unscheduled_jobs = array();
        
        foreach ($this->cron_hooks as $hook) {
            if (!wp_next_scheduled($hook)) {
                $unscheduled_jobs[] = $hook;
            }
        }
        
        if (!empty($unscheduled_jobs)) {
            // Force schedule these jobs
            foreach ($unscheduled_jobs as $hook) {
                $offset = wp_rand(60, 3600); // Random offset between 1 minute and 1 hour
                $scheduled = wp_schedule_event(time() + $offset, 'realsatisfied_cache_interval', $hook);
            }
        }
    }

    /**
     * Manual scheduling trigger (for admin interface)
     */
    public function force_schedule_all() {
        // Clear existing schedules first
        $this->unschedule_cron_jobs();
        
        // Wait a moment
        usleep(100000); // 0.1 seconds
        
        // Schedule fresh
        foreach ($this->cron_hooks as $hook) {
            $offset = wp_rand(60, 1800); // Random offset between 1 minute and 30 minutes
            wp_schedule_event(time() + $offset, 'realsatisfied_cache_interval', $hook);
        }
        
        return true;
    }
}

// Initialize the cache manager
add_action('plugins_loaded', function() {
    if (class_exists('RealSatisfied_Blocks')) {
        RealSatisfied_RSS_Cache_Manager::get_instance();
    }
});
