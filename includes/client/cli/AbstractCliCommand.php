<?php
namespace Press_Sync\client\cli;

/**
 * Class AbstractCliCommand
 *
 * @package Press_Sync\client\cli
 * @since NEXT
 */
abstract class AbstractCliCommand extends \WP_CLI_Command {
	/**
	 * @since NEXT
	 * @return void
	 */
	abstract public function register_command();
}
