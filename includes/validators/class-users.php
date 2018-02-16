<?php
/**
 * User Validation
 *
 * @package PressSync
 */

namespace Press_Sync\validators;

use Press_Sync\validation\User as LocalUserData;
use Press_Sync\api\route\validation\User as RemoteUserData;
use Press_Sync\validation\ValidatorInterface;

/**
 * User validation class to get and compare results.
 */
class Users implements ValidatorInterface {
	use ValidationUtility;

	protected static $endpoint = 'users';

	public function __construct() {
		$this->local_data  = new LocalUserData();
		$this->remote_data = new RemoteUserData();
	}

	/**
	 * Get data from the source site.
	 *
	 * @since NEXT
	 */
	public function get_source_data() {
		$this->source_data = array(
			'counts'  => $this->local_data->get_count(),
			'samples' => $this->local_data->get_samples(),
		);
	}

	/**
	 * Get data from the destination site.
	 *
	 * @since NEXT
	 */
	public function get_destination_data() {
		$this->destination_data = array(
			'counts'  => $this->remote_data->get_data( 'count' ),
			'samples' => $this->remote_data->get_data( 'samples' ),
		);
	}

	/**
	 * Compare data from source and destination sites.
	 *
	 * @since NEXT
	 * @return array
	 */
	public function validate() {
		$this->get_source_data();
		$this->get_destination_data();

		$results = array(
			'counts'  => array(),
			'samples' => array(),
		);

		// Compare counts.
		foreach ( $this->source_data['counts'] as $count_key => $count ) {
			$results['counts'][ $count_key ] = $this->compare_counts( $count_key );
		}

		return $results;
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
