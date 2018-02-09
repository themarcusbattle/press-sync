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

	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'sample_count' => 5,
		) );

		foreach ( $args as $key => $value ) {
			if ( isset( $this->{$key} ) ) {
				$this->{$key} = $value;
			}
		}
	}

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
	public function get_samples( $args = array() ) {
		$count   = absint( $this->sample_count ) ?: 5;
		$users   = get_users();
		$samples = array();

		for ( $count; $count--; ) {
			$offset = rand(0, count( $users ) ) - 1;
			$samples[] = current( array_slice( $users, $offset, 1 ) );
		}

		return $samples;
	}
}
