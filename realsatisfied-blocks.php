<?php
/**
 * Plugin Name: RealSatisfied Blocks
 * Description: Standalone Gutenberg blocks for RealSatisfied office and agent data - ratings and testimonials with WordPress Interactivity API. No dependencies required.
 * Version: 1.0.0
 * Author: RealSatisfied
 * Text Domain: realsatisfied-blocks
 * Requires at least: 5.4
 * Tested up to: 6.7
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RSOB_PLUGIN_VERSION', '1.0.0');
define('RSOB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RSOB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RSOB_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main RealSatisfied Blocks class
 */
class RealSatisfied_Blocks {
    
    /**
     * Plugin instance
     *
     * @var RealSatisfied_Blocks
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return RealSatisfied_Blocks
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
        $this->load_dependencies();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load includes
        require_once RSOB_PLUGIN_PATH . 'includes/class-office-rss-parser.php';
        require_once RSOB_PLUGIN_PATH . 'includes/class-agent-rss-parser.php';
        require_once RSOB_PLUGIN_PATH . 'includes/class-custom-fields.php';
        require_once RSOB_PLUGIN_PATH . 'includes/class-widget-compatibility.php';
        
        // Load blocks
        require_once RSOB_PLUGIN_PATH . 'blocks/office-ratings/office-ratings.php';
        require_once RSOB_PLUGIN_PATH . 'blocks/office-testimonials/office-testimonials.php';
        require_once RSOB_PLUGIN_PATH . 'blocks/agent-testimonials/agent-testimonials.php';
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize components
        if (class_exists('RealSatisfied_Office_RSS_Parser')) {
            RealSatisfied_Office_RSS_Parser::get_instance();
        }
        
        if (class_exists('RealSatisfied_Agent_RSS_Parser')) {
            RealSatisfied_Agent_RSS_Parser::get_instance();
        }
        
        if (class_exists('RealSatisfied_Custom_Fields')) {
            RealSatisfied_Custom_Fields::get_instance();
        }
        
        if (class_exists('RealSatisfied_Widget_Compatibility')) {
            RealSatisfied_Widget_Compatibility::get_instance();
        }

        // Initialize blocks - call register_block directly since we're already in init hook
        if (class_exists('RealSatisfied_Office_Ratings_Block')) {
            $office_ratings_block = new RealSatisfied_Office_Ratings_Block();
            $office_ratings_block->register_block();
        }
        
        if (class_exists('RealSatisfied_Office_Testimonials_Block')) {
            $office_testimonials_block = new RealSatisfied_Office_Testimonials_Block();
            $office_testimonials_block->register_block();
        }
        
        if (class_exists('RealSatisfied_Agent_Testimonials_Block')) {
            $agent_testimonials_block = new RealSatisfied_Agent_Testimonials_Block();
            $agent_testimonials_block->register_block();
        }
        
        // Register blocks
        $this->register_blocks();
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'realsatisfied-blocks',
            false,
            dirname(RSOB_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Register blocks
     */
    private function register_blocks() {
        // Blocks will be registered by their individual classes
        // This method serves as a central registration point if needed
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'realsatisfied-blocks',
            RSOB_PLUGIN_URL . 'assets/realsatisfied-blocks.css',
            array(),
            RSOB_PLUGIN_VERSION
        );

        // Office testimonials frontend script is now handled by Interactivity API modules below
        
        // Load agent testimonials script - improved detection for custom post types and templates
        $should_load_agent_script = false;
        
        // Check for block in post content
        if (has_block('realsatisfied-blocks/agent-testimonials')) {
            $should_load_agent_script = true;
        }
        
        // Additional checks for custom post types and templates where has_block() might fail
        if (!$should_load_agent_script) {
            global $post;
            if ($post) {
                // Check if this is a person/agent post type or template that might use the block
                if ($post->post_type === 'person' || 
                    get_post_meta($post->ID, 'realsatified-agent-vanity', true) ||
                    (function_exists('get_field') && get_field('realsatified-agent-vanity', $post->ID))) {
                    $should_load_agent_script = true;
                }
            }
        }
        
        if ($should_load_agent_script) {
            // Load Interactivity API module for agent testimonials
            wp_enqueue_script_module(
                'realsatisfied-agent-testimonials-view',
                RSOB_PLUGIN_URL . 'blocks/agent-testimonials/view.js',
                array('@wordpress/interactivity'),
                RSOB_PLUGIN_VERSION
            );
        }
        
        // Check for office testimonials block
        $should_load_office_script = false;
        
        // Check for blocks in post content
        if (has_block('realsatisfied-blocks/office-testimonials')) {
            $should_load_office_script = true;
        }
        
        // Additional checks for custom post types and templates where has_block() might fail
        if (!$should_load_office_script) {
            global $post;
            if ($post) {
                // Check if this is an office/company post type or template that might use the blocks
                if ($post->post_type === 'office' || $post->post_type === 'company' ||
                    get_post_meta($post->ID, 'realsatisfied_feed', true) ||
                    (function_exists('get_field') && get_field('realsatisfied_feed', $post->ID))) {
                    $should_load_office_script = true;
                }
            }
        }
        
        if ($should_load_office_script) {
            // Load Interactivity API module for office testimonials
            wp_enqueue_script_module(
                'realsatisfied-office-testimonials-view',
                RSOB_PLUGIN_URL . 'blocks/office-testimonials/view.js',
                array('@wordpress/interactivity'),
                RSOB_PLUGIN_VERSION
            );
        }
    }

    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        wp_enqueue_style(
            'realsatisfied-blocks-editor',
            RSOB_PLUGIN_URL . 'assets/realsatisfied-blocks.css',
            array(),
            RSOB_PLUGIN_VERSION
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.4', '<')) {
            deactivate_plugins(RSOB_PLUGIN_BASENAME);
            wp_die(__('RealSatisfied Blocks requires WordPress 5.4 or higher.', 'realsatisfied-blocks'));
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
}

// Initialize the plugin
RealSatisfied_Blocks::get_instance();