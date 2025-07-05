<?php
/**
 * Plugin Name: RealSatisfied Block
 * Description: A Gutenberg block wrapper for the RealSatisfied widget with custom field integration
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class RealSatisfiedBlock {
    
    public function __construct() {
        add_action('init', array($this, 'register_block'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        add_action('wp_ajax_get_custom_fields', array($this, 'get_custom_fields_ajax'));
        add_action('wp_ajax_nopriv_get_custom_fields', array($this, 'get_custom_fields_ajax'));
    }
    
    public function register_block() {
        // Check if RealSatisfied widget exists
        if (!class_exists('RSRW_Real_Satisfied_Review_Widget')) {
            add_action('admin_notices', array($this, 'missing_plugin_notice'));
            return;
        }
        
        register_block_type('realsatisfied/block', array(
            'render_callback' => array($this, 'render_block'),
            'attributes' => array(
                'useCustomField' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'customFieldName' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'manualVanityKey' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'mode' => array(
                    'type' => 'string',
                    'default' => 'Office'
                ),
                'displayPhoto' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showOverallRatings' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showRsBanner' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'displayRatings' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showDates' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'autoAnimate' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'displayArrows' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'speed' => array(
                    'type' => 'number',
                    'default' => 2
                ),
                'animationType' => array(
                    'type' => 'string',
                    'default' => 'slide'
                )
            )
        ));
    }
    
    public function render_block($attributes) {
        // Check if RealSatisfied widget exists
        if (!class_exists('RSRW_Real_Satisfied_Review_Widget')) {
            return '<div class="notice notice-error"><p>RealSatisfied Review Widget plugin is required.</p></div>';
        }
        
        // Determine vanity key
        $vanity_key = '';
        if ($attributes['useCustomField'] && !empty($attributes['customFieldName'])) {
            $vanity_key = get_post_meta(get_the_ID(), $attributes['customFieldName'], true);
        } else {
            $vanity_key = $attributes['manualVanityKey'];
        }
        
        if (empty($vanity_key)) {
            return '<div class="notice notice-warning"><p>No vanity key specified for RealSatisfied widget.</p></div>';
        }
        
        // Prepare widget instance
        $instance = array(
            'real_satisfied_id' => $vanity_key,
            'mode' => $attributes['mode'],
            'display_photo' => $attributes['displayPhoto'],
            'show_overall_ratings' => $attributes['showOverallRatings'],
            'show_rs_banner' => $attributes['showRsBanner'],
            'display_ratings' => $attributes['displayRatings'],
            'show_dates' => $attributes['showDates'],
            'auto_animate' => $attributes['autoAnimate'],
            'display_arrows' => $attributes['displayArrows'],
            'speed' => $attributes['speed'],
            'animation_type' => $attributes['animationType']
        );
        
        // Widget arguments
        $args = array(
            'before_widget' => '<div class="realsatisfied-block-widget">',
            'after_widget' => '</div>',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>'
        );
        
        // Capture widget output
        ob_start();
        $widget = new RSRW_Real_Satisfied_Review_Widget();
        $widget->widget($args, $instance);
        return ob_get_clean();
    }
    
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'realsatisfied-block-editor',
            plugin_dir_url(__FILE__) . 'block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            '1.0.1', // Increment version to clear cache
            true // Load in footer after other scripts
        );
        
        wp_enqueue_style(
            'realsatisfied-block-editor-style',
            plugin_dir_url(__FILE__) . 'block.css',
            array(),
            '1.0.1'
        );
        
        // Localize script with AJAX URL
        wp_localize_script('realsatisfied-block-editor', 'realsatisfiedBlock', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('realsatisfied_nonce')
        ));
    }
    
    public function get_custom_fields_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'realsatisfied_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        global $wpdb;
        
        // Get all unique meta keys from the database
        $meta_keys = $wpdb->get_col("
            SELECT DISTINCT meta_key 
            FROM {$wpdb->postmeta} 
            WHERE meta_key NOT LIKE '\_%' 
            ORDER BY meta_key
        ");
        
        $field_options = array();
        
        foreach ($meta_keys as $key) {
            $field_options[] = array(
                'label' => $key,
                'value' => $key
            );
        }
        
        wp_send_json_success($field_options);
    }
    
    public function missing_plugin_notice() {
        echo '<div class="notice notice-error"><p><strong>RealSatisfied Block:</strong> The RealSatisfied Review Widget plugin is required.</p></div>';
    }
}

new RealSatisfiedBlock();