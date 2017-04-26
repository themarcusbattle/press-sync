<?php

use Press_Sync_API_Validator as Validator;

/**
 * Class Press_Sync_API_Route_User
 */
class Press_Sync_API_Route_User extends WP_REST_Controller {
	/**
	 * @var Press_Sync_API_Validator
	 */
	protected $validator;

	/**
	 * Press_Sync_API_Route_User constructor.
	 *
	 * @param Press_Sync_API_Validator $validator
	 */
	public function __construct( Validator $validator ) {
		$this->validator = $validator;
	}

	/**
	 *
	 */
	public function register_routes() {
		register_rest_route( 'press-sync/v1', '/user', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'insert_new_user' ),
			'permission_callback' => array( $this->validator, 'validate_sync_key' ),
		) );
	}

	/**
	 * @param $request
	 */
	public function insert_new_user( $request ) {

		$user_args = $request->get_params();
		$username = isset( $user_args['user_login'] ) ? $user_args['user_login'] : '';

		// Check to see if the user exists
		$user = get_user_by( 'login', $username );

		if ( ! $user ) {

			$user_id = wp_insert_user( $user_args );

			if ( is_wp_error( $user_id ) ) {
				return wp_send_json_error();
			}

			$user = get_user_by( 'id', $user_id );

		} else {
			$user_id = $user->ID;
		}

		// Update the meta
		foreach ( $user_args['meta_input'] as $usermeta_key => $usermeta_value ) {
			update_user_meta( $user_id, $usermeta_key, $usermeta_value );
		}

		// Asign user role
		$user->add_role( $user_args['role'] );

		// Prepare response
		$data['user_id'] = $user_id;

		return wp_send_json_success( $data );

	}
}
