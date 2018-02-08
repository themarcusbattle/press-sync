<?php
namespace Press_Sync\client\cli\command\validate;

use Press_Sync\validation\ValidatorInterface;
use WP_CLI\ExitException;

/**
 * Class AbstractValidateSubcommand
 *
 * @package Press_Sync\client\cli\command\validate
 */
abstract class AbstractValidateSubcommand {
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
	 *
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
}
