<?php
namespace Press_Sync\api\route\validation;

use Press_Sync\api\route\AbstractRoute;

/**
 * Class Taxonomy
 *
 * @package Press_Sync\api\route\validation
 * @since NEXT
 */
class Taxonomy extends AbstractRoute {
	/**
	 * Taxonomy validation constructor.
	 *
	 * @since NEXT
	 */
	public function __construct() {
		$this->rest_base = 'validation/taxonomy';
	}

	/**
	 * Register hooks for Taxonomy validation.
	 *
	 * @since NEXT
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes for Taxonomy validation.
	 *
	 * @since NEXT
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, "{$this->rest_base}/count", [
			'methods'             => [ 'GET' ],
			'callback'            => array( new \Press_Sync\validation\Taxonomy(), 'get_count' ),
			'permission_callback' => [ $this, 'validate_sync_key' ],
			'args'                => [
				'press_sync_key' => [
					'required' => true,
				],
			],
		] );
	}
}
