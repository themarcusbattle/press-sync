<?php
namespace Press_Sync\validation;

/*
 * @package Press_Sync\validation
 */
class User {
	/**
	 * Number of sample records to get.
	 *
	 * @since NEXT
	 * @var int
	 */
	protected $sample_count = 0;

	/**
	 * Get the number of users in the WordPress install.
	 */
	public function get_count() {
		$counts            = count_users();
		$prepared          = $counts['avail_roles'];
		$prepared['total'] = $counts['total_users'];

		return $prepared;
	}

	/**
	 * Returns a random sample of data for validation.
	 *
	 * @since NEXT
	 * @return array
	 */
	public function get_samples() {
		$count = absint( $this->sample_count ) ?: 5;

		/**
		 * Sample data for samples.
		 *
		array(
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
		// ...
		*/
	}
}
