<?php
namespace Press_Sync\client\cli\command\validate;

use Press_Sync\validation\ValidatorInterface;
use WP_CLI\ExitException;
use Press_Sync\validation\ValidationOutputInterface;

/**
 * Class AbstractValidateSubcommand
 *
 * @package Press_Sync\client\cli\command\validate
 */
abstract class AbstractValidateSubcommand implements ValidationOutputInterface {
	/**
	 * Associative args from the parent CLI command.
	 *
	 * @var array
	 */
	public $args;

	/**
	 * @var ValidatorInterface
	 */
	public $validator;

	/**
	 * Run the subcommand's validation.
	 *
	 * @since NEXT
	 */
	abstract public function validate();

	/**
	 * Check for valid parameters for targeting sites in multisite.
	 *
	 * @throws ExitException Configuration parameters are missing from a CLI request.
	 */
	public function check_multisite_params() {
		if ( ! is_multisite() ) {
			return;
		}

		try {
			if ( ! \WP_CLI::get_config( 'url' ) ) {
				throw new ExitException( 'You must include the --url parameter when calling this command on WordPress multisite.' );
			}
		} catch ( ExitException $e ) {
			\WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Gets the output data formatting for CLI commands.
	 *
	 * @since NEXT
	 * @return array
	 */
	public function get_data_output_format() {
		return array(
			'match_open_wrap'     => '%G',
			'match_close_wrap'    => '%n',
			'mismatch_open_wrap'  => '%R',
			'mismatch_close_wrap' => '%n',
		);
	}
}
