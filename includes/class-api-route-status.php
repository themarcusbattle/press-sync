<?php

use Press_Sync_API_Validator as Validator;

/**
 * Class Press_Sync_API_Route_Status
 */
class Press_Sync_API_Route_Status extends WP_REST_Controller {
	/**
	 * @var Validator
	 */
	private $validator;

	/**
	 * Press_Sync_API_Route_Status constructor.
	 *
	 * @param Press_Sync_API_Validator $validator
	 */
	public function __construct( Validator $validator ) {
		$this->validator = $validator;
	}

	/**
	 *
	 */
	public function register_routes() {
		register_rest_route( 'press-sync/v1', '/status', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_connection_status_via_api' ),
			'validate_callback' => array( $this->validator, 'validate_sync_key' ),
		) );
	}

	/**
	 * Gets the connection status via API request
	 *
	 * @since 0.1.0
	 * @return JSON
	 */
	public function get_connection_status_via_api() {
		wp_send_json_success();
	}
}
