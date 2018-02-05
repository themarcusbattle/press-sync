<?php
/**
 * Primary validation class for Press Sync.
 *
 * @package PressSync
 */

namespace Press_Sync;

use Press_Sync\validators;

/**
 * Validation class to manage overall actions of Validators.
 */
class Validation {

	/**
	 * Option key used to store the current object validation type.
	 *
	 * @since NEXT
	 * @var string
	 */
	const VALIDATION_OPTION = 'ps_current_validation';

	/**
	 * Define an array of available Validators.
	 *
	 * @TODO can we pull this info from the ./validators/ directory instead? Maybe.
	 *
	 * @since NEXT
	 * @var array
	 */
	private static $valid_types = array(
		'posts' => array(
			'label'       => 'Posts',
			'description' => 'Validate a sample of published Posts.',
		),
		'users' => array(
			'label'       => 'Users',
			'description' => 'Validate a random sample of users across different roles.',
		),
	);

	/**
	 * The type of validation operation we're currently doing.
	 *
	 * @since NEXT
	 * @var bool
	 */
	private static $validation_type = false;

	/**
	 * Check to see if we're running a validation.
	 *
	 * @since NEXT
	 * @return bool
	 */
	public static function is_validating() {
		$current_validation = get_option( self::VALIDATION_OPTION );

		if ( ! $current_validation ) {
			return false;
		}

		// If we're validating, find out what we're validating and delete the option.
		$current_validation = explode( ' ', strtolower( $current_validation ) );
		self::$validation_type = array_pop( $current_validation );
		delete_option( self::VALIDATION_OPTION );

		return true;
	}

	/**
	 * Static call method to return private variables.
	 *
	 * @since NEXT
	 * @param  string $method The static method being called.
	 * @param  array  $args   Arguments passed to the method.
	 * @return mixed
	 */
	public static function __callStatic( $method, $args ) {
		if ( 'get_' !== substr( $method, 0, 4 ) ) {
			return;
		}

		$what = substr( $method, 4 );

		if ( ! isset( self::${$what} ) ) {
			return;
		}

		return self::${$what};
	}

	/**
	 * Get results of a validation request.
	 *
	 * @since NEXT
	 * @return string
	 */
	public static function get_validation_results() {
		$validation_class = '\Press_Sync\validators\\' . ucwords( self::$validation_type, '_' ); // Validate_Users
		$validator = new $validation_class();
		return $validator->compare_results();
	}

	public static function register_api_endpoints() {
		static $validators = array(
			'Users',
		);

		foreach ( $validators as $validation_class ) {
			$validation_class::register_api_endpoints();
		}
	}
}
