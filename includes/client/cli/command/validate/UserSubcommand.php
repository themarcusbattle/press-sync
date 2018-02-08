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
		$this->validator = new UserValidator();
	}

	/**
	 * Get validation data for the Taxonomy entity.
	 *
	 * @throws ExitException Exception if url parameter is not passed in multisite.
	 * @since NEXT
	 */
	public function validate() {
		$this->check_multisite_params();

		$data = $this->validator->validate();

		$this->output( $data['source'], 'Local User Counts' );
		$this->output( $data['destination'], 'Remote User Counts' );
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

		\WP_CLI\Utils\format_items( 'table', array( $data ), array_keys( $data ) );
	}
}
