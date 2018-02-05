<?php
/**
 * User Validation
 *
 * @package PressSync
 */

namespace Press_Sync\validators;

/**
 * User validation class to get and compare results.
 */
class Users extends Validation_Utility implements Validation_Interface {
	protected static $endpoint = 'users';

	/**
	 * Get data from the source site.
	 *
	 * @since NEXT
	 */
	public function get_source_data() {
		$this->source_data = array(
			'counts' => array(
				'administrators' => 1,
				'editors'        => 23,
				'subscribers'    => 112,
			),
			'users' => array(
				'blah' => array(
					'ID'         => 453,
					'user_login' => 'blah',
					// ... other wp_users data
					'meta_input' => array(
						'admin_color' => 'fresh',
						// ... other wp_usermeta data
					),
					'additional_data' => array(
						'user_role' => 'Editor',
						'authored'  => array(
							'post'         => 52,
							'mtvn_sponsor' => 3,
							'page'         => 1,
						),
					),
				),
			),
		);

		/**
		$user_logins = wp_list_pluck( $user_sample, 'user_login' );

		// Send array of user_logins to remote site to get validation data.
		$remote_url  = $press_sync->get_remote_url( '', 'validate_users' );
		$remote_data = $press_sync->send_data_to_remote_site( $remote_url, array(
			'user_logins' => $user_logins,
		) );
		 */
	}

	/**
	 * Get data from the destination site.
	 *
	 * @since NEXT
	 */
	public function get_destintation_data() {
		$this->destination_data = array(
			'counts' => array(
				'administrators' => 1,
				'editors'        => 23,
				'subscribers'    => 111,
			),
			'samples' => array(
				'blah' => array(
					'ID'         => 332,
					'user_login' => 'blah',
					'meta_input' => array(
						'admin_color' => 'mocha',
					),
					'additional_data' => array(
						'user_role' => 'Editor',
						'authored'  => array(
							'post' => 51,
						),
					),
				),
			),
		);
	}

	/**
	 * Compare data from source and destination sites.
	 *
	 * @since NEXT
	 * @return array
	 */
	public function compare_results() {
		$this->get_source_data();
		$this->get_destintation_data();

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

	public static function get_api_response( $response ) {
		wp_send_json_success( 'blah' );
	}
}
