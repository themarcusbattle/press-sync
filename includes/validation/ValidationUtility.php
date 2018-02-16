<?php
/**
 * Validation Utility abstraction.
 *
 * @package PressSync
 */

namespace Press_Sync\validators;

/**
 * This class defines utility methods that are common to all Validators.
 */
trait ValidationUtility {
	/**
	 * Get an icon based on whether a result was (bool) true or not.
	 *
	 * @since NEXT
	 *
	 * @param  bool $result The result to test.
	 *
	 * @return string
	 */
	protected function get_result_icon( $result ) {
		return ( (bool) $result ) === true ? '✅' : '❌';
	}

	abstract public function get_output_format();
}
