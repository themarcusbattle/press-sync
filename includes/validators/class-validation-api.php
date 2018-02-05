<?php

namespace Press_Sync\validators;

use Press_Sync\API;

class Validation_API {

	/**
	 * The route for validators.
	 *
	 * @since NEXT
	 * @var string
	 */
	protected static $route = 'validators';

	/**
	 * The endpoint for the validator.
	 *
	 * This should be set in your extending class and will make the API
	 * request route something like /press-sync/v1/{$route}/{$endpoint}.
	 *
	 * @since NEXT
	 * @var string
	 */
	protected static $endpoint;

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

	/**
	 * Registers API endpoints for the extending class.
	 *
	 * This registers an API endpoint at /namespace/route/endpoint/ based on the extending
	 * class's properties. The class should implement a static public method called
	 * get_api_response that can parse the request parameters and return the desired data.
	 *
	 * @since NEXT
	 */
	public static function register_api_endpoints() {
		if ( empty( static::$endpoint ) ) {
			trigger_error( __( 'Validation classes must define an endpoint.', 'press-sync' ), E_USER_ERROR );
		}

		$route    = static::$route;
		$endpoint = static::$endpoint;

		register_rest_route( \Press_Sync\API::NAMESPACE, "/{$route}/{$endpoint}", array(
			'methods'             => array( 'GET' ),
			'callback'            => static::class . '::get_api_response',
			'permission_callback' => array( \Press_Sync\Press_Sync::init()->api, 'validate_sync_key' ),
			'args'                => array(
				'request' => array(
					'required' => true,
				),
				'press_sync_key' => array(
					'required' => true,
				),
			),
		) );
	}

	/**
	 * Get remote data from an API request.
	 *
	 * @since NEXT
	 * @param  string $request The requested datapoint.
	 * @return array
	 */
	public function get_remote_data( $request ) {
		$route    = static::$route;
		$endpoint = static::$endpoint;

		$url = API::get_remote_url( '', "{$route}/{$endpoint}", array(
			'request' => $request,
		) );

		$response = API::get_remote_response( $url );

		if ( empty( $response['body']['success'] ) ) {
			return array();
		}

		return $response['body']['data'];
	}
}
