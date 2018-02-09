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

	public function __invoke( $runtime_args = array() ) {
		$this->runtime_args = $runtime_args;
		parent::validate();
		$this->get_source_data();
		$this->get_destination_data();
		$this->compare_data();

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
		$this->destination_data['counts'] = API::get_remote_data( 'validation/user/count' );

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

	private function compare_data() {
		$this->compare_counts();
	}

	private function compare_counts() {
		$this->processed_data['count'] = array();

		foreach ( $this->source_data['counts'] as $role => $src_count ) {
			$dest_count = 0;

			if ( isset( $this->destination_data['counts'][ $role ] ) ) {
				$dest_count = $this->destination_data['counts'][ $role ];
			}

			$this->processed_data['counts'][ $role ] = ( $src_count === $dest_count ? $this->good : $this->bad ) . "{$dest_count} ";
		}
	}
}
