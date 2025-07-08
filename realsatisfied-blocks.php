<?php
/**
 * Plugin Name: RealSatisfied Blocks
 * Description: Comprehensive Gutenberg blocks for RealSatisfied office and agent data - ratings, testimonials, stats, and more
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
        
        // Check for required plugin
        add_action('admin_notices', array($this, 'check_required_plugin'));
        
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
        require_once RSOB_PLUGIN_PATH . 'blocks/office-stats/office-stats.php';
        require_once RSOB_PLUGIN_PATH . 'blocks/office-agents/office-agents.php';
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

        // Initialize blocks
        if (class_exists('RealSatisfied_Office_Ratings_Block')) {
            new RealSatisfied_Office_Ratings_Block();
        }
        
        if (class_exists('RealSatisfied_Office_Testimonials_Block')) {
            new RealSatisfied_Office_Testimonials_Block();
        }
        
        if (class_exists('RealSatisfied_Agent_Testimonials_Block')) {
            new RealSatisfied_Agent_Testimonials_Block();
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
     * Check if required plugin is active
     */
    public function check_required_plugin() {
        if (!class_exists('RSRW_Real_Satisfied_Review_Widget')) {
            $message = sprintf(
                __('RealSatisfied Blocks requires the %s plugin to be installed and activated.', 'realsatisfied-blocks'),
                '<strong>RealSatisfied Review Widget</strong>'
            );
            
            echo '<div class="notice notice-error"><p>' . wp_kses_post($message) . '</p></div>';
        }
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

        // Only enqueue testimonials frontend JS if testimonials blocks are present and have pagination
        global $post;
        if ($post && (has_block('realsatisfied-blocks/office-testimonials', $post) || 
                     has_block('realsatisfied-blocks/agent-testimonials', $post))) {
            
            $blocks = parse_blocks($post->post_content);
            $needs_office_js = false;
            $needs_agent_js = false;
            
            foreach ($blocks as $block) {
                if ($block['blockName'] === 'realsatisfied-blocks/office-testimonials' && 
                    isset($block['attrs']['enablePagination']) && 
                    $block['attrs']['enablePagination'] === true) {
                    $needs_office_js = true;
                }
                
                if ($block['blockName'] === 'realsatisfied-blocks/agent-testimonials' && 
                    isset($block['attrs']['enablePagination']) && 
                    $block['attrs']['enablePagination'] === true) {
                    $needs_agent_js = true;
                }
            }
            
            if ($needs_office_js) {
                wp_enqueue_script(
                    'realsatisfied-office-testimonials-frontend',
                    RSOB_PLUGIN_URL . 'blocks/office-testimonials/office-testimonials-frontend.js',
                    array('jquery'),
                    RSOB_PLUGIN_VERSION,
                    true
                );
            }
            
            if ($needs_agent_js) {
                wp_enqueue_script(
                    'realsatisfied-agent-testimonials-frontend',
                    RSOB_PLUGIN_URL . 'blocks/agent-testimonials/agent-testimonials-frontend.js',
                    array('jquery'),
                    RSOB_PLUGIN_VERSION,
                    true
                );
            }
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

        // Check for required plugin
        if (!class_exists('RSRW_Real_Satisfied_Review_Widget')) {
            deactivate_plugins(RSOB_PLUGIN_BASENAME);
            wp_die(__('RealSatisfied Blocks requires the RealSatisfied Review Widget plugin.', 'realsatisfied-blocks'));
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