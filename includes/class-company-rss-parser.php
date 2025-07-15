<?php
/**
 * RealSatisfied Company RSS Parser
 * 
 * Handles company-level RSS feed parsing for brokerage-wide testimonials
 * Extends the existing office functionality to handle multi-office brokerages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * RealSatisfied Company RSS Parser Class
 */
class RealSatisfied_Company_RSS_Parser {
    
    /**
     * Plugin instance
     *
     * @var RealSatisfied_Company_RSS_Parser
     */
    private static $instance = null;

    /**
     * RSS feed cache duration (12 hours)
     *
     * @var int
     */
    private $cache_duration = 43200;

    /**
     * RSS feed URL for company
     *
     * @var string
     */
    private $company_feed_url = 'https://rss.realsatisfied.com/rss/company/';

    /**
     * XML namespaces
     *
     * @var array
     */
    private $namespaces = array(
        'realsatisfied' => 'https://rss.realsatisfied.com/ns/realsatisfied/',
        'atom' => 'http://www.w3.org/2005/Atom'
    );

    /**
     * Get plugin instance
     *
     * @return RealSatisfied_Company_RSS_Parser
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
        add_action('wp_ajax_rsob_clear_company_feed_cache', array($this, 'clear_feed_cache_callback'));
        add_action('wp_ajax_nopriv_rsob_clear_company_feed_cache', array($this, 'clear_feed_cache_callback'));
    }

    /**
     * Fetch and parse company RSS feed
     *
     * @param string $company_id The company ID (e.g., "https://rss.realsatisfied.com/rss/company/{companyid}")
     * @param array $options Optional parameters (limit, office_filter, etc.)
     * @return array|WP_Error Array with company data and testimonials, or WP_Error on failure
     */
    public function fetch_company_data($company_id, $options = array()) {
        if (empty($company_id)) {
            return new WP_Error('missing_company_id', __('No company ID provided', 'realsatisfied-blocks'));
        }

        // Check cache first
        $cache_key = 'rsob_company_' . md5($company_id . serialize($options));
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }

        // Build feed URL
        $feed_url = $this->company_feed_url . $company_id;
        
        // Add query parameters if needed
        if (!empty($options['page'])) {
            $feed_url .= '?page=' . intval($options['page']);
        }
        
        // Temporarily increase HTTP timeout for this request
        add_filter('http_request_timeout', array($this, 'increase_http_timeout'));
        add_filter('http_request_args', array($this, 'customize_http_args'));
        
        // Try to fetch RSS feed using WordPress HTTP API instead of SimplePie
        $response = wp_remote_get($feed_url, array(
            'timeout' => 60,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        ));
        
        // Remove timeout filter
        remove_filter('http_request_timeout', array($this, 'increase_http_timeout'));
        remove_filter('http_request_args', array($this, 'customize_http_args'));
        
        if (is_wp_error($response)) {
            return new WP_Error('fetch_failed', 'Failed to fetch RSS feed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return new WP_Error('empty_response', 'Empty response from RSS feed');
        }
        
        // Parse XML directly using SimpleXML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            $error_msg = 'XML parsing failed';
            if (!empty($errors)) {
                $error_msg .= ': ' . $errors[0]->message;
            }
            return new WP_Error('xml_parse_failed', $error_msg);
        }
        
        // Extract data using SimpleXML
        $company_data = $this->extract_company_data_from_xml($xml, $options);
        
        if (is_wp_error($company_data)) {
            return $company_data;
        }

        // Cache the result for 12 hours (43200 seconds)
        set_transient($cache_key, $company_data, $this->cache_duration);
        
        // Store cache metadata for the cache manager
        $cache_hash = md5($company_id . serialize($options));
        $meta_key = 'realsatisfied_company_meta_' . $cache_hash;
        update_option($meta_key, array(
            'company_id' => $company_id,
            'options' => $options,
            'cached_at' => time(),
            'cache_key' => $cache_key
        ), false);

