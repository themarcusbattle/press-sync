<?php
namespace Press_Sync\validation;

/**
 * Defines methods for highlighting discrepancies in the client.
 *
 * @package
 */
interface ValidationOutputInterface {

	/**
	 * Should return an array of output formatting parameters.
	 *
	 * @since NEXT
	 * @TODO TBD on what those keys should be.
	 *
	 * @return array
	 */
	public function get_data_output_format();
}
