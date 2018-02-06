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
	 * Data source for the API.
	 *
	 * @var \Press_Sync\validation\Taxonomy
	 * @since NEXT
	 */
	protected $data_source;

	/**
	 * Taxonomy validation constructor.
	 *
	 * @since NEXT
	 */
	public function __construct() {
		$this->rest_base   = 'validation/taxonomy';
		$this->data_source = new \Press_Sync\validation\Taxonomy();
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
			'callback'            => array( $this->data_source, 'get_count' ),
			'permission_callback' => [ $this, 'validate_sync_key' ],
			'args'                => [
				'press_sync_key' => [
					'required' => true,
				],
			],
		] );
	}
}
