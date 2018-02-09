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
	protected $source_data = array();

	/**
	 * Return the validation data.
	 *
	 * @since NEXT
	 * @return array
	 */
	public function validate() {
		$return = array(
			'source'      => $this->get_source_data(),
			'destination' => $this->get_destination_data(),
			'comparison'  => array(),
		);

		$return['comparison'] = $this->compare_data( $return['source'], $return['destination'] );
		return $return;
	}

	/**
	 * Get data from the source site.
	 *
	 * @since NEXT
	 * @return array
	 */
	public function get_source_data() {
		$this->source_data = ( new User( $this->args ) )->get_data();
		return $this->source_data;
	}

	/**
	 * Get data from the destination site.
	 *
	 * @since NEXT
	 * @return array
	 */
	public function get_destination_data() {
		return array(
			'count'  => API::get_remote_data( 'validation/user/count' ),
			'sample' => $this->get_destination_samples(),
		);
	}

	/**
	 * Get samples from the destination site based on source data.
	 *
	 * @since NEXT
	 * @return array
	 */
	private function get_destination_samples() {
		$args = array(
			'source_users' => array(),
		);

		foreach ( $this->source_data['sample'] as $user ) {
			$args['source_users'][] = array(
				'ID'         => $user->ID,
				'user_login' => $user->data->user_login,
				'user_email' => $user->data->user_email,
			);
		}

		return API::get_remote_data( 'validation/user/samples', $args );
	}

	/**
	 * Compares source and destination data.
	 *
	 * @since NEXT
	 * @param  array $source      The source dataset.
	 * @param  array $destination The destination dataset.
	 * @return array
	 */
	public function compare_data( array $source, array $destination ) {
		return array(
			'count' => $this->compare_counts( $source['count'], $destination['count'] ),
		);
	}

	/**
	 * Compare counts between source and destination.
	 *
	 * @since NEXT
	 * @param  array $source      Source counts.
	 * @param  array $destination Destination counts.
	 * @return array
	 */
	private function compare_counts( $source, $destination ) {
		$comparison = array();

		foreach ( $source as $role => $src_count ) {
			$dest_count = 0;

			if ( isset( $destination[ $role ] ) ) {
				$dest_count = $destination[ $role ];
			}

			$comparison[ $role ] = $this->apply_diff_to_values( $src_count, $dest_count );
		}

		return $comparison;
	}
}
