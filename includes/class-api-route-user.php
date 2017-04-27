<?php

use Press_Sync_API_Validator as Validator;
use Press_Sync_Data_Synchronizer as Synchronizer;

/**
 * Class Press_Sync_API_Route_User
 */
class Press_Sync_API_Route_User extends WP_REST_Controller {
	/**
	 * Data validation helper class.
	 *
	 * @var Press_Sync_API_Validator
	 */
	protected $validator;

	/**
	 * @var Press_Sync_Data_Synchronizer
	 */
	protected $synchronizer;

	/**
	 * Press_Sync_API_Route_User constructor.
	 *
	 * @param Press_Sync_API_Validator     $validator    Data validation helper class.
	 * @param Press_Sync_Data_Synchronizer $synchronizer Data synchronization helper class.
	 */
	public function __construct( Validator $validator, Synchronizer $synchronizer ) {
		$this->validator    = $validator;
		$this->synchronizer = $synchronizer;
	}

	/**
	 * Register endpoints for this API route.
	 */
	public function register_routes() {
		register_rest_route( 'press-sync/v1', '/user', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this->synchronizer, 'sync_objects' ),
			'permission_callback' => array( $this->validator, 'validate_sync_key' ),
		) );
	}
}
