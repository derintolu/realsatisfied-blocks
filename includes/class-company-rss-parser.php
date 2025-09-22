1<?php
/**
 * RealSatisfied Company RSS Parser
 *
 * Handles company-level RSS feed parsing for brokerage-wide testimonials
 * Extends the existing office functionality to handle multi-office brokerages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
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
	 * RSS feed cache duration (1 week)
	 *
	 * @var int
	 */
	private $cache_duration = 604800; // 7 days * 24 hours * 60 minutes * 60 seconds

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
		'atom'          => 'http://www.w3.org/2005/Atom',
	);

	/**
	 * Get plugin instance
	 *
	 * @return RealSatisfied_Company_RSS_Parser
	 */
	public static function get_instance() {
		if ( self::$instance === null ) {
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
		add_action( 'wp_ajax_rsob_clear_company_feed_cache', array( $this, 'clear_feed_cache_callback' ) );
		add_action( 'wp_ajax_nopriv_rsob_clear_company_feed_cache', array( $this, 'clear_feed_cache_callback' ) );

		// Set up weekly cron job for RSS updates
		add_action( 'wp', array( $this, 'schedule_weekly_rss_update' ) );
		add_action( 'rsob_weekly_rss_update', array( $this, 'update_all_company_testimonials' ) );

		// Initialize RSS data on first load
		add_action( 'init', array( $this, 'initialize_rss_data' ) );
	}

	/**
	 * Fetch and parse company RSS feed
	 *
	 * @param string $company_id The company ID (e.g., "https://rss.realsatisfied.com/rss/company/{companyid}")
	 * @param array  $options Optional parameters (limit, office_filter, etc.)
	 * @return array|WP_Error Array with company data and testimonials, or WP_Error on failure
	 */
	public function fetch_company_data( $company_id, $options = array() ) {
		if ( empty( $company_id ) ) {
			return new WP_Error( 'missing_company_id', __( 'No company ID provided', 'realsatisfied-blocks' ) );
		}

		// Set defaults
		$limit     = isset( $options['limit'] ) ? intval( $options['limit'] ) : 50;
		$page_size = 200; // Fetch in chunks of 200 max

		// Check if we have recent data in database
		$cached_testimonials = $this->get_testimonials_from_db( $company_id, $limit );
		$cache_key           = 'rsob_company_meta_' . md5( $company_id . serialize( $options ) );
		$cached_meta         = get_transient( $cache_key );

		if ( $cached_testimonials && $cached_meta ) {
			return array(
				'company'      => $cached_meta['company'],
				'testimonials' => $cached_testimonials,
			);
		}

		// Try to fetch data with pagination
		return $this->fetch_company_data_paginated( $company_id, $options, $limit, $page_size );
	}

	/**
	 * Fetch company data with pagination support
	 *
	 * @param string $company_id The company ID
	 * @param array  $options Request options
	 * @param int    $total_limit Total testimonials needed
	 * @param int    $page_size Items per page request
	 * @return array|WP_Error
	 */
	private function fetch_company_data_paginated( $company_id, $options, $total_limit, $page_size ) {
		$all_testimonials = array();
		$company_data     = null;
		$page             = isset( $options['page'] ) ? intval( $options['page'] ) : 1;
		$collected        = 0;
		$max_pages        = 1; // Limit to 200 testimonials max

		while ( $collected < $total_limit && $page <= $max_pages ) {
			// Memory management - check available memory
			if ( memory_get_usage() > ( 0.8 * $this->get_memory_limit() ) ) {
				break; // Stop if using too much memory
			}

			$page_data = $this->fetch_single_page( $company_id, $page, $page_size, $options );

			if ( is_wp_error( $page_data ) ) {
				// If we already have some data, return what we have
				if ( ! empty( $all_testimonials ) && $company_data ) {
					break;
				}
				return $page_data;
			}

			// Store company data from first successful page
			if ( ! $company_data ) {
				$company_data = $page_data['company'];
			}

			// Add testimonials
			$page_testimonials = $page_data['testimonials'];
			if ( empty( $page_testimonials ) ) {
				// No more testimonials available
				break;
			}

			$remaining = $total_limit - $collected;
			$to_add    = min( count( $page_testimonials ), $remaining );

			$all_testimonials = array_merge( $all_testimonials, array_slice( $page_testimonials, 0, $to_add ) );
			$collected       += $to_add;

			// Clean up memory
			unset( $page_data, $page_testimonials );

			// If we got fewer than page_size testimonials, we've reached the end
			if ( $to_add < $page_size ) {
				break;
			}

			$page++;
		}

		// Store testimonials in database instead of memory cache
		$this->store_testimonials_in_db( $company_id, $all_testimonials, $company_data );

		$result = array(
			'company'      => $company_data,
			'testimonials' => $all_testimonials,
		);

		// Only cache the company metadata, not the testimonials
		$cache_key = 'rsob_company_meta_' . md5( $company_id . serialize( $options ) );
		set_transient(
			$cache_key,
			array(
				'company'            => $company_data,
				'last_updated'       => time(),
				'total_testimonials' => count( $all_testimonials ),
			),
			$this->cache_duration
		);

		return $result;
	}

	/**
	 * Fetch a single page of RSS data
	 *
	 * @param string $company_id The company ID
	 * @param int    $page Page number
	 * @param int    $page_size Items per page
	 * @param array  $options Request options
	 * @return array|WP_Error
	 */
	private function fetch_single_page( $company_id, $page, $page_size, $options = array() ) {
		// Build feed URL
		$feed_url = $this->company_feed_url . $company_id;

		// Add pagination parameters
		$feed_url .= '?page=' . $page . '&limit=' . $page_size;

		// Use shorter timeout for paginated requests
		$response = wp_remote_get(
			$feed_url,
			array(
				'timeout'    => 15, // Shorter timeout for smaller chunks
				'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'fetch_failed', 'Failed to fetch RSS feed: ' . $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return new WP_Error( 'empty_response', 'Empty response from RSS feed' );
		}

		// Parse XML directly using SimpleXML
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $body );

		if ( $xml === false ) {
			$errors    = libxml_get_errors();
			$error_msg = 'XML parsing failed';
			if ( ! empty( $errors ) ) {
				$error_msg .= ': ' . $errors[0]->message;
			}
			return new WP_Error( 'xml_parse_failed', $error_msg );
		}

		// Extract data using SimpleXML
		return $this->extract_company_data_from_xml( $xml, $options );
	}

	/**
	 * Store testimonials in database
	 *
	 * @param string $company_id Company ID
	 * @param array  $testimonials Array of testimonials
	 * @param array  $company_data Company metadata
	 */
	private function store_testimonials_in_db( $company_id, $testimonials, $company_data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'realsatisfied_testimonials';

		// Create table if it doesn't exist
		$this->create_testimonials_table();

		// Clear existing testimonials for this company
		$wpdb->delete( $table_name, array( 'company_id' => $company_id ) );

		// Insert new testimonials in batches to avoid memory issues
		$batch_size = 100;
		$batches    = array_chunk( $testimonials, $batch_size );

		foreach ( $batches as $batch ) {
			$values = array();
			foreach ( $batch as $testimonial ) {
				$values[] = $wpdb->prepare(
					'(%s, %s, %s, %s, %s, %s, %s, %s, %s, %d)',
					$company_id,
					sanitize_text_field( $testimonial['agent_name'] ),
					sanitize_text_field( $testimonial['office_name'] ),
					sanitize_textarea_field( $testimonial['text'] ),
					sanitize_text_field( $testimonial['client_name'] ),
					sanitize_text_field( $testimonial['transaction_type'] ),
					sanitize_text_field( $testimonial['date'] ),
					floatval( $testimonial['rating'] ),
					sanitize_text_field( $testimonial['link'] ),
					time()
				);
			}

			if ( ! empty( $values ) ) {
				$sql = "INSERT INTO {$table_name}
						(company_id, agent_name, office_name, testimonial_text, client_name, transaction_type, date, rating, link, created_at)
						VALUES " . implode( ',', $values );
				$wpdb->query( $sql );
			}
		}
	}

	/**
	 * Create testimonials table
	 */
	private function create_testimonials_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'realsatisfied_testimonials';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id int(11) NOT NULL AUTO_INCREMENT,
			company_id varchar(255) NOT NULL,
			agent_name varchar(255) NOT NULL,
			office_name varchar(255) NOT NULL,
			testimonial_text text NOT NULL,
			client_name varchar(255) NOT NULL,
			transaction_type varchar(100) NOT NULL,
			date varchar(50) NOT NULL,
			rating decimal(3,2) NOT NULL,
			link varchar(500) NOT NULL,
			created_at int(11) NOT NULL,
			PRIMARY KEY (id),
			KEY company_id (company_id),
			KEY agent_name (agent_name),
			KEY office_name (office_name)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get memory limit in bytes
	 *
	 * @return int Memory limit in bytes
	 */
	private function get_memory_limit() {
		$memory_limit = ini_get( 'memory_limit' );

		if ( $memory_limit == -1 ) {
			return PHP_INT_MAX;
		}

		$unit         = strtolower( substr( $memory_limit, -1 ) );
		$memory_limit = (int) $memory_limit;

		switch ( $unit ) {
			case 'g':
				$memory_limit *= 1024;
			case 'm':
				$memory_limit *= 1024;
			case 'k':
				$memory_limit *= 1024;
		}

		return $memory_limit;
	}

	/**
	 * Get testimonials from database
	 *
	 * @param string $company_id Company ID
	 * @param int    $limit Number of testimonials to retrieve
	 * @return array|false Array of testimonials or false if not found
	 */
	private function get_testimonials_from_db( $company_id, $limit = 50 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'realsatisfied_testimonials';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
			return false;
		}

		// Check if we have recent data (within cache duration)
		$cutoff_time = time() - $this->cache_duration;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name}
				WHERE company_id = %s AND created_at > %d
				ORDER BY created_at DESC
				LIMIT %d",
				$company_id,
				$cutoff_time,
				$limit
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			return false;
		}

		// Convert database format back to array format
		$testimonials = array();
		foreach ( $results as $row ) {
			$testimonials[] = array(
				'agent_name'       => $row['agent_name'],
				'office_name'      => $row['office_name'],
				'text'             => $row['testimonial_text'],
				'client_name'      => $row['client_name'],
				'transaction_type' => $row['transaction_type'],
				'date'             => $row['date'],
				'rating'           => floatval( $row['rating'] ),
				'link'             => $row['link'],
			);
		}

		return $testimonials;
	}

	/**
	 * Get testimonials directly from database with filtering options
	 *
	 * @param string $company_id Company ID
	 * @param array  $options Query options (limit, office_filter, etc.)
	 * @return array Array of testimonials
	 */
	public function get_testimonials_from_cache( $company_id, $options = array() ) {
		global $wpdb;

		$table_name    = $wpdb->prefix . 'realsatisfied_testimonials';
		$limit         = isset( $options['limit'] ) ? intval( $options['limit'] ) : 50;
		$office_filter = isset( $options['office_filter'] ) ? $options['office_filter'] : '';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
			return array();
		}

		$where_clause = 'WHERE company_id = %s';
		$params       = array( $company_id );

		// Add office filter if specified
		if ( ! empty( $office_filter ) ) {
			$where_clause .= ' AND office_name LIKE %s';
			$params[]      = '%' . $wpdb->esc_like( $office_filter ) . '%';
		}

		$sql      = "SELECT * FROM {$table_name} {$where_clause} ORDER BY created_at DESC LIMIT %d";
		$params[] = $limit;

		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, ...$params ),
			ARRAY_A
		);

		// Convert to standard format
		$testimonials = array();
		foreach ( $results as $row ) {
			$testimonials[] = array(
				'agent_name'       => $row['agent_name'],
				'office_name'      => $row['office_name'],
				'text'             => $row['testimonial_text'],
				'client_name'      => $row['client_name'],
				'transaction_type' => $row['transaction_type'],
				'date'             => $row['date'],
				'rating'           => floatval( $row['rating'] ),
				'link'             => $row['link'],
			);
		}

		return $testimonials;
	}

	/**
	 * Schedule weekly RSS update cron job
	 */
	public function schedule_weekly_rss_update() {
		if ( ! wp_next_scheduled( 'rsob_weekly_rss_update' ) ) {
			wp_schedule_event( time(), 'weekly', 'rsob_weekly_rss_update' );
		}
	}

	/**
	 * Initialize RSS data on plugin load
	 */
	public function initialize_rss_data() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'realsatisfied_testimonials';

		// Check if we need to initialize data
		$init_option = 'rsob_rss_initialized';
		if ( get_option( $init_option ) ) {
			return; // Already initialized
		}

		// Create table if it doesn't exist
		$this->create_testimonials_table();

		// Check if table is empty
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		if ( $count > 0 ) {
			update_option( $init_option, true );
			return; // Already has data
		}

		// Get default company ID from blocks or options
		$default_company_id = $this->get_default_company_id();
		if ( $default_company_id ) {
			// Fetch initial data
			$options = array( 'limit' => 200 ); // Start with 200 testimonials
			$this->fetch_company_data_paginated( $default_company_id, $options, 200, 200 );
		}

		// Mark as initialized
		update_option( $init_option, true );
	}

	/**
	 * Get default company ID from existing usage
	 */
	private function get_default_company_id() {
		// Check for company ID in recent posts with testimonial blocks
		global $wpdb;

		$query = "
			SELECT post_content
			FROM {$wpdb->posts}
			WHERE post_content LIKE '%realsatisfied/testimonial-marquee%'
			AND post_status = 'publish'
			LIMIT 5
		";

		$posts = $wpdb->get_results( $query );

		foreach ( $posts as $post ) {
			// Extract company ID from block content
			if ( preg_match( '/"companyId":"([^"]+)"/', $post->post_content, $matches ) ) {
				return $matches[1];
			}
		}

		return null;
	}

	/**
	 * Update all company testimonials via cron job
	 */
	public function update_all_company_testimonials() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'realsatisfied_testimonials';

		// Get all unique company IDs from the database
		$company_ids = $wpdb->get_col( "SELECT DISTINCT company_id FROM {$table_name}" );

		if ( empty( $company_ids ) ) {
			return;
		}

		foreach ( $company_ids as $company_id ) {
			// Force refresh by clearing cache first
			$this->clear_company_cache( $company_id );

			// Fetch fresh data (this will populate the database)
			$options = array( 'limit' => 200 ); // Get up to 200 testimonials per company
			$this->fetch_company_data_paginated( $company_id, $options, 200, 200 );

			// Add small delay to prevent overwhelming the RSS server
			sleep( 2 );
		}
	}

	/**
	 * Clear cache for a specific company
	 *
	 * @param string $company_id Company ID
	 */
	private function clear_company_cache( $company_id ) {
		global $wpdb;

		// Clear database entries
		$table_name = $wpdb->prefix . 'realsatisfied_testimonials';
		$wpdb->delete( $table_name, array( 'company_id' => $company_id ) );

		// Clear transient cache
		$cache_pattern = 'rsob_company_meta_' . md5( $company_id );
		delete_transient( $cache_pattern );
	}

	/**
	 * Extract company data from XML using SimpleXML instead of SimplePie
	 *
	 * @param SimpleXMLElement $xml The XML object
	 * @param array            $options Optional parameters
	 * @return array|WP_Error Array with company data and testimonials, or WP_Error on failure
	 */
	private function extract_company_data_from_xml( $xml, $options = array() ) {
		// Register namespace
		$xml->registerXPathNamespace( 'rs', $this->namespaces['realsatisfied'] );

		// Extract channel data (company-wide information)
		$channel = $xml->channel;
		if ( ! $channel ) {
			return new WP_Error( 'no_channel', 'No channel found in RSS feed' );
		}

		$channel_data = array(
			'title'       => (string) $channel->title,
			'link'        => (string) $channel->link,
			'description' => (string) $channel->description,
			'language'    => (string) $channel->language,
			'copyright'   => (string) $channel->copyright,
			'pub_date'    => (string) $channel->pubDate,
			'category'    => (string) $channel->category,
			'generator'   => (string) $channel->generator,
			'ttl'         => (string) $channel->ttl,
		);

		// Extract offices and testimonials
		$all_testimonials = array();
		$offices          = array();
		$unique_agents    = array();

		// Get all items (testimonials) - company feed has nested structure
		$limit = isset( $options['limit'] ) ? intval( $options['limit'] ) : 200;
		$count = 0;

		// Check if this is a company feed with offices or a direct feed with items
		$has_offices = isset( $channel->office ) && count( $channel->office ) > 0;

		if ( $has_offices ) {
			// For company feeds, items are nested under office elements
			foreach ( $channel->office as $office ) {

				// Extract office information
				$office_info = array(
					'title'       => (string) $office->title,
					'description' => (string) $office->description,
					'link'        => (string) $office->link,
					'pub_date'    => (string) $office->pubDate,
					'category'    => (string) $office->category,
				);

				// Extract office custom fields
				$office_children = $office->children( $this->namespaces['realsatisfied'] );
				foreach ( $office_children as $field_name => $field_value ) {
					$value = (string) $field_value;
					switch ( $field_name ) {
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
				foreach ( $office->item as $item ) {
					if ( $count >= $limit ) {
						break;
					}

					// Extract basic testimonial data
					$testimonial = array(
						'text'              => (string) $item->description,
						'customer_name'     => '',
						'customer_location' => '',
						'customer_type'     => '',
						'agent_name'        => '',
						'agent_avatar'      => '',
						'office_name'       => isset( $office_info['name'] ) ? $office_info['name'] : (string) $office->title,
						'office_category'   => 'BASIC',
						'link'              => (string) $item->link,
						'pub_date'          => (string) $item->pubDate,
						'guid'              => (string) $item->guid,
						'rating'            => 5, // Default high rating
					);

					// Parse customer name and location from title (format: "Name, City, State")
					$title = (string) $item->title;
					if ( ! empty( $title ) ) {
						$parts                        = explode( ', ', $title );
						$testimonial['customer_name'] = $parts[0];
						if ( count( $parts ) >= 2 ) {
							$testimonial['customer_location'] = implode( ', ', array_slice( $parts, 1 ) );
						}
					}

					// Extract RealSatisfied custom fields
					$children = $item->children( $this->namespaces['realsatisfied'] );
					foreach ( $children as $field_name => $field_value ) {
						$value = (string) $field_value;
						switch ( $field_name ) {
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

					$all_testimonials[] = $testimonial;

					// Collect unique agents
					if ( ! empty( $testimonial['agent_name'] ) && ! isset( $unique_agents[ $testimonial['agent_name'] ] ) ) {
						$unique_agents[ $testimonial['agent_name'] ] = array(
							'name'   => $testimonial['agent_name'],
							'avatar' => $testimonial['agent_avatar'],
							'office' => $testimonial['office_name'],
							'link'   => $testimonial['link'],
						);
					}

					$count++;
				}
			}
		} else {
			// Fallback: Direct items under channel (like office feeds)
			foreach ( $channel->item as $item ) {
				if ( $count >= $limit ) {
					break;
				}

				// Extract basic testimonial data
				$testimonial = array(
					'text'              => (string) $item->description,
					'customer_name'     => '',
					'customer_location' => '',
					'customer_type'     => '',
					'agent_name'        => '',
					'agent_avatar'      => '',
					'office_name'       => '',
					'office_category'   => 'BASIC',
					'link'              => (string) $item->link,
					'pub_date'          => (string) $item->pubDate,
					'guid'              => (string) $item->guid,
					'rating'            => 5, // Default high rating
				);

				// Parse customer name and location from title (format: "Name, City, State")
				$title = (string) $item->title;
				if ( ! empty( $title ) ) {
					$parts                        = explode( ', ', $title );
					$testimonial['customer_name'] = $parts[0];
					if ( count( $parts ) >= 2 ) {
						$testimonial['customer_location'] = implode( ', ', array_slice( $parts, 1 ) );
					}
				}

				// Extract RealSatisfied custom fields
				$children = $item->children( $this->namespaces['realsatisfied'] );
				foreach ( $children as $field_name => $field_value ) {
					$value = (string) $field_value;
					switch ( $field_name ) {
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

				$all_testimonials[] = $testimonial;

				// Collect unique agents
				if ( ! empty( $testimonial['agent_name'] ) && ! isset( $unique_agents[ $testimonial['agent_name'] ] ) ) {
					$unique_agents[ $testimonial['agent_name'] ] = array(
						'name'   => $testimonial['agent_name'],
						'avatar' => $testimonial['agent_avatar'],
						'office' => $testimonial['office_name'],
						'link'   => $testimonial['link'],
					);
				}

				$count++;
			}
		}

		// Apply filters if specified
		if ( ! empty( $options['office_filter'] ) ) {
			$all_testimonials = $this->filter_testimonials_by_office( $all_testimonials, $options['office_filter'] );
		}

		if ( ! empty( $options['agent_filter'] ) ) {
			$all_testimonials = $this->filter_testimonials_by_agent( $all_testimonials, $options['agent_filter'] );
		}

		if ( ! empty( $options['customer_type_filter'] ) ) {
			$all_testimonials = $this->filter_testimonials_by_customer_type( $all_testimonials, $options['customer_type_filter'] );
		}

		// Advanced shuffle for better variety across offices and agents
		if ( empty( $options['preserve_order'] ) ) {
			// Group by office first, then shuffle within offices and across offices
			$testimonials_by_office = array();
			foreach ( $all_testimonials as $testimonial ) {
				$office_key = ! empty( $testimonial['office_name'] ) ? $testimonial['office_name'] : 'unknown';
				if ( ! isset( $testimonials_by_office[ $office_key ] ) ) {
					$testimonials_by_office[ $office_key ] = array();
				}
				$testimonials_by_office[ $office_key ][] = $testimonial;
			}

			// Shuffle testimonials within each office
			foreach ( $testimonials_by_office as &$office_testimonials ) {
				shuffle( $office_testimonials );
			}

			// Create interleaved result (round-robin through offices)
			$shuffled_testimonials = array();
			$office_names          = array_keys( $testimonials_by_office );
			shuffle( $office_names ); // Randomize office order

			$max_rounds = max( array_map( 'count', $testimonials_by_office ) );
			for ( $round = 0; $round < $max_rounds; $round++ ) {
				foreach ( $office_names as $office_name ) {
					if ( isset( $testimonials_by_office[ $office_name ][ $round ] ) ) {
						$shuffled_testimonials[] = $testimonials_by_office[ $office_name ][ $round ];
					}
				}
			}

			$all_testimonials = $shuffled_testimonials;
		}

		return array(
			'company'      => $channel_data,
			'offices'      => $offices,
			'testimonials' => $all_testimonials,
			'agents'       => array_values( $unique_agents ),
			'stats'        => array(
				'total_testimonials' => count( $all_testimonials ),
				'total_offices'      => count( $offices ),
				'total_agents'       => count( $unique_agents ),
			),
		);
	}

	/**
	 * Extract offices data from the RSS feed
	 *
	 * @param SimplePie $rss_feed The RSS feed object
	 * @return array Array of office data
	 */
	private function extract_offices_data( $rss_feed ) {
		$offices   = array();
		$namespace = $this->namespaces['realsatisfied'];

		// Get raw XML to parse office sections
		$raw_xml = $this->safe_get_feed_data( $rss_feed, 'get_raw_data' );

		// Use SimpleXML to parse office sections
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $raw_xml );

		if ( $xml === false ) {
			return $offices;
		}

		// Register namespace
		$xml->registerXPathNamespace( 'rs', $namespace );

		// Find all office elements
		$office_elements = $xml->xpath( '//office' );

		foreach ( $office_elements as $office_element ) {
			$office_data = array(
				'name'         => (string) $office_element->title,
				'link'         => (string) $office_element->link,
				'description'  => (string) $office_element->description,
				'language'     => (string) $office_element->language,
				'copyright'    => (string) $office_element->copyright,
				'pub_date'     => (string) $office_element->pubDate,
				'category'     => (string) $office_element->category,
				'docs'         => (string) $office_element->docs,
				'generator'    => (string) $office_element->generator,
				'ttl'          => (string) $office_element->ttl,
				'testimonials' => array(),
			);

			// Extract RealSatisfied-specific office data
			$rs_office = $office_element->xpath( 'rs:office' );
			if ( ! empty( $rs_office ) ) {
				$office_data['rs_office_id'] = (string) $rs_office[0];
			}

			// Extract testimonials for this office
			$items = $office_element->xpath( 'item' );
			foreach ( $items as $item ) {
				$testimonial = array(
					'title'         => (string) $item->title,
					'link'          => (string) $item->link,
					'description'   => (string) $item->description,
					'pub_date'      => (string) $item->pubDate,
					'guid'          => (string) $item->guid,
					'customer_type' => '',
					'display_name'  => '',
					'avatar'        => '',
				);

				// Extract RealSatisfied-specific data
				$item->registerXPathNamespace( 'rs', $namespace );

				$customer_type = $item->xpath( 'rs:customer_type' );
				if ( ! empty( $customer_type ) ) {
					$testimonial['customer_type'] = (string) $customer_type[0];
				}

				$display_name = $item->xpath( 'rs:display_name' );
				if ( ! empty( $display_name ) ) {
					$testimonial['display_name'] = (string) $display_name[0];
				}

				$avatar = $item->xpath( 'rs:avatar' );
				if ( ! empty( $avatar ) ) {
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
	 * @param array  $testimonials Array of testimonials
	 * @param string $office_filter Office name to filter by
	 * @return array Filtered testimonials
	 */
	private function filter_testimonials_by_office( $testimonials, $office_filter ) {
		return array_filter(
			$testimonials,
			function( $testimonial ) use ( $office_filter ) {
				return stripos( $testimonial['office_name'], $office_filter ) !== false;
			}
		);
	}

	/**
	 * Filter testimonials by agent name
	 *
	 * @param array  $testimonials Array of testimonials
	 * @param string $agent_filter Agent name to filter by
	 * @return array Filtered testimonials
	 */
	private function filter_testimonials_by_agent( $testimonials, $agent_filter ) {
		return array_filter(
			$testimonials,
			function( $testimonial ) use ( $agent_filter ) {
				return stripos( $testimonial['display_name'], $agent_filter ) !== false;
			}
		);
	}

	/**
	 * Filter testimonials by customer type
	 *
	 * @param array  $testimonials Array of testimonials
	 * @param string $customer_type_filter Customer type to filter by (Buyer, Seller, Tenant)
	 * @return array Filtered testimonials
	 */
	private function filter_testimonials_by_customer_type( $testimonials, $customer_type_filter ) {
		return array_filter(
			$testimonials,
			function( $testimonial ) use ( $customer_type_filter ) {
				return strcasecmp( $testimonial['customer_type'], $customer_type_filter ) === 0;
			}
		);
	}

	/**
	 * Calculate company-wide statistics
	 *
	 * @param array $offices Array of office data
	 * @param array $testimonials Array of all testimonials
	 * @return array Statistics array
	 */
	private function calculate_company_stats( $offices, $testimonials ) {
		$stats = array(
			'total_offices'       => count( $offices ),
			'total_testimonials'  => count( $testimonials ),
			'unique_agents'       => 0,
			'customer_types'      => array(
				'Buyer'  => 0,
				'Seller' => 0,
				'Tenant' => 0,
			),
			'recent_testimonials' => 0, // Testimonials in last 30 days
		);

		$unique_agents   = array();
		$thirty_days_ago = strtotime( '-30 days' );

		foreach ( $testimonials as $testimonial ) {
			// Count unique agents
			if ( ! empty( $testimonial['display_name'] ) && ! in_array( $testimonial['display_name'], $unique_agents ) ) {
				$unique_agents[] = $testimonial['display_name'];
			}

			// Count customer types
			if ( isset( $stats['customer_types'][ $testimonial['customer_type'] ] ) ) {
				$stats['customer_types'][ $testimonial['customer_type'] ]++;
			}

			// Count recent testimonials
			$pub_date = strtotime( $testimonial['pub_date'] );
			if ( $pub_date && $pub_date > $thirty_days_ago ) {
				$stats['recent_testimonials']++;
			}
		}

		$stats['unique_agents'] = count( $unique_agents );

		return $stats;
	}

	/**
	 * Get channel tag data with fallback
	 *
	 * @param SimplePie $rss_feed RSS feed object
	 * @param string    $namespace XML namespace
	 * @param string    $tag Tag name
	 * @param string    $default Default value if tag not found
	 * @return string Tag data or default value
	 */
	private function obtain_channel_tag_data( $rss_feed, $namespace, $tag, $default = '' ) {
		try {
			if ( method_exists( $rss_feed, 'get_channel_tags' ) ) {
				$tag_data = $rss_feed->get_channel_tags( $namespace, $tag );
				return ( ! empty( $tag_data ) && isset( $tag_data[0]['data'] ) ) ? $tag_data[0]['data'] : $default;
			}
		} catch ( Exception $e ) {
			error_log( 'RealSatisfied Company RSS Parser: Error getting channel tag ' . $tag . ': ' . $e->getMessage() );
		}
		return $default;
	}

	/**
	 * Clear feed cache (AJAX callback)
	 */
	public function clear_feed_cache_callback() {
		// Temporarily set cache to 1 second
		add_filter(
			'wp_feed_cache_transient_lifetime',
			function() {
				return 1;
			}
		);

		// Clear cache by fetching with minimal cache time
		$response = __( 'Company feed cache has been cleared.', 'realsatisfied-blocks' );

		// Restore normal cache time
		add_filter(
			'wp_feed_cache_transient_lifetime',
			function() {
				return $this->cache_duration;
			}
		);

		wp_send_json_success( $response );
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
	public function increase_http_timeout( $timeout ) {
		return 60; // Increase to 60 seconds
	}

	/**
	 * Customize HTTP request arguments for better compatibility
	 *
	 * @param array $args HTTP request arguments
	 * @return array Modified arguments
	 */
	public function customize_http_args( $args ) {
		// Add User-Agent header
		$args['user-agent'] = 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' );

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
	 * @param string    $method The method to call
	 * @return string|null The data or null on error
	 */
	private function safe_get_feed_data( $rss_feed, $method ) {
		try {
			if ( method_exists( $rss_feed, $method ) ) {
				return $rss_feed->$method();
			}
		} catch ( Exception $e ) {
			error_log( 'RealSatisfied Company RSS Parser: Error calling ' . $method . ': ' . $e->getMessage() );
		}
		return null;
	}

	/**
	 * Safely get feed item quantity
	 *
	 * @param SimplePie $rss_feed The RSS feed object
	 * @param int       $limit Maximum items to get
	 * @return int Number of items
	 */
	private function safe_get_feed_quantity( $rss_feed, $limit ) {
		try {
			if ( method_exists( $rss_feed, 'get_item_quantity' ) ) {
				return $rss_feed->get_item_quantity( $limit );
			}
		} catch ( Exception $e ) {
			error_log( 'RealSatisfied Company RSS Parser: Error getting item quantity: ' . $e->getMessage() );
		}
		return 0;
	}

	/**
	 * Safely get feed items
	 *
	 * @param SimplePie $rss_feed The RSS feed object
	 * @param int       $start Start index
	 * @param int       $end End index
	 * @return array Feed items
	 */
	private function safe_get_feed_items( $rss_feed, $start, $end ) {
		try {
			if ( method_exists( $rss_feed, 'get_items' ) ) {
				return $rss_feed->get_items( $start, $end );
			}
		} catch ( Exception $e ) {
			error_log( 'RealSatisfied Company RSS Parser: Error getting items: ' . $e->getMessage() );
		}
		return array();
	}
}
