<?php
use Press_Sync_API_Validator as Validator;
use Press_Sync_Data_Synchronizer as Synchronizer;

/**
 * Class Press_Sync_API_Route_Attachment
 */
class Press_Sync_API_Route_Attachment extends Press_Sync_API_Abstract_Route {
	/**
	 * Press_Sync_API_Route_Attachment constructor.
	 *
	 * @param Press_Sync_API_Validator     $validator    Data validation helper class.
	 * @param Press_Sync_Data_Synchronizer $synchronizer Post synchronization helper class.
	 */
	public function __construct( Validator $validator, Synchronizer $synchronizer ) {
		$this->validator    = $validator;
		$this->synchronizer = $synchronizer;
		$this->rest_base    = 'attachment';
	}

	/**
	 * Register endpoints for this API route.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, $this->rest_base, array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'sync_objects' ),
			'permission_callback' => array( $this->validator, 'validate_sync_key' ),
		) );
	}
}
