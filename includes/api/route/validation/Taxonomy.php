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
		$this->routes      = array(
			'count'  => array(
				'callback' => array( $this->data_source, 'get_count' ),
			),
			'sample' => array(
				'callback' => array( $this, 'get_sample' ),
				'args'     => array(
					'type'  => array( 'required' => true ),
				),
			),
		);
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
	 * Get sample taxonomy data.
	 *
	 * @param \WP_REST_Request $request The API request.
	 *
	 * @return array|\WP_Error
	 */
	public function get_sample( \WP_REST_Request $request ) {
		$type = filter_var( $request->get_param( 'type' ), FILTER_SANITIZE_STRING );

		if ( 'meta' === $type ) {
			return $this->data_source->get_sample_meta();
		}

		return new \WP_Error( 'taxonomy_type_not_found', 'Invalid type parameter.', array( 'status' => 404 ) );
	}
}
