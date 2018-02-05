<?php
/**
 * Validation Utility abstraction.
 *
 * @package PressSync
 */

namespace Press_Sync\validators;

/**
 * This class defines utility methods that are common to all Validators.
 */
abstract class Validation_Utility {

	protected static $route = 'validation';
	protected static $endpoint;

	/**
	 * Get an icon based on whether a result was (bool) true or not.
	 *
	 * @since NEXT
	 * @param  bool   $result The result to test.
	 * @return string
	 */
	protected function get_result_icon( $result ) {
		return ((bool) $result) === true ? '✅' : '❌';
	}

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
}
