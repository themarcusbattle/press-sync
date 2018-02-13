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
		$this->routes      = array(
			'count'  => array(
				'callback' => array( $this->data_source, 'get_count' ),
			),
			'sample' => array(
				'callback' => array( $this, 'get_sample' ),
				'args'     => array(
					'type'           => array( 'required' => true ),
					'count'          => array( 'required' => false ),
					'ids'            => array( 'required' => false ),
					'press_sync_key' => array( 'required' => true ),
				),
			),
		);
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
	 * Get a sample of post data.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_sample( \WP_REST_Request $request ) {
		$params = array(
			'count' => $request->get_param( 'count' ),
			'ids'   => $request->get_param( 'ids' ),
			'type'  => $request->get_param( 'type' ),
		);

		if ( $params['count'] ) {
			$callback = "get_sample_{$params['type']}_data";
			return $this->data_source->{$callback}( $params['count'] );
		}

		if ( $params['ids'] ) {
			$callback = "get_comparison_{$params['type']}";
			return $this->data_source->{$callback}( $params['ids'] );
		}

		return [];
	}
}
