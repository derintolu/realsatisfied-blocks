<?php
/**
 * RealSatisfied Widget Compatibility
 * 
 * Ensures our office blocks work alongside the existing widget
 * Handles asset loading conflicts and provides compatibility utilities
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RealSatisfied Widget Compatibility Class
 */
class RealSatisfied_Widget_Compatibility {
    
    /**
     * Plugin instance
     *
     * @var RealSatisfied_Widget_Compatibility
     */
    private static $instance = null;

    /**
     * Whether widget plugin is active
     *
     * @var bool
     */
    private $widget_active = false;

    /**
     * Get plugin instance
     *
     * @return RealSatisfied_Widget_Compatibility
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
        $this->check_widget_plugin();
        $this->init_hooks();
    }

    /**
     * Check if widget plugin is active
     */
    private function check_widget_plugin() {
        $this->widget_active = class_exists('RSRW_Real_Satisfied_Review_Widget');
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Handle asset loading conflicts
        add_action('wp_enqueue_scripts', array($this, 'manage_asset_conflicts'), 5);
        
        // Add compatibility notices
        add_action('admin_notices', array($this, 'compatibility_notices'));
        
        // Handle FlexSlider conflicts
        add_action('wp_enqueue_scripts', array($this, 'handle_flexslider_conflicts'), 15);
    }

    /**
     * Check if widget plugin is active
     *
     * @return bool True if widget plugin is active
     */
    public function is_widget_active() {
        return $this->widget_active;
    }

    /**
     * Get widget plugin version
     *
     * @return string|false Widget plugin version or false if not active
     */
    public function get_widget_version() {
        if (!$this->widget_active) {
            return false;
        }

        // Try to get version from plugin data
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_file = WP_PLUGIN_DIR . '/realsatisfied-review-widget/realsatisfied.php';
        if (file_exists($plugin_file)) {
            $plugin_data = get_plugin_data($plugin_file);
            return $plugin_data['Version'];
        }

        return false;
    }

    /**
     * Manage asset loading conflicts
     */
    public function manage_asset_conflicts() {
        if (!$this->widget_active) {
            return;
        }

        // Check if we're on a page with our blocks
        if (!$this->has_office_blocks()) {
            return;
        }

        // Prevent duplicate FlexSlider loading
        // The widget already loads FlexSlider, so we can reuse it
        add_filter('rsob_load_flexslider', '__return_false');
        
        // Prevent duplicate Bootstrap loading
        add_filter('rsob_load_bootstrap', '__return_false');
    }

    /**
     * Handle FlexSlider conflicts
     */
    public function handle_flexslider_conflicts() {
        if (!$this->widget_active || !$this->has_office_blocks()) {
            return;
        }

        // Ensure FlexSlider is available for our blocks
        // If widget hasn't loaded it, load it ourselves
        if (!wp_script_is('flexslider', 'enqueued')) {
            wp_enqueue_script(
                'flexslider-office-blocks',
                RSOB_PLUGIN_URL . 'assets/flexslider/jquery.flexslider.min.js',
                array('jquery'),
                '2.7.2',
                true
            );
        }

        if (!wp_style_is('flexslider-css', 'enqueued')) {
            wp_enqueue_style(
                'flexslider-css-office-blocks',
                RSOB_PLUGIN_URL . 'assets/flexslider/flexslider.css',
                array(),
                '2.7.2'
            );
        }
    }

    /**
     * Check if current page has office blocks
     *
     * @return bool True if page has office blocks
     */
    private function has_office_blocks() {
        global $post;
        
        if (!$post) {
            return false;
        }

        // Check for our block patterns in post content
        $blocks = array(
            'realsatisfied-blocks/office-ratings',
            'realsatisfied-blocks/office-testimonials',
            'realsatisfied-blocks/agent-testimonials'
        );

        foreach ($blocks as $block) {
            if (has_block($block, $post)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Display compatibility notices
     */
    public function compatibility_notices() {
        if (!$this->widget_active) {
            return;
        }

        $version = $this->get_widget_version();
        if ($version && version_compare($version, '2.0.0', '<')) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>RealSatisfied Office Blocks:</strong> ';
            echo esc_html__(
                'For best compatibility, please update the RealSatisfied Review Widget to version 2.0.0 or higher.',
                'realsatisfied-blocks'
            );
            echo '</p>';
            echo '</div>';
        }
    }

    /**
     * Get widget utility functions for reuse
     * 
     * Provides access to widget utility functions if available
     *
     * @return array Available utility functions
     */
    public function get_widget_utilities() {
        $utilities = array();

        if ($this->widget_active) {
            // Check what utility functions are available
            if (function_exists('rsrw_rating_star_score')) {
                $utilities['rating_star_score'] = 'rsrw_rating_star_score';
            }
            
            if (function_exists('rsrw_limit_words')) {
                $utilities['limit_words'] = 'rsrw_limit_words';
            }
            
            if (function_exists('rsrw_obtain_single_gold_star')) {
                $utilities['single_gold_star'] = 'rsrw_obtain_single_gold_star';
            }
        }

        return $utilities;
    }

    /**
     * Get widget CSS classes for consistency
     *
     * @return array Widget CSS classes
     */
    public function get_widget_css_classes() {
        return array(
            'container' => 'widget_real_satisfied_review_widget',
            'header' => 'header',
            'ratings' => 'profile-ratings',
            'star_container' => 'overall_star_rating',
            'testimonial' => 'testimonial',
            'slider' => 'rsw-flexslider',
            'slides' => 'rs-slides',
            'footer' => 'footer'
        );
    }

    /**
     * Check if we can reuse widget assets
     *
     * @param string $asset_type Asset type (css, js, images)
     * @return bool True if can reuse
     */
    public function can_reuse_widget_assets($asset_type = 'css') {
        if (!$this->widget_active) {
            return false;
        }

        switch ($asset_type) {
            case 'css':
                return wp_style_is('rsw-styles', 'enqueued');
            case 'js':
                return wp_script_is('realsatisfied-script', 'enqueued');
            case 'flexslider':
                return wp_script_is('flexslider', 'enqueued');
            default:
                return false;
        }
    }

    /**
     * Get widget plugin path
     *
     * @return string|false Widget plugin path or false if not active
     */
    public function get_widget_plugin_path() {
        if (!$this->widget_active) {
            return false;
        }

        return WP_PLUGIN_DIR . '/realsatisfied-review-widget/';
    }

    /**
     * Get widget plugin URL
     *
     * @return string|false Widget plugin URL or false if not active
     */
    public function get_widget_plugin_url() {
        if (!$this->widget_active) {
            return false;
        }

        return plugins_url('/', WP_PLUGIN_DIR . '/realsatisfied-review-widget/realsatisfied.php');
    }
} 