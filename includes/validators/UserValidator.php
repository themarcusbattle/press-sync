<?php
/**
 * User Validation
 *
 * @package PressSync
 */
namespace Press_Sync\validators;

/*
 * use Press_Sync\validation\User as LocalUserData;
use Press_Sync\api\route\validation\User as RemoteUserData;
use Press_Sync\validation\ValidatorInterface;
 */

use Press_Sync\API;
use Press_Sync\validation\ValidatorInterface;
use Press_Sync\validation\Taxonomy;

/**
 * User validation class to get and compare results.
 */
class UserValidator extends AbstractValidator implements ValidatorInterface {
	public function validate() {
		/**
		 *

		// Compare counts.
		foreach ( $this->source_data['counts'] as $count_key => $count ) {
			$results['counts'][ $count_key ] = $this->compare_counts( $count_key );
		}
		 */

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
		API::get_remote_data( 'validation/user/count' );
	}

	/**
	 * Method to compare counts.
	 *
	 * @since NEXT
	 * @param  string $key The count key to compare.
	 * @return string
	 */
	private function compare_counts( $key ) {
		$source_count      = absint( $this->source_data['counts'][ $key ] );
		$destination_count = absint( $this->destination_data['counts'][ $key ] );

		$icon = $this->get_result_icon( $source_count === $destination_count );
		return sprintf( '%s Count of %s: %d vs %d', $icon, $key, $source_count, $destination_count );
	}
}
