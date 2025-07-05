<?php
/**
 * RealSatisfied Custom Fields Integration
 * 
 * Handles custom field integration for office blocks
 * Manages the realsatisfied_feed custom field and post type interactions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RealSatisfied Custom Fields Class
 */
class RealSatisfied_Custom_Fields {
    
    /**
     * Plugin instance
     *
     * @var RealSatisfied_Custom_Fields
     */
    private static $instance = null;

    /**
     * Target post type for office data
     * 
     * @var string
     */
    private $office_post_type = 'post_type_685d8ecad6bb5';

    /**
     * Custom field name for vanity key
     * 
     * @var string
     */
    private $vanity_key_field = 'realsatisfied_feed';

    /**
     * Get plugin instance
     *
     * @return RealSatisfied_Custom_Fields
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
        // AJAX handler for getting custom fields
        add_action('wp_ajax_get_office_custom_fields', array($this, 'get_custom_fields_ajax'));
        add_action('wp_ajax_nopriv_get_office_custom_fields', array($this, 'get_custom_fields_ajax'));

        // Add meta box for office post type
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box'));
    }

    /**
     * Get vanity key from post custom field
     *
     * @param int $post_id Post ID
     * @return string|false Vanity key or false if not found
     */
    public function get_vanity_key($post_id = null) {
        if (is_null($post_id)) {
            $post_id = get_the_ID();
        }

        if (!$post_id) {
            return false;
        }

        $vanity_key = get_post_meta($post_id, $this->vanity_key_field, true);
        return !empty($vanity_key) ? $vanity_key : false;
    }

    /**
     * Set vanity key for post
     *
     * @param int $post_id Post ID
     * @param string $vanity_key Vanity key
     * @return bool Success
     */
    public function set_vanity_key($post_id, $vanity_key) {
        return update_post_meta($post_id, $this->vanity_key_field, sanitize_text_field($vanity_key));
    }

    /**
     * Check if post has vanity key
     *
     * @param int $post_id Post ID
     * @return bool True if has vanity key
     */
    public function has_vanity_key($post_id = null) {
        return $this->get_vanity_key($post_id) !== false;
    }

    /**
     * Get all posts with vanity keys
     *
     * @return array Array of post objects with vanity keys
     */
    public function get_posts_with_vanity_keys() {
        $args = array(
            'post_type' => $this->office_post_type,
            'meta_query' => array(
                array(
                    'key' => $this->vanity_key_field,
                    'value' => '',
                    'compare' => '!='
                )
            ),
            'posts_per_page' => -1
        );

        return get_posts($args);
    }

    /**
     * AJAX handler for getting custom fields
     */
    public function get_custom_fields_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'realsatisfied_office_blocks_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        global $wpdb;

        // Get all unique meta keys from the database
        $meta_keys = $wpdb->get_col(
            "SELECT DISTINCT meta_key 
             FROM {$wpdb->postmeta} 
             WHERE meta_key NOT LIKE '\_%' 
             AND meta_key LIKE '%realsatisfied%'
             ORDER BY meta_key"
        );

        // Add common field if not present
        if (!in_array($this->vanity_key_field, $meta_keys)) {
            $meta_keys[] = $this->vanity_key_field;
        }

        $field_options = array();
        foreach ($meta_keys as $key) {
            $field_options[] = array(
                'label' => $key,
                'value' => $key
            );
        }

        wp_send_json_success($field_options);
    }

    /**
     * Add meta boxes for office post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'realsatisfied_office_meta',
            __('RealSatisfied Office Settings', 'realsatisfied-blocks'),
            array($this, 'render_meta_box'),
            $this->office_post_type,
            'side',
            'default'
        );
    }

    /**
     * Render meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_meta_box($post) {
        // Add nonce field
        wp_nonce_field('realsatisfied_office_meta_nonce', 'realsatisfied_office_meta_nonce');

        // Get current value
        $vanity_key = $this->get_vanity_key($post->ID);

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="<?php echo esc_attr($this->vanity_key_field); ?>">
                        <?php esc_html_e('Office Vanity Key', 'realsatisfied-blocks'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" 
                           id="<?php echo esc_attr($this->vanity_key_field); ?>"
                           name="<?php echo esc_attr($this->vanity_key_field); ?>"
                           value="<?php echo esc_attr($vanity_key); ?>"
                           class="regular-text"
                           placeholder="<?php esc_attr_e('e.g., CENTURY21-Masters-11', 'realsatisfied-blocks'); ?>"
                    />
                    <p class="description">
                        <?php esc_html_e('Enter the RealSatisfied office vanity key for this office page.', 'realsatisfied-blocks'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save meta box data
     *
     * @param int $post_id Post ID
     */
    public function save_meta_box($post_id) {
        // Check if user has permission
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['realsatisfied_office_meta_nonce']) || 
            !wp_verify_nonce($_POST['realsatisfied_office_meta_nonce'], 'realsatisfied_office_meta_nonce')) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Save the field
        if (isset($_POST[$this->vanity_key_field])) {
            $this->set_vanity_key($post_id, $_POST[$this->vanity_key_field]);
        }
    }

    /**
     * Get office post type
     *
     * @return string Office post type
     */
    public function get_office_post_type() {
        return $this->office_post_type;
    }

    /**
     * Get vanity key field name
     *
     * @return string Vanity key field name
     */
    public function get_vanity_key_field() {
        return $this->vanity_key_field;
    }

    /**
     * Validate vanity key format
     *
     * @param string $vanity_key Vanity key to validate
     * @return bool True if valid format
     */
    public function validate_vanity_key($vanity_key) {
        // Basic validation - should contain letters, numbers, and hyphens
        return preg_match('/^[a-zA-Z0-9\-]+$/', $vanity_key);
    }
} 