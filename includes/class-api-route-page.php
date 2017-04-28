<?php

use Press_Sync_API_Validator as Validator;
use Press_Sync_Data_Synchronizer as Synchronizer;

/**
 * Class Press_Sync_API_Route_Page
 */
class Press_Sync_API_Route_Page extends Press_Sync_API_Abstract_Route {
	/**
	 * Press_Sync_API_Route_Page constructor.
	 *
	 * @param Press_Sync_API_Validator     $validator    Data validation helper class.
	 * @param Press_Sync_Data_Synchronizer $synchronizer Data synchronization helper class.
	 */
	public function __construct( Validator $validator, Synchronizer $synchronizer ) {
		$this->validator    = $validator;
		$this->synchronizer = $synchronizer;
		$this->rest_base    = 'page';
	}

	/**
	 * Register endpoints for this API route.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, $this->rest_base, array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this->synchronizer, 'sync_objects' ),
			'permission_callback' => array( $this->validator, 'validate_sync_key' ),
		) );
	}
}
