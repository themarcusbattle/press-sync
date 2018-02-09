<?php
namespace Press_Sync\client\cli\command\validate;

use Press_Sync\validators\UserValidator;
use WP_CLI\ExitException;

/**
 * Class UserSubcommand
 *
 * @package Press_Sync\client\cli\command\validate
 * @since NEXT
 */
class UserSubcommand extends AbstractValidateSubcommand {
	/**
	 * @param array $args Associative args from the parent command.
	 * @since NEXT
	 */
	public function __construct( $args ) {
		$this->args      = $args;
		$this->validator = new UserValidator( array(
			'good' => '%G',
			'bad'  => '%R',
		) );
	}

	/**
	 * Get validation data for the Taxonomy entity.
	 *
	 * @throws ExitException Exception if url parameter is not passed in multisite.
	 * @since NEXT
	 */
	public function validate() {
		$this->check_multisite_params();

		$data = ( $this->validator )();

		foreach ( $data['counts']['destination'] as $role => $count ) {
			$data['counts']['destination'][ $role ] = \WP_CLI::colorize( $data['counts']['processed'][ $role ] . '%n' );
		}

		$this->output( $data['counts']['source'], 'Local User Counts' );
		$this->output( $data['counts']['destination'], 'Remote User Counts' );

		// @TODO output sample data.
		// echo '<pre>', print_r($data['samples']['destination'], true); die("G");
	}

	/**
	 * Output data in the CLI.
	 *
	 * @param array  $data    Data to output.
	 * @param string $message Optional message to render.
	 * @since NEXT
	 */
	private function output( $data, $message = '' ) {
		if ( $message ) {
			\WP_CLI::line( $message );
		}

		$format = 'table';
		$fields = array_keys( $data );
		$assoc_args = compact( 'format', 'fields' );
		$formatter = new \WP_CLI\Formatter( $assoc_args );
		$formatter->display_items( array( $data ), true );
	}
}
