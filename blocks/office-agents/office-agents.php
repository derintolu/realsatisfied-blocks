<?php
/**
 * RealSatisfied Office Agents Block
 * 
 * Displays agents for an office with layout options (grid, list, slider),
 * filtering, and sorting capabilities using WordPress Interactivity API
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RealSatisfied Office Agents Block Class
 */
class RealSatisfied_Office_Agents_Block {
    
    /**
     * Block name
     *
     * @var string
     */
    private $block_name = 'realsatisfied-blocks/office-agents';

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register block is now called directly from main plugin
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        // Frontend assets are handled by the main plugin file
    }

    /**
     * Register the block
     */
    public function register_block() {
        // Check if required classes exist
        if (!class_exists('RealSatisfied_Office_RSS_Parser') || 
            !class_exists('RealSatisfied_Custom_Fields')) {
            return;
        }

        register_block_type($this->block_name, array(
            'render_callback' => array($this, 'render_block'),
            'supports' => array(
                'html' => false,
                'align' => array('left', 'center', 'right', 'wide', 'full'),
                'alignWide' => true,
                'anchor' => true,
                'customClassName' => true,
                'interactivity' => true,
                'color' => array(
                    'gradients' => true,
                    'link' => true,
                    'text' => true,
                    'background' => true
                ),
                'typography' => array(
                    'fontSize' => true,
                    'lineHeight' => true,
                    'fontFamily' => true,
                    'fontWeight' => true,
                    'fontStyle' => true,
                    'textTransform' => true,
                    'textDecoration' => true,
                    'letterSpacing' => true
                ),
                'spacing' => array(
                    'margin' => true,
                    'padding' => true,
                    'blockGap' => true
                ),
                'border' => array(
                    'color' => true,
                    'radius' => true,
                    'style' => true,
                    'width' => true
                ),
                'dimensions' => array(
                    'minHeight' => true
                )
            ),
            'attributes' => array(
                'useCustomField' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'customFieldName' => array(
                    'type' => 'string',
                    'default' => 'realsatisfied_feed'
                ),
                'manualVanityKey' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'layout' => array(
                    'type' => 'string',
                    'default' => 'grid'
                ),
                'columns' => array(
                    'type' => 'number',
                    'default' => 3
                ),
                'agentCount' => array(
                    'type' => 'number',
                    'default' => 9
                ),
                'enablePagination' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'itemsPerPage' => array(
                    'type' => 'number',
                    'default' => 9
                ),
                'showAgentPhoto' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showAgentName' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showAgentTitle' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showAgentEmail' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'showAgentPhone' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'showAgentRating' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showReviewCount' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'sortBy' => array(
                    'type' => 'string',
                    'default' => 'name'
                ),
                'sortOrder' => array(
                    'type' => 'string',
                    'default' => 'asc'
                ),
                'backgroundColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'textColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'borderColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'borderRadius' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'cardBackgroundColor' => array(
                    'type' => 'string',
                    'default' => '#ffffff'
                ),
                'cardBorderColor' => array(
                    'type' => 'string',
                    'default' => '#e0e0e0'
                ),
                'cardBorderRadius' => array(
                    'type' => 'string',
                    'default' => '8px'
                ),
                'buttonColor' => array(
                    'type' => 'string',
                    'default' => '#007cba'
                ),
                'buttonTextColor' => array(
                    'type' => 'string',
                    'default' => '#ffffff'
                ),
                'buttonHoverColor' => array(
                    'type' => 'string',
                    'default' => '#005a87'
                )
            )
        ));
    }

    /**
     * Render the block
     *
     * @param array $attributes Block attributes
     * @return string Block HTML
     */
    public function render_block($attributes) {
        // Get vanity key
        $vanity_key = $this->get_vanity_key($attributes);
        
        if (empty($vanity_key)) {
            return $this->render_error(__('No vanity key specified for office agents.', 'realsatisfied-blocks'));
        }

        // Get RSS parser instance
        $rss_parser = RealSatisfied_Office_RSS_Parser::get_instance();
        
        // Fetch office data
        $office_data = $rss_parser->fetch_office_data($vanity_key);
        
        if (is_wp_error($office_data)) {
            return $this->render_error($office_data->get_error_message());
        }

        // Get agents from office data
        $agents = $office_data['agents'] ?? array();
        
        if (empty($agents)) {
            return $this->render_error(__('No agents found for this office.', 'realsatisfied-blocks'));
        }

        // Process agents for display
        $processed_agents = array();
        foreach ($agents as $agent) {
            $processed_agent = array(
                'display_name' => $this->clean_rss_text($agent['display_name'] ?? ''),
                'first_name' => $this->clean_rss_text($agent['first_name'] ?? ''),
                'last_name' => $this->clean_rss_text($agent['last_name'] ?? ''),
                'title' => $this->clean_rss_text($agent['title'] ?? ''),
                'email' => $agent['email'] ?? '',
                'phone' => $agent['phone'] ?? '',
                'mobile' => $agent['mobile'] ?? '',
                'avatar' => $agent['avatar'] ?? '',
                'vanity_id' => $agent['vanity_id'] ?? '',
                'overall_rating' => $agent['overall_rating'] ?? '',
                'review_count' => $agent['review_count'] ?? 0,
                'formatted_rating' => !empty($agent['overall_rating']) ? number_format($agent['overall_rating'], 1) : '',
                'star_rating' => !empty($agent['overall_rating']) ? $this->generate_stars($agent['overall_rating']) : '',
                'agent_photo' => $this->get_agent_photo($agent, $attributes),
                'contact_phone' => $this->format_phone_display($agent),
                'profile_url' => $this->get_agent_profile_url($agent)
            );
            
            $processed_agents[] = $processed_agent;
        }

        // Sort agents
        $sorted_agents = $this->sort_agents($processed_agents, $attributes);

        // Handle pagination or simple count limit
        $enable_pagination = $attributes['enablePagination'] ?? false;
        $total_agents = count($sorted_agents);
        
        if ($enable_pagination) {
            $items_per_page = intval($attributes['itemsPerPage'] ?? 9);
            $current_page = 1; // Default to first page for initial render
            $total_pages = ceil($total_agents / $items_per_page);
            $offset = ($current_page - 1) * $items_per_page;
            $paged_agents = array_slice($sorted_agents, $offset, $items_per_page);
        } else {
            // Simple count limit (original behavior)
            $agent_count = intval($attributes['agentCount'] ?? 9);
            if ($agent_count > 0) {
                $sorted_agents = array_slice($sorted_agents, 0, $agent_count);
            }
            $paged_agents = $sorted_agents;
            $total_pages = 1;
        }

        // Get block wrapper attributes
        $wrapper_attributes = get_block_wrapper_attributes(array(
            'class' => 'realsatisfied-office-agents layout-' . esc_attr($attributes['layout'] ?? 'grid')
        ));

        // Build wrapper styles
        $wrapper_styles = $this->build_wrapper_styles($attributes);
        $style_attr = !empty($wrapper_styles) ? 'style="' . implode('; ', $wrapper_styles) . '"' : '';

        // Start building output
        ob_start();
        ?>
        <div <?php echo $wrapper_attributes; ?> <?php echo $style_attr; ?>
             data-wp-interactive="realsatisfied-office-agents"
             <?php echo wp_interactivity_data_wp_context(array(
                 'agents' => $sorted_agents,
                 'currentPage' => 1,
                 'totalPages' => $total_pages,
                 'itemsPerPage' => $enable_pagination ? ($attributes['itemsPerPage'] ?? 9) : $total_agents,
                 'enablePagination' => $enable_pagination,
                 'layout' => $attributes['layout'] ?? 'grid',
                 'columns' => $attributes['columns'] ?? 3,
                 'loading' => false,
                 'error' => null,
                 'sortBy' => $attributes['sortBy'] ?? 'name'
             )); ?>
             data-wp-init="callbacks.initAgents">
            
            <?php 
            // Show sorting controls if there are multiple agents
            if (count($sorted_agents) > 1): 
            ?>
                <div class="agents-controls">
                    <div class="agents-sorting">
                        <select data-wp-on--change="actions.sortAgents" class="sort-control">
                            <option value="name"><?php _e('Sort by Name', 'realsatisfied-blocks'); ?></option>
                            <option value="rating"><?php _e('Sort by Rating', 'realsatisfied-blocks'); ?></option>
                            <option value="reviews"><?php _e('Sort by Review Count', 'realsatisfied-blocks'); ?></option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="agents-container">
                <?php
                $layout = $attributes['layout'] ?? 'grid';
                $columns = $attributes['columns'] ?? 3;
                
                if ($layout === 'grid'):
                ?>
                    <div class="agents-grid columns-<?php echo esc_attr($columns); ?>">
                        <template data-wp-each="state.currentAgents">
                            <?php echo $this->render_agent_template($attributes); ?>
                        </template>
                    </div>
                <?php elseif ($layout === 'list'): ?>
                    <div class="agents-list">
                        <template data-wp-each="state.currentAgents">
                            <?php echo $this->render_agent_template($attributes); ?>
                        </template>
                    </div>
                <?php elseif ($layout === 'slider'): ?>
                    <div class="agents-slider flexslider">
                        <ul class="slides">
                            <template data-wp-each="state.currentAgents">
                                <li><?php echo $this->render_agent_template($attributes); ?></li>
                            </template>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="agents-grid columns-<?php echo esc_attr($columns); ?>">
                        <template data-wp-each="state.currentAgents">
                            <?php echo $this->render_agent_template($attributes); ?>
                        </template>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($enable_pagination): ?>
                <div class="agents-pagination" style="<?php echo $this->get_pagination_style($attributes); ?>" data-wp-show="state.totalPages > 1">
                    <button class="pagination-btn pagination-prev" 
                            data-wp-on--click="actions.prevPage"
                            data-wp-bind--disabled="!state.canGoPrev">
                        &larr; <?php _e('Previous', 'realsatisfied-blocks'); ?>
                    </button>
                    <div class="pagination-numbers">
                        <span class="pagination-info" data-wp-text="state.pageInfo"></span>
                    </div>
                    <button class="pagination-btn pagination-next"
                            data-wp-on--click="actions.nextPage" 
                            data-wp-bind--disabled="!state.canGoNext">
                        <?php _e('Next', 'realsatisfied-blocks'); ?> &rarr;
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Sort agents based on attributes
     *
     * @param array $agents Array of agents
     * @param array $attributes Block attributes
     * @return array Sorted agents
     */
    private function sort_agents($agents, $attributes) {
        $sort_by = $attributes['sortBy'] ?? 'name';
        $sort_order = $attributes['sortOrder'] ?? 'asc';

        usort($agents, function($a, $b) use ($sort_by, $sort_order) {
            $result = 0;
            
            switch ($sort_by) {
                case 'name':
                    $result = strcmp($a['display_name'], $b['display_name']);
                    break;
                case 'rating':
                    $rating_a = floatval($a['overall_rating'] ?? 0);
                    $rating_b = floatval($b['overall_rating'] ?? 0);
                    $result = $rating_a - $rating_b;
                    break;
                case 'reviews':
                    $reviews_a = intval($a['review_count'] ?? 0);
                    $reviews_b = intval($b['review_count'] ?? 0);
                    $result = $reviews_a - $reviews_b;
                    break;
            }
            
            return $sort_order === 'asc' ? $result : -$result;
        });

        return $agents;
    }

    /**
     * Get agent photo URL with fallback to initials
     *
     * @param array $agent Agent data
     * @param array $attributes Block attributes
     * @return string Photo URL or SVG data URL
     */
    private function get_agent_photo($agent, $attributes) {
        if (!empty($agent['avatar'])) {
            return $agent['avatar'];
        }
        
        // Generate initials-based placeholder
        $display_name = $agent['display_name'] ?? '';
        $initials = '';
        if (!empty($display_name)) {
            $name_parts = explode(' ', trim($display_name));
            foreach ($name_parts as $part) {
                if (!empty($part)) {
                    $initials .= strtoupper(substr($part, 0, 1));
                    if (strlen($initials) >= 2) break; // Limit to 2 initials
                }
            }
        }
        if (empty($initials)) {
            $initials = '?';
        }
        
        // Create a colored background
        $button_color = $attributes['buttonColor'] ?? '#007cba';
        $colors = [
            $button_color, // Primary button color
            $this->adjust_color_brightness($button_color, -20),
            $this->adjust_color_brightness($button_color, 20),
            $this->adjust_color_hue($button_color, 30),
            $this->adjust_color_hue($button_color, -30),
            '#e74c3c', '#2ecc71', '#9b59b6' // Fallback colors
        ];
        $color_index = crc32($display_name) % count($colors);
        $bg_color = $colors[$color_index];
        
        return "data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Ccircle cx='60' cy='60' r='60' fill='" . urlencode($bg_color) . "'/%3E%3Ctext x='60' y='72' text-anchor='middle' fill='white' font-size='36' font-weight='bold' font-family='Arial'%3E" . urlencode($initials) . "%3C/text%3E%3C/svg%3E";
    }

    /**
     * Format phone number for display
     *
     * @param array $agent Agent data
     * @return string Formatted phone number
     */
    private function format_phone_display($agent) {
        $phone = $agent['phone'] ?? '';
        $mobile = $agent['mobile'] ?? '';
        
        // Prefer mobile over phone
        $primary_phone = !empty($mobile) ? $mobile : $phone;
        
        // Basic phone formatting (US format)
        if (!empty($primary_phone)) {
            $cleaned = preg_replace('/[^0-9]/', '', $primary_phone);
            if (strlen($cleaned) === 10) {
                return sprintf('(%s) %s-%s', 
                    substr($cleaned, 0, 3),
                    substr($cleaned, 3, 3),
                    substr($cleaned, 6, 4)
                );
            }
        }
        
        return $primary_phone;
    }

    /**
     * Get agent profile URL
     *
     * @param array $agent Agent data
     * @return string Profile URL
     */
    private function get_agent_profile_url($agent) {
        $vanity_id = $agent['vanity_id'] ?? '';
        if (!empty($vanity_id)) {
            return 'https://www.realsatisfied.com/agents/' . urlencode($vanity_id);
        }
        return '';
    }

    /**
     * Generate star rating display
     *
     * @param float $rating Rating value (0.0-5.0)
     * @return string Star rating HTML
     */
    private function generate_stars($rating) {
        if (empty($rating)) {
            return '';
        }
        
        $star_rating = floatval($rating);
        $full_stars = floor($star_rating);
        $half_star = ($star_rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
        
        $html = '';
        
        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $html .= '★';
        }
        
        // Half star
        if ($half_star) {
            $html .= '☆';
        }
        
        // Empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            $html .= '☆';
        }

        return $html;
    }

    /**
     * Render agent template for Interactivity API
     *
     * @param array $attributes Block attributes
     * @return string Template HTML
     */
    private function render_agent_template($attributes) {
        // Build card styles
        $card_styles = $this->build_card_styles($attributes);
        $card_style_attr = !empty($card_styles) ? 'style="' . implode('; ', $card_styles) . '"' : '';
        
        ob_start();
        ?>
        <div class="agent-item agent-card" <?php echo $card_style_attr; ?>>
            <?php if ($attributes['showAgentPhoto'] ?? true): ?>
                <div class="agent-photo">
                    <img data-wp-bind--src="context.item.agent_photo" 
                         data-wp-bind--alt="context.item.display_name" />
                </div>
            <?php endif; ?>
            
            <div class="agent-info">
                <?php if ($attributes['showAgentName'] ?? true): ?>
                    <h3 class="agent-name" data-wp-text="context.item.display_name"></h3>
                <?php endif; ?>
                
                <?php if ($attributes['showAgentTitle'] ?? true): ?>
                    <div class="agent-title" data-wp-text="context.item.title" data-wp-show="context.item.title"></div>
                <?php endif; ?>
                
                <?php if ($attributes['showAgentRating'] ?? true): ?>
                    <div class="agent-rating" data-wp-show="context.item.overall_rating">
                        <span class="rating-stars" data-wp-text="context.item.star_rating"></span>
                        <span class="rating-value" data-wp-text="context.item.formatted_rating"></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($attributes['showReviewCount'] ?? true): ?>
                    <div class="agent-reviews" data-wp-show="context.item.review_count">
                        <span data-wp-text="context.item.review_count"></span>
                        <span><?php _e('reviews', 'realsatisfied-blocks'); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="agent-contact">
                    <?php if ($attributes['showAgentEmail'] ?? false): ?>
                        <div class="agent-email" data-wp-show="context.item.email">
                            <a data-wp-bind--href="'mailto:' + context.item.email" data-wp-text="context.item.email"></a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($attributes['showAgentPhone'] ?? false): ?>
                        <div class="agent-phone" data-wp-show="context.item.contact_phone">
                            <a data-wp-bind--href="'tel:' + context.item.contact_phone.replace(/[^0-9]/g, '')" data-wp-text="context.item.contact_phone"></a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="agent-actions" data-wp-show="context.item.profile_url">
                    <a class="agent-profile-link" 
                       data-wp-bind--href="context.item.profile_url"
                       target="_blank"
                       rel="noopener noreferrer"
                       style="background-color: <?php echo esc_attr($attributes['buttonColor'] ?? '#007cba'); ?>; color: <?php echo esc_attr($attributes['buttonTextColor'] ?? '#ffffff'); ?>;">
                        <?php _e('View Profile', 'realsatisfied-blocks'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Build wrapper styles
     *
     * @param array $attributes Block attributes
     * @return array CSS styles
     */
    private function build_wrapper_styles($attributes) {
        $styles = array();
        
        if (!empty($attributes['backgroundColor'])) {
            $styles[] = 'background-color: ' . esc_attr($attributes['backgroundColor']);
        }
        
        if (!empty($attributes['textColor'])) {
            $styles[] = 'color: ' . esc_attr($attributes['textColor']);
        }
        
        if (!empty($attributes['borderColor'])) {
            $styles[] = 'border-color: ' . esc_attr($attributes['borderColor']);
        }
        
        if (!empty($attributes['borderRadius'])) {
            $styles[] = 'border-radius: ' . esc_attr($attributes['borderRadius']);
        }
        
        return $styles;
    }

    /**
     * Build individual card styles
     *
     * @param array $attributes Block attributes
     * @return array Array of CSS styles
     */
    private function build_card_styles($attributes) {
        $styles = array();

        if (!empty($attributes['cardBackgroundColor'])) {
            $styles[] = 'background-color: ' . esc_attr($attributes['cardBackgroundColor']);
        }

        if (!empty($attributes['cardBorderColor'])) {
            $styles[] = 'border-color: ' . esc_attr($attributes['cardBorderColor']);
        }

        if (!empty($attributes['cardBorderRadius'])) {
            $styles[] = 'border-radius: ' . esc_attr($attributes['cardBorderRadius']);
        }

        return $styles;
    }

    /**
     * Get pagination styles
     *
     * @param array $attributes Block attributes
     * @return string CSS styles for pagination
     */
    private function get_pagination_style($attributes) {
        $button_bg = $attributes['buttonColor'] ?? '#007cba';
        $button_text = $attributes['buttonTextColor'] ?? '#ffffff';
        $button_hover_bg = $attributes['buttonHoverColor'] ?? '#005a87';
        
        // Generate CSS custom properties for pagination
        $styles = array(
            '--pagination-bg-color: ' . esc_attr($button_bg),
            '--pagination-text-color: ' . esc_attr($button_text),
            '--pagination-hover-bg-color: ' . esc_attr($button_hover_bg),
            '--pagination-border-radius: 5px'
        );
        
        return implode('; ', $styles);
    }

    /**
     * Get vanity key from attributes
     *
     * @param array $attributes Block attributes
     * @return string|false Vanity key or false if not found
     */
    private function get_vanity_key($attributes) {
        if ($attributes['useCustomField'] && !empty($attributes['customFieldName'])) {
            $custom_fields = RealSatisfied_Custom_Fields::get_instance();
            $vanity_key = $custom_fields->get_vanity_key();
            if ($vanity_key) {
                return $vanity_key;
            }
        } elseif (!empty($attributes['manualVanityKey'])) {
            return $attributes['manualVanityKey'];
        }
        
        // Default fallback for testing
        return 'CENTURY21-Masters-11';
    }

    /**
     * Render error message
     *
     * @param string $message Error message
     * @return string Error HTML
     */
    private function render_error($message) {
        return '<div class="realsatisfied-office-agents-error notice notice-warning">' .
               '<p>' . esc_html($message) . '</p>' .
               '</div>';
    }

    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'realsatisfied-office-agents-editor',
            RSOB_PLUGIN_URL . 'blocks/office-agents/office-agents-editor.js',
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
            RSOB_PLUGIN_VERSION,
            true
        );

        wp_enqueue_style(
            'realsatisfied-office-agents-editor',
            RSOB_PLUGIN_URL . 'assets/realsatisfied-blocks.css',
            array(),
            RSOB_PLUGIN_VERSION
        );
    }
    
    /**
     * Adjust color brightness
     * 
     * @param string $hex_color Hex color (e.g., #007cba)
     * @param int $percent Percentage to adjust (-100 to 100)
     * @return string Adjusted hex color
     */
    private function adjust_color_brightness($hex_color, $percent) {
        // Remove # if present
        $hex_color = ltrim($hex_color, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex_color, 0, 2));
        $g = hexdec(substr($hex_color, 2, 2));
        $b = hexdec(substr($hex_color, 4, 2));
        
        // Adjust brightness
        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));
        
        // Convert back to hex
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Adjust color hue
     * 
     * @param string $hex_color Hex color (e.g., #007cba)
     * @param int $degrees Degrees to shift hue (-360 to 360)
     * @return string Adjusted hex color
     */
    private function adjust_color_hue($hex_color, $degrees) {
        // Remove # if present
        $hex_color = ltrim($hex_color, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex_color, 0, 2)) / 255;
        $g = hexdec(substr($hex_color, 2, 2)) / 255;
        $b = hexdec(substr($hex_color, 4, 2)) / 255;
        
        // Convert RGB to HSL
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        
        if ($max == $min) {
            $h = $s = 0; // achromatic
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            
            switch ($max) {
                case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
                case $g: $h = ($b - $r) / $d + 2; break;
                case $b: $h = ($r - $g) / $d + 4; break;
            }
            $h /= 6;
        }
        
        // Adjust hue
        $h += $degrees / 360;
        if ($h > 1) $h -= 1;
        if ($h < 0) $h += 1;
        
        // Convert HSL back to RGB
        if ($s == 0) {
            $r = $g = $b = $l; // achromatic
        } else {
            $hue_to_rgb = function($p, $q, $t) {
                if ($t < 0) $t += 1;
                if ($t > 1) $t -= 1;
                if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
                if ($t < 1/2) return $q;
                if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
                return $p;
            };
            
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $hue_to_rgb($p, $q, $h + 1/3);
            $g = $hue_to_rgb($p, $q, $h);
            $b = $hue_to_rgb($p, $q, $h - 1/3);
        }
        
        // Convert back to hex
        return sprintf('#%02x%02x%02x', round($r * 255), round($g * 255), round($b * 255));
    }

    /**
     * Clean and decode text content from RSS feeds
     * 
     * @param string $text Raw text that may contain HTML entities or unwanted characters
     * @return string Cleaned and decoded text
     */
    private function clean_rss_text($text) {
        if (empty($text)) {
            return '';
        }
        
        // First decode HTML entities multiple times to handle nested encoding
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Handle any remaining common encoded characters
        $replacements = array(
            '&amp;' => '&',
            '&lt;' => '<',
            '&gt;' => '>',
            '&quot;' => '"',
            '&#39;' => "'",
            '&#8217;' => "'", // Right single quotation mark
            '&#8216;' => "'", // Left single quotation mark
            '&#8220;' => '"', // Left double quotation mark
            '&#8221;' => '"', // Right double quotation mark
            '&#8211;' => '–', // En dash
            '&#8212;' => '—', // Em dash
            '&#8230;' => '…', // Horizontal ellipsis
            // Additional common problematic sequences
            '&lsquo;' => "'",
            '&rsquo;' => "'",
            '&ldquo;' => '"',
            '&rdquo;' => '"',
            '&ndash;' => '–',
            '&mdash;' => '—',
            '&hellip;' => '…',
            // Fix common encoding issues
            'â€™' => "'", // Common UTF-8 encoding issue for apostrophe
            'â€œ' => '"', // Common UTF-8 encoding issue for left quote
            'â€' => '"',  // Common UTF-8 encoding issue for right quote
            'â€"' => '—', // Common UTF-8 encoding issue for em dash
            'â€"' => '–', // Common UTF-8 encoding issue for en dash
        );
        
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
        
        // Remove any remaining HTML tags
        $text = strip_tags($text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // Final pass to remove any remaining weird characters
        $text = preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '', $text);
        
        return $text;
    }
}

// Initialize the block
new RealSatisfied_Office_Agents_Block();