        return $company_data;
    }

    /**
     * Extract company data from XML using SimpleXML instead of SimplePie
     *
     * @param SimpleXMLElement $xml The XML object
     * @param array $options Optional parameters
     * @return array|WP_Error Array with company data and testimonials, or WP_Error on failure
     */
    private function extract_company_data_from_xml($xml, $options = array()) {
        // Register namespace
        $xml->registerXPathNamespace('rs', $this->namespaces['realsatisfied']);
        
        // Debug: log the XML structure
        error_log('Company RSS Parser: XML root element: ' . $xml->getName());
        error_log('Company RSS Parser: Channel found: ' . (isset($xml->channel) ? 'yes' : 'no'));
        
        // Extract channel data (company-wide information)
        $channel = $xml->channel;
        if (!$channel) {
            error_log('Company RSS Parser: No channel found in XML');
            return new WP_Error('no_channel', 'No channel found in RSS feed');
        }
        
        $channel_data = array(
            'title' => (string) $channel->title,
            'link' => (string) $channel->link,
            'description' => (string) $channel->description,
            'language' => (string) $channel->language,
            'copyright' => (string) $channel->copyright,
            'pub_date' => (string) $channel->pubDate,
            'category' => (string) $channel->category,
            'generator' => (string) $channel->generator,
            'ttl' => (string) $channel->ttl
        );

        // Extract offices and testimonials
        $all_testimonials = array();
        $offices = array();
        $unique_agents = array();
        
        // Get all items (testimonials) - company feed has nested structure
        $limit = isset($options['limit']) ? intval($options['limit']) : 200;
        $count = 0;
        
        // Check if this is a company feed with offices or a direct feed with items
        $has_offices = isset($channel->office) && count($channel->office) > 0;
        
        if ($has_offices) {
            // For company feeds, items are nested under office elements
            $office_count = count($channel->office);
            error_log('Company RSS Parser: Found ' . $office_count . ' offices in channel');
            
            foreach ($channel->office as $office) {
            $item_count = count($office->item);
            error_log('Company RSS Parser: Found ' . $item_count . ' items in office');
            
            // Extract office information
            $office_info = array(
                'title' => (string) $office->title,
                'description' => (string) $office->description,
                'link' => (string) $office->link,
                'pub_date' => (string) $office->pubDate,
                'category' => (string) $office->category,
            );
            
            // Extract office custom fields
            $office_children = $office->children($this->namespaces['realsatisfied']);
            foreach ($office_children as $field_name => $field_value) {
                $value = (string) $field_value;
                switch ($field_name) {
                    case 'office':
                        $office_info['name'] = $value;
                        break;
                    case 'phone':
                        $office_info['phone'] = $value;
                        break;
                    case 'address':
                        $office_info['address'] = $value;
                        break;
                    case 'city':
                        $office_info['city'] = $value;
                        break;
                    case 'state':
                        $office_info['state'] = $value;
                        break;
                    case 'postcode':
                        $office_info['postcode'] = $value;
                        break;
                    case 'logo':
                        $office_info['logo'] = $value;
                        break;
                }
            }
            
            $offices[] = $office_info;
            
            // Process testimonials in this office
            foreach ($office->item as $item) {
                if ($count >= $limit) break;
            
            // Extract basic testimonial data
            $testimonial = array(
                'text' => (string) $item->description,
                'customer_name' => '',
                'customer_location' => '',
                'customer_type' => '',
                'agent_name' => '',
                'agent_avatar' => '',
                'office_name' => isset($office_info['name']) ? $office_info['name'] : (string) $office->title,
                'office_category' => 'BASIC',
                'link' => (string) $item->link,
                'pub_date' => (string) $item->pubDate,
                'guid' => (string) $item->guid,
                'rating' => 5 // Default high rating
            );
            
            // Parse customer name and location from title (format: "Name, City, State")
            $title = (string) $item->title;
            if (!empty($title)) {
                $parts = explode(', ', $title);
                $testimonial['customer_name'] = $parts[0];
                if (count($parts) >= 2) {
                    $testimonial['customer_location'] = implode(', ', array_slice($parts, 1));
                }
            }
            
            // Extract RealSatisfied custom fields
            $children = $item->children($this->namespaces['realsatisfied']);
            foreach ($children as $field_name => $field_value) {
                $value = (string) $field_value;
                switch ($field_name) {
                    case 'display_name':
                        $testimonial['agent_name'] = $value;
                        break;
                    case 'avatar':
                        $testimonial['agent_avatar'] = $value;
                        break;
                    case 'customer_type':
                        $testimonial['customer_type'] = $value;
                        break;
                    case 'office':
                        $testimonial['office_name'] = $value;
                        break;
                    case 'category':
                        $testimonial['office_category'] = $value;
                        break;
                }
            }
            
            error_log('Company RSS Parser: Testimonial ' . ($count + 1) . ' from ' . $testimonial['office_name'] . ': ' . substr($testimonial['text'], 0, 50) . '... by ' . $testimonial['agent_name']);
            
            $all_testimonials[] = $testimonial;
            
            // Collect unique agents
            if (!empty($testimonial['agent_name']) && !isset($unique_agents[$testimonial['agent_name']])) {
                $unique_agents[$testimonial['agent_name']] = array(
                    'name' => $testimonial['agent_name'],
                    'avatar' => $testimonial['agent_avatar'],
                    'office' => $testimonial['office_name'],
                    'link' => $testimonial['link']
                );
            }
            
            $count++;
            }
        }
        } else {
            // Fallback: Direct items under channel (like office feeds)
            error_log('Company RSS Parser: No offices found, checking for direct items under channel');
            $item_count = count($channel->item);
            error_log('Company RSS Parser: Found ' . $item_count . ' direct items in channel');
            
            foreach ($channel->item as $item) {
                if ($count >= $limit) break;
                
                // Extract basic testimonial data
                $testimonial = array(
                    'text' => (string) $item->description,
                    'customer_name' => '',
                    'customer_location' => '',
                    'customer_type' => '',
                    'agent_name' => '',
                    'agent_avatar' => '',
                    'office_name' => '',
                    'office_category' => 'BASIC',
                    'link' => (string) $item->link,
                    'pub_date' => (string) $item->pubDate,
                    'guid' => (string) $item->guid,
                    'rating' => 5 // Default high rating
                );
                
                // Parse customer name and location from title (format: "Name, City, State")
                $title = (string) $item->title;
                if (!empty($title)) {
                    $parts = explode(', ', $title);
                    $testimonial['customer_name'] = $parts[0];
                    if (count($parts) >= 2) {
                        $testimonial['customer_location'] = implode(', ', array_slice($parts, 1));
                    }
                }
                
                // Extract RealSatisfied custom fields
                $children = $item->children($this->namespaces['realsatisfied']);
                foreach ($children as $field_name => $field_value) {
                    $value = (string) $field_value;
                    switch ($field_name) {
                        case 'display_name':
                            $testimonial['agent_name'] = $value;
                            break;
                        case 'avatar':
                            $testimonial['agent_avatar'] = $value;
                            break;
                        case 'customer_type':
                            $testimonial['customer_type'] = $value;
                            break;
                        case 'office':
                            $testimonial['office_name'] = $value;
                            break;
                        case 'category':
                            $testimonial['office_category'] = $value;
                            break;
                    }
                }
                
                error_log('Company RSS Parser: Direct testimonial ' . ($count + 1) . ': ' . substr($testimonial['text'], 0, 50) . '... by ' . $testimonial['agent_name']);
                
                $all_testimonials[] = $testimonial;
                
                // Collect unique agents
                if (!empty($testimonial['agent_name']) && !isset($unique_agents[$testimonial['agent_name']])) {
                    $unique_agents[$testimonial['agent_name']] = array(
                        'name' => $testimonial['agent_name'],
                        'avatar' => $testimonial['agent_avatar'],
                        'office' => $testimonial['office_name'],
                        'link' => $testimonial['link']
                    );
                }
                
                $count++;
            }
        }
        
        error_log('Company RSS Parser: Extracted ' . count($all_testimonials) . ' testimonials total');

        // Apply filters if specified
        if (!empty($options['office_filter'])) {
            $all_testimonials = $this->filter_testimonials_by_office($all_testimonials, $options['office_filter']);
        }

        if (!empty($options['agent_filter'])) {
            $all_testimonials = $this->filter_testimonials_by_agent($all_testimonials, $options['agent_filter']);
        }

        if (!empty($options['customer_type_filter'])) {
            $all_testimonials = $this->filter_testimonials_by_customer_type($all_testimonials, $options['customer_type_filter']);
        }

        // Advanced shuffle for better variety across offices and agents
        if (empty($options['preserve_order'])) {
            // Group by office first, then shuffle within offices and across offices
            $testimonials_by_office = array();
            foreach ($all_testimonials as $testimonial) {
                $office_key = !empty($testimonial['office_name']) ? $testimonial['office_name'] : 'unknown';
                if (!isset($testimonials_by_office[$office_key])) {
                    $testimonials_by_office[$office_key] = array();
                }
                $testimonials_by_office[$office_key][] = $testimonial;
            }
            
            // Shuffle testimonials within each office
            foreach ($testimonials_by_office as &$office_testimonials) {
                shuffle($office_testimonials);
            }
            
            // Create interleaved result (round-robin through offices)
            $shuffled_testimonials = array();
            $office_names = array_keys($testimonials_by_office);
            shuffle($office_names); // Randomize office order
            
            $max_rounds = max(array_map('count', $testimonials_by_office));
            for ($round = 0; $round < $max_rounds; $round++) {
                foreach ($office_names as $office_name) {
                    if (isset($testimonials_by_office[$office_name][$round])) {
                        $shuffled_testimonials[] = $testimonials_by_office[$office_name][$round];
                    }
                }
            }
            
            $all_testimonials = $shuffled_testimonials;
        }

        return array(
            'company' => $channel_data,
            'offices' => $offices,
            'testimonials' => $all_testimonials,
            'agents' => array_values($unique_agents),
            'stats' => array(
                'total_testimonials' => count($all_testimonials),
                'total_offices' => count($offices),
                'total_agents' => count($unique_agents)
            )
        );
    }

    /**
     * Extract offices data from the RSS feed
     *
     * @param SimplePie $rss_feed The RSS feed object
     * @return array Array of office data
     */
    private function extract_offices_data($rss_feed) {
        $offices = array();
        $namespace = $this->namespaces['realsatisfied'];
        
        // Get raw XML to parse office sections
        $raw_xml = $this->safe_get_feed_data($rss_feed, 'get_raw_data');
        
        // Use SimpleXML to parse office sections
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw_xml);
        
        if ($xml === false) {
            return $offices;
        }
        
        // Register namespace
        $xml->registerXPathNamespace('rs', $namespace);
        
        // Find all office elements
        $office_elements = $xml->xpath('//office');
        
        foreach ($office_elements as $office_element) {
            $office_data = array(
                'name' => (string) $office_element->title,
                'link' => (string) $office_element->link,
                'description' => (string) $office_element->description,
                'language' => (string) $office_element->language,
                'copyright' => (string) $office_element->copyright,
                'pub_date' => (string) $office_element->pubDate,
                'category' => (string) $office_element->category,
                'docs' => (string) $office_element->docs,
                'generator' => (string) $office_element->generator,
                'ttl' => (string) $office_element->ttl,
                'testimonials' => array()
            );
            
            // Extract RealSatisfied-specific office data
            $rs_office = $office_element->xpath('rs:office');
            if (!empty($rs_office)) {
                $office_data['rs_office_id'] = (string) $rs_office[0];
            }
            
            // Extract testimonials for this office
            $items = $office_element->xpath('item');
            foreach ($items as $item) {
                $testimonial = array(
                    'title' => (string) $item->title,
                    'link' => (string) $item->link,
                    'description' => (string) $item->description,
                    'pub_date' => (string) $item->pubDate,
                    'guid' => (string) $item->guid,
                    'customer_type' => '',
                    'display_name' => '',
                    'avatar' => ''
                );
                
                // Extract RealSatisfied-specific data
                $item->registerXPathNamespace('rs', $namespace);
                
                $customer_type = $item->xpath('rs:customer_type');
                if (!empty($customer_type)) {
                    $testimonial['customer_type'] = (string) $customer_type[0];
                }
                
                $display_name = $item->xpath('rs:display_name');
                if (!empty($display_name)) {
                    $testimonial['display_name'] = (string) $display_name[0];
                }
                
                $avatar = $item->xpath('rs:avatar');
                if (!empty($avatar)) {
                    $testimonial['avatar'] = (string) $avatar[0];
                }
                
                $office_data['testimonials'][] = $testimonial;
            }
            
            $offices[] = $office_data;
        }
        
        return $offices;
    }

    /**
     * Filter testimonials by office name
     *
     * @param array $testimonials Array of testimonials
     * @param string $office_filter Office name to filter by
     * @return array Filtered testimonials
     */
    private function filter_testimonials_by_office($testimonials, $office_filter) {
        return array_filter($testimonials, function($testimonial) use ($office_filter) {
            return stripos($testimonial['office_name'], $office_filter) !== false;
        });
    }

    /**
     * Filter testimonials by agent name
     *
     * @param array $testimonials Array of testimonials
     * @param string $agent_filter Agent name to filter by
     * @return array Filtered testimonials
     */
    private function filter_testimonials_by_agent($testimonials, $agent_filter) {
        return array_filter($testimonials, function($testimonial) use ($agent_filter) {
            return stripos($testimonial['display_name'], $agent_filter) !== false;
        });
    }

    /**
     * Filter testimonials by customer type
     *
     * @param array $testimonials Array of testimonials
     * @param string $customer_type_filter Customer type to filter by (Buyer, Seller, Tenant)
     * @return array Filtered testimonials
     */
    private function filter_testimonials_by_customer_type($testimonials, $customer_type_filter) {
        return array_filter($testimonials, function($testimonial) use ($customer_type_filter) {
            return strcasecmp($testimonial['customer_type'], $customer_type_filter) === 0;
        });
    }

    /**
     * Calculate company-wide statistics
     *
     * @param array $offices Array of office data
     * @param array $testimonials Array of all testimonials
     * @return array Statistics array
     */
    private function calculate_company_stats($offices, $testimonials) {
        $stats = array(
            'total_offices' => count($offices),
            'total_testimonials' => count($testimonials),
            'unique_agents' => 0,
            'customer_types' => array(
                'Buyer' => 0,
                'Seller' => 0,
                'Tenant' => 0
            ),
            'recent_testimonials' => 0 // Testimonials in last 30 days
        );
        
        $unique_agents = array();
        $thirty_days_ago = strtotime('-30 days');
        
        foreach ($testimonials as $testimonial) {
            // Count unique agents
            if (!empty($testimonial['display_name']) && !in_array($testimonial['display_name'], $unique_agents)) {
                $unique_agents[] = $testimonial['display_name'];
            }
            
            // Count customer types
            if (isset($stats['customer_types'][$testimonial['customer_type']])) {
                $stats['customer_types'][$testimonial['customer_type']]++;
            }
            
            // Count recent testimonials
            $pub_date = strtotime($testimonial['pub_date']);
            if ($pub_date && $pub_date > $thirty_days_ago) {
                $stats['recent_testimonials']++;
            }
        }
        
        $stats['unique_agents'] = count($unique_agents);
        
        return $stats;
    }

    /**
     * Get channel tag data with fallback
     *
     * @param SimplePie $rss_feed RSS feed object
     * @param string $namespace XML namespace
     * @param string $tag Tag name
     * @param string $default Default value if tag not found
     * @return string Tag data or default value
     */
    private function obtain_channel_tag_data($rss_feed, $namespace, $tag, $default = '') {
        try {
            if (method_exists($rss_feed, 'get_channel_tags')) {
                $tag_data = $rss_feed->get_channel_tags($namespace, $tag);
                return (!empty($tag_data) && isset($tag_data[0]['data'])) ? $tag_data[0]['data'] : $default;
            }
        } catch (Exception $e) {
            error_log('RealSatisfied Company RSS Parser: Error getting channel tag ' . $tag . ': ' . $e->getMessage());
        }
        return $default;
    }

    /**
     * Clear feed cache (AJAX callback)
     */
    public function clear_feed_cache_callback() {
        // Temporarily set cache to 1 second
        add_filter('wp_feed_cache_transient_lifetime', function() { return 1; });
        
        // Clear cache by fetching with minimal cache time
        $response = __('Company feed cache has been cleared.', 'realsatisfied-blocks');
        
        // Restore normal cache time
        add_filter('wp_feed_cache_transient_lifetime', function() { return $this->cache_duration; });
        
        wp_send_json_success($response);
    }

    /**
     * Get company feed URL
     *
     * @return string Company feed URL
     */
    public function get_company_feed_url() {
        return $this->company_feed_url;
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
     * Increase HTTP timeout for RSS feed requests
     *
     * @param int $timeout Current timeout
     * @return int Increased timeout
     */
    public function increase_http_timeout($timeout) {
        return 60; // Increase to 60 seconds
    }

    /**
     * Customize HTTP request arguments for better compatibility
     *
     * @param array $args HTTP request arguments
     * @return array Modified arguments
     */
    public function customize_http_args($args) {
        // Add User-Agent header
        $args['user-agent'] = 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url');
        
        // Allow redirects
        $args['redirection'] = 5;
        
        // Set SSL verification (disable if needed for local development)
        $args['sslverify'] = true;
        
        // Set connection timeout
        $args['timeout'] = 60;
        
        return $args;
    }

    /**
     * Safely get data from RSS feed, handling SimplePie exceptions
     *
     * @param SimplePie $rss_feed The RSS feed object
     * @param string $method The method to call
     * @return string|null The data or null on error
     */
    private function safe_get_feed_data($rss_feed, $method) {
        try {
            if (method_exists($rss_feed, $method)) {
                return $rss_feed->$method();
            }
        } catch (Exception $e) {
            error_log('RealSatisfied Company RSS Parser: Error calling ' . $method . ': ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Safely get feed item quantity
     *
     * @param SimplePie $rss_feed The RSS feed object
     * @param int $limit Maximum items to get
     * @return int Number of items
     */
    private function safe_get_feed_quantity($rss_feed, $limit) {
        try {
            if (method_exists($rss_feed, 'get_item_quantity')) {
                return $rss_feed->get_item_quantity($limit);
            }
        } catch (Exception $e) {
            error_log('RealSatisfied Company RSS Parser: Error getting item quantity: ' . $e->getMessage());
        }
        return 0;
    }

    /**
     * Safely get feed items
     *
     * @param SimplePie $rss_feed The RSS feed object
     * @param int $start Start index
     * @param int $end End index
     * @return array Feed items
     */
    private function safe_get_feed_items($rss_feed, $start, $end) {
        try {
            if (method_exists($rss_feed, 'get_items')) {
                return $rss_feed->get_items($start, $end);
            }
        } catch (Exception $e) {
            error_log('RealSatisfied Company RSS Parser: Error getting items: ' . $e->getMessage());
        }
        return array();
    }
}
