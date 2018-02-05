<?php

namespace Press_Sync\api\route\validation;

use Press_Sync\api\route\AbstractRoute;

/**
 * Class User
 *
 * @package Press_Sync\api\route\validation
 */
class User extends AbstractRoute {
	/**
	 * User constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->namespace = 'press-sync/v1';
		$this->rest_base = 'validation/user';
	}

	/**
	 *
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Gets a response based on the incoming API request.
	 *
	 * @since NEXT
	 *
	 * @param WP_REST_Request $rest_request The incoming request.
	 */
	public static function get_api_response( \WP_REST_Request $rest_request ) {
		try {
			$request = $rest_request->get_param( 'request' );
			$request = filter_var( $request, FILTER_SANITIZE_STRING );
			$handler = "handle_{$request}_request";
			$data    = static::{$handler}( $rest_request );

			wp_send_json_success( $data );
		} catch ( \Exception $e ) {
			$message = sprintf( __( 'There was an error processing the request: %s', 'press-sync' ), $e->getMessage() );
			trigger_error( $message, E_USER_ERROR );
			wp_send_json_error( $message );
		}
	}

	/**
	 *
	 */
	public function register_routes() {
		$routes = array(
			'count' => array( $this, 'count_users' ),
		);

		register_rest_route( $this->namespace, "{$this->rest_base}/count", [
			'methods'             => [ 'GET' ],
			'callback'            => array( $this, 'count_users' ),
			'permission_callback' => [ \Press_Sync\Press_Sync::init()->api, 'validate_sync_key' ],
			'args'                => [
				'press_sync_key' => [
					'required' => true,
				],
			],
		] );
	}
}
