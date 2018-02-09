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
	protected $processed_data   = array();
	protected $source_data      = array();
	protected $destination_data = array();
	private $runtime_args       = array();

	public function validate( $runtime_args = array() ) {
		$this->runtime_args = $runtime_args;
		$this->get_source_data();
		$this->get_destination_data();
		$this->compare_counts();
		// $this->compare_samples

		return array(
			'counts' => array(
				'source'      => $this->source_data['counts'],
				'destination' => $this->destination_data['counts'],
				'processed'   => $this->processed_data['counts'],
			),
			'samples' => array(
				'source'      => $this->source_data['samples'],
				'destination' => $this->destination_data['samples'],
			),
		);
	}

	/**
	 * Get data from the source site.
	 *
	 * @since NEXT
	 */
	public function get_source_data() {
		$source = new User( $this->runtime_args );
		$this->source_data['counts']  = $source->get_count();
		$this->source_data['samples'] = $source->get_samples();
	}

	/**
	 * Get data from the destination site.
	 *
	 * @since NEXT
	 */
	public function get_destination_data() {
		$this->get_destination_counts();
		$this->get_destination_samples();
	}

	private function get_destination_counts() {
		$this->destination_data['counts'] = API::get_remote_data( 'validation/user/count' );
	}

	private function get_destination_samples() {
		$args = array(
			'source_users' => array(),
		);

		foreach ( $this->source_data['samples'] as $user ) {
			$args['source_users'][] = array(
				'ID'         => $user->ID,
				'user_login' => $user->data->user_login,
				'user_email' => $user->data->user_email,
			);
		}

		$this->destination_data['samples'] = API::get_remote_data( 'validation/user/samples', $args );
	}

	/**
	 * Compare counts between source and destination.
	 *
	 * @since NEXT
	 */
	private function compare_counts() {
		$this->processed_data['count'] = array();

		foreach ( $this->source_data['counts'] as $role => $src_count ) {
			$dest_count = 0;

			if ( isset( $this->destination_data['counts'][ $role ] ) ) {
				$dest_count = $this->destination_data['counts'][ $role ];
			}

			$this->processed_data['counts'][ $role ] = $this->diff_counts( $src_count, $dest_count );
		}
	}

	private function diff_counts( $count, $compare ) {
		$pre  = $this->runtime_args['pre_match'];
		$post = $this->runtime_args['post_match'];

		if ( $count !== $compare ) {
			$pre = $this->runtime_args['pre_mismatch'];
			$post = $this->runtime_args['post_mismatch'];
		}

		return "{$pre}{$compare}{$post}";
	}
}
