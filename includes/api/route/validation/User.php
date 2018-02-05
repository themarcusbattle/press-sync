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
	 * Register routes for this API endpoint.
	 */
	public function register_routes() {
		$routes = array(
			'count' => array( $this, 'count_users' ),
		);

		register_rest_route( $this->namespace, "{$this->rest_base}/count", [
			'methods'             => [ 'GET' ],
			'callback'            => array( new \Press_Sync\validation\User(), 'get_count' ),
			'permission_callback' => [ $this, 'validate_sync_key' ],
			'args'                => [
				'press_sync_key' => [
					'required' => true,
				],
			],
		] );
	}
}
