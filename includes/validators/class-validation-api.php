<?php

namespace Press_Sync\validators;

class Validation_API {

	/**
	 * The route for validators.
	 *
	 * @since NEXT
	 * @var string
	 */
	protected $route = 'validators';

	/**
	 * The endpoint for the validator.
	 *
	 * This should be set in your extending class and will make the API
	 * request route something like /press-sync/v1/{$route}/{$endpoint}.
	 *
	 * @since NEXT
	 * @var string
	 */
	protected $endpoint;

	/**
	 * Gets a response based on the incoming API request.
	 *
	 * @since NEXT
	 *
	 * @param WP_REST_Request $rest_request The incoming request.
	 */
	public static function get_api_response( $rest_request ) {
		try {
			$request = $rest_request->get_param( 'request' );
			$request = filter_var( $request, FILTER_SANITIZE_STRING );
			$handler = "handle_{$request}_request";
			$data    = static::{$handler}( $rest_request );

			wp_send_json_success( $data );
		} catch ( \Exception $e ) {
			$message = sprintf( __( 'There was an error processing the request: %s', 'press-sync' ), $e->getMessage() );
			trigger_error( $message, E_USER_ERROR );
			wp_send_json_error( $message );
		}
	}

}
