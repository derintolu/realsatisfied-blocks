<?php
/**
 * Cloudflare Testimonials Client
 *
 * Fetches testimonials from Cloudflare Worker API instead of RSS directly
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cloudflare Testimonials Client Class
 */
class Cloudflare_Testimonials_Client {

	/**
	 * Plugin instance
	 *
	 * @var Cloudflare_Testimonials_Client
	 */
	private static $instance = null;

	/**
	 * Cloudflare Worker API URL
	 *
	 * @var string
	 */
	private $api_url = 'https://realsatisfied-testimonials.century-21-real-estate-alliance-group1007.workers.dev';

	/**
	 * Cache duration (1 hour - Worker already caches for 7 days)
	 *
	 * @var int
	 */
	private $cache_duration = 3600;

	/**
	 * Get plugin instance
	 *
	 * @return Cloudflare_Testimonials_Client
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
		// Allow API URL to be configured
		$this->api_url = defined( 'RSOB_CLOUDFLARE_API_URL' ) ? RSOB_CLOUDFLARE_API_URL : $this->api_url;
	}

	/**
	 * Fetch testimonials from Cloudflare Worker
	 *
	 * @param string $company_id Company ID
	 * @param array  $options Optional parameters
	 * @return array|WP_Error Array of testimonials or error
	 */
	public function fetch_testimonials( $company_id, $options = array() ) {
		if ( empty( $company_id ) ) {
			return new WP_Error( 'missing_company_id', 'No company ID provided' );
		}

		// Check WordPress cache first
		$cache_key = 'cf_testimonials_' . md5( $company_id . serialize( $options ) );
		$cached_data = get_transient( $cache_key );

		if ( $cached_data !== false ) {
			return $cached_data;
		}

		// Fetch from Cloudflare Worker
		$api_endpoint = $this->api_url . '/api/testimonials?company_id=' . urlencode( $company_id );

		$response = wp_remote_get(
			$api_endpoint,
			array(
				'timeout' => 5, // Fast timeout - Cloudflare Worker is quick
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Cloudflare API error: ' . $response->get_error_message() );
			return new WP_Error( 'api_error', 'Failed to fetch testimonials from API' );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data ) || isset( $data['error'] ) ) {
			error_log( 'Cloudflare API returned error: ' . ( $data['error'] ?? 'Empty response' ) );
			return new WP_Error( 'api_error', $data['error'] ?? 'Invalid API response' );
		}

		// Extract testimonials
		$testimonials = $data['data']['testimonials'] ?? array();

		// Apply local filters if needed
		if ( ! empty( $options['limit'] ) ) {
			$testimonials = array_slice( $testimonials, 0, $options['limit'] );
		}

		// Cache the result
		set_transient( $cache_key, $testimonials, $this->cache_duration );

		return $testimonials;
	}

	/**
	 * Get API status
	 *
	 * @return array Status information
	 */
	public function get_status() {
		$response = wp_remote_get(
			$this->api_url . '/api/status',
			array(
				'timeout' => 3,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
	}

	/**
	 * Refresh testimonials (requires API key)
	 *
	 * @param string $company_id Company ID
	 * @param string $api_key API key for authentication
	 * @return bool Success status
	 */
	public function refresh_testimonials( $company_id, $api_key ) {
		$response = wp_remote_get(
			$this->api_url . '/api/refresh?company_id=' . urlencode( $company_id ),
			array(
				'timeout' => 35, // Longer timeout for refresh
				'headers' => array(
					'X-API-Key' => $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Refresh error: ' . $response->get_error_message() );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Clear local cache on successful refresh
		if ( $data['success'] ?? false ) {
			$cache_key = 'cf_testimonials_' . md5( $company_id );
			delete_transient( $cache_key );
		}

		return $data['success'] ?? false;
	}
}