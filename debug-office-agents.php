<?php
/**
 * Debug script to test Office Agents block registration
 * Add this to functions.php temporarily to test
 */

add_action('wp_loaded', function() {
    if (is_admin() && current_user_can('manage_options')) {
        // Check if block is registered
        $registry = WP_Block_Type_Registry::get_instance();
        $block_types = $registry->get_all_registered();
        
        if (isset($block_types['realsatisfied-blocks/office-agents'])) {
            error_log('✅ Office Agents block is registered successfully');
            error_log('Block supports: ' . print_r($block_types['realsatisfied-blocks/office-agents']->supports, true));
        } else {
            error_log('❌ Office Agents block is NOT registered');
            error_log('Available blocks: ' . implode(', ', array_keys($block_types)));
        }
        
        // Check if block categories are registered
        $categories = get_default_block_categories();
        $has_realsatisfied = false;
        foreach ($categories as $category) {
            if ($category['slug'] === 'realsatisfied') {
                $has_realsatisfied = true;
                break;
            }
        }
        
        if ($has_realsatisfied) {
            error_log('✅ RealSatisfied block category is registered');
        } else {
            error_log('❌ RealSatisfied block category is NOT registered');
        }
        
        // Check if required classes exist
        if (class_exists('RealSatisfied_Office_Agents_Block')) {
            error_log('✅ Office Agents block class exists');
        } else {
            error_log('❌ Office Agents block class does NOT exist');
        }
        
        if (class_exists('RealSatisfied_Office_RSS_Parser')) {
            error_log('✅ Office RSS Parser class exists');
        } else {
            error_log('❌ Office RSS Parser class does NOT exist');
        }
    }
});
