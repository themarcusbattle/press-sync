<?php
namespace Press_Sync\api\route\validation;

use Press_Sync\api\route\AbstractRoute;

/**
 * Class Post
 *
 * @package Press_Sync\api\route\validation
 * @since NEXT
 */
class Post extends AbstractRoute {
	/**
	 * @var \Press_Sync\validation\Post
	 *
	 * @since NEXT
	 */
	protected $data_source;

	/**
	 * Post constructor.
	 *
	 * @since NEXT
	 */
	public function __construct() {
		$this->rest_base   = 'validation/post';
		$this->data_source = new \Press_Sync\validation\Post();
	}

	/**
	 * Register hooks for Post validation.
	 *
	 * @since NEXT
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes for Post validation.
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
