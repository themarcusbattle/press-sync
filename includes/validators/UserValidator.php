<?php
/**
 * User Validation
 *
 * @package PressSync
 */
namespace Press_Sync\validators;

use Press_Sync\API;
use Press_Sync\validation\ValidatorInterface;
use Press_Sync\validation\User;

/**
 * User validation class to get and compare results.
 */
class UserValidator extends AbstractValidator implements ValidatorInterface {
	public function validate() {
		return array(
			'source'      => $this->get_source_data(),
			'destination' => $this->get_destination_data(),
		);
	}

	/**
	 * Get data from the source site.
	 *
	 * @since NEXT
	 */
	public function get_source_data() {
		return ( new User() )->get_count();
	}

	/**
	 * Get data from the destination site.
	 *
	 * @since NEXT
	 */
	public function get_destination_data() {
		return API::get_remote_data( 'validation/user/count' );
	}
}
