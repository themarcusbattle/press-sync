<?php

namespace Press_Sync\api\route\validation;

use Press_Sync\api\route\AbstractRoute;

/**
 * Class User
 *
 * @package Press_Sync\api\route\validation
 * @since NEXT
 */
class User extends AbstractRoute {
	/**
	 * User constructor.
	 *
	 * @since NEXT
	 */
	public function __construct() {
		$this->rest_base   = 'validation/user';
		$this->data_source = new \Press_Sync\validation\User();
		$this->routes['count']    = array(
			'callback' => array( $this->data_source, 'get_count' ),
		);
	}

	/**
	 * Register endpoint with WordPress.
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}
}
