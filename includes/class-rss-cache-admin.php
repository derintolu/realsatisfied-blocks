<?php
/**
 * RealSatisfied Cache Manager Admin Interface
 * 
 * Provides admin interface for monitoring and manually triggering cache refreshes
 * 
 * @since 1.2.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RealSatisfied Cache Admin Class
 */
class RealSatisfied_Cache_Admin {
    
    /**
     * Plugin instance
     *
     * @var RealSatisfied_Cache_Admin
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return RealSatisfied_Cache_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_realsatisfied_manual_refresh', array($this, 'handle_manual_refresh'));
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        // Add as submenu under Tools only
        add_management_page(
            __('RealSatisfied Cache Manager', 'realsatisfied-blocks'),
            __('RealSatisfied Cache', 'realsatisfied-blocks'),
            'manage_options',
            'realsatisfied-cache',
            array($this, 'admin_page')
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_realsatisfied-cache') {
            return;
        }

        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'realsatisfiedCache', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('realsatisfied_cache_refresh'),
            'refreshing' => __('Refreshing...', 'realsatisfied-blocks'),
            'refresh' => __('Refresh Cache', 'realsatisfied-blocks')
        ));
    }

    /**
     * Admin page content
     */
    public function admin_page() {
        $cache_manager = RealSatisfied_RSS_Cache_Manager::get_instance();
        $stats = $cache_manager->get_cache_stats();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Auto Cache Prefetch System', 'realsatisfied-blocks'); ?></strong><br>
                    <?php _e('This system automatically refreshes RSS caches every 11 hours to prevent expiration delays. Caches are refreshed in the background using WordPress Cron.', 'realsatisfied-blocks'); ?>
                </p>
            </div>

            <div class="postbox">
                <h2 class="hndle"><?php _e('Cache Status', 'realsatisfied-blocks'); ?></h2>
                <div class="inside">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Cache Type', 'realsatisfied-blocks'); ?></th>
                                <th><?php _e('Status', 'realsatisfied-blocks'); ?></th>
                                <th><?php _e('Last Refresh', 'realsatisfied-blocks'); ?></th>
                                <th><?php _e('Next Scheduled', 'realsatisfied-blocks'); ?></th>
                                <th><?php _e('Actions', 'realsatisfied-blocks'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats as $hook => $data): ?>
                                <?php
                                $type = str_replace('realsatisfied_refresh_', '', $hook);
                                $type = str_replace('_cache', '', $type);
                                $type = ucfirst($type);
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($type); ?></strong></td>
                                    <td>
                                        <?php if ($data['is_scheduled']): ?>
                                            <span style="color: green;">✓ <?php _e('Scheduled', 'realsatisfied-blocks'); ?></span>
                                        <?php else: ?>
                                            <span style="color: red;">✗ <?php _e('Not Scheduled', 'realsatisfied-blocks'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($data['last_refresh']): ?>
                                            <?php echo esc_html(human_time_diff($data['last_refresh']['timestamp']) . ' ago'); ?>
                                            <br><small>(<?php echo esc_html($data['last_refresh']['count']); ?> caches, <?php echo esc_html($data['last_refresh']['execution_time']); ?>ms)</small>
                                        <?php else: ?>
                                            <em><?php _e('Never', 'realsatisfied-blocks'); ?></em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($data['next_scheduled']): ?>
                                            <?php echo esc_html(human_time_diff($data['next_scheduled']) . ' from now'); ?>
                                        <?php else: ?>
                                            <em><?php _e('Not scheduled', 'realsatisfied-blocks'); ?></em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="button refresh-cache" data-type="<?php echo esc_attr(strtolower($type)); ?>">
                                            <?php _e('Refresh Now', 'realsatisfied-blocks'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <h2 class="hndle"><?php _e('Manual Actions', 'realsatisfied-blocks'); ?></h2>
                <div class="inside">
                    <p><?php _e('Use these buttons to manually refresh specific cache types or all caches at once.', 'realsatisfied-blocks'); ?></p>
                    <p>
                        <button class="button button-primary refresh-cache" data-type="all">
                            <?php _e('Refresh All Caches', 'realsatisfied-blocks'); ?>
                        </button>
                        <button class="button refresh-cache" data-type="company">
                            <?php _e('Refresh Company Caches', 'realsatisfied-blocks'); ?>
                        </button>
                        <button class="button refresh-cache" data-type="office">
                            <?php _e('Refresh Office Caches', 'realsatisfied-blocks'); ?>
                        </button>
                        <button class="button refresh-cache" data-type="agent">
                            <?php _e('Refresh Agent Caches', 'realsatisfied-blocks'); ?>
                        </button>
                    </p>
                    
                    <hr style="margin: 20px 0;">
                    
                    <p><?php _e('If cron jobs show "Not Scheduled", use this button to force schedule them:', 'realsatisfied-blocks'); ?></p>
                    <p>
                        <button class="button button-secondary" id="force-schedule-jobs">
                            <?php _e('Force Schedule All Cron Jobs', 'realsatisfied-blocks'); ?>
                        </button>
                    </p>
                </div>
            </div>

            <div id="cache-refresh-result" style="display: none;" class="notice">
                <p id="cache-refresh-message"></p>
            </div>

        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.refresh-cache').on('click', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var type = button.data('type');
                var originalText = button.text();
                
                button.prop('disabled', true).text(realsatisfiedCache.refreshing);
                
                $.ajax({
                    url: realsatisfiedCache.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'realsatisfied_manual_refresh',
                        type: type,
                        nonce: realsatisfiedCache.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#cache-refresh-result')
                                .removeClass('notice-error')
                                .addClass('notice-success')
                                .show();
                            $('#cache-refresh-message').text(response.data.message);
                            
                            // Reload page after short delay to show updated stats
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $('#cache-refresh-result')
                                .removeClass('notice-success')
                                .addClass('notice-error')
                                .show();
                            $('#cache-refresh-message').text(response.data || 'An error occurred.');
                        }
                    },
                    error: function() {
                        $('#cache-refresh-result')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .show();
                        $('#cache-refresh-message').text('AJAX request failed.');
                    },
                    complete: function() {
                        button.prop('disabled', false).text(originalText);
                    }
                });
            });
            
            // Force schedule jobs button
            $('#force-schedule-jobs').on('click', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var originalText = button.text();
                
                button.prop('disabled', true).text('Scheduling...');
                
                $.ajax({
                    url: realsatisfiedCache.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'realsatisfied_manual_refresh',
                        type: 'schedule',
                        nonce: realsatisfiedCache.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#cache-refresh-result')
                                .removeClass('notice-error')
                                .addClass('notice-success')
                                .show();
                            $('#cache-refresh-message').text('Cron jobs scheduled successfully!');
                            
                            // Reload page after short delay to show updated status
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $('#cache-refresh-result')
                                .removeClass('notice-success')
                                .addClass('notice-error')
                                .show();
                            $('#cache-refresh-message').text(response.data || 'Failed to schedule jobs.');
                        }
                    },
                    error: function() {
                        $('#cache-refresh-result')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .show();
                        $('#cache-refresh-message').text('AJAX request failed.');
                    },
                    complete: function() {
                        button.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
        </script>

        <style type="text/css">
        .postbox {
            margin-top: 20px;
        }
        .postbox h2.hndle {
            padding: 10px 15px;
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .postbox .inside {
            padding: 15px;
        }
        #cache-refresh-result {
            margin-top: 20px;
        }
        </style>
        <?php
    }

    /**
     * Handle manual cache refresh AJAX request
     */
    public function handle_manual_refresh() {
        // Verify user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'realsatisfied-blocks'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'realsatisfied_cache_refresh')) {
            wp_send_json_error(__('Invalid nonce', 'realsatisfied-blocks'));
        }
        
        $type = sanitize_text_field($_POST['type']);
        $cache_manager = RealSatisfied_RSS_Cache_Manager::get_instance();
        
        // Handle force scheduling
        if ($type === 'schedule') {
            $result = $cache_manager->force_schedule_all();
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('All cron jobs have been scheduled successfully', 'realsatisfied-blocks')
                ));
            } else {
                wp_send_json_error(__('Failed to schedule some cron jobs', 'realsatisfied-blocks'));
            }
            return;
        }
        
        // Trigger the appropriate refresh
        switch ($type) {
            case 'company':
                $cache_manager->execute_cache_refresh('realsatisfied_refresh_company_cache');
                break;
            case 'office':
                $cache_manager->execute_cache_refresh('realsatisfied_refresh_office_cache');
                break;
            case 'agent':
                $cache_manager->execute_cache_refresh('realsatisfied_refresh_agent_cache');
                break;
            case 'all':
                $cache_manager->execute_cache_refresh('realsatisfied_refresh_company_cache');
                $cache_manager->execute_cache_refresh('realsatisfied_refresh_office_cache');
                $cache_manager->execute_cache_refresh('realsatisfied_refresh_agent_cache');
                break;
            default:
                wp_send_json_error(__('Invalid cache type', 'realsatisfied-blocks'));
                return;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%s cache refresh completed successfully', 'realsatisfied-blocks'), ucfirst($type))
        ));
    }
}

// Initialize admin interface
add_action('plugins_loaded', function() {
    if (is_admin() && class_exists('RealSatisfied_RSS_Cache_Manager')) {
        RealSatisfied_Cache_Admin::get_instance();
    }
});
