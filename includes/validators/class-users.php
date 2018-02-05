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
		$counts = self::count_users();

		$this->source_data = array(
			'counts' => $counts,
			'users'  => array(
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
	public function get_destination_data() {
		$counts = $this->get_remote_data( 'count' );

		$this->destination_data = array(
			'counts' => $counts,
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

	/**
	 * Get the count of users from this site.
	 *
	 * @since NEXT
	 * @return array
	 */
	private static function count_users() {
		$counts            = count_users();
		$prepared          = $counts['avail_roles'];
		$prepared['total'] = $counts['total_users'];
		return $prepared;
	}

	/**
	 * API handler for getting counts.
	 *
	 * @since NEXT
	 *
	 * @param  WP_REST_Request $rest_request The HTTP REST request.
	 * @return array
	 */
	protected static function handle_count_request( $rest_request ) {
		return self::count_users();
	}
}
