<?php
namespace Press_Sync\validators;

use Press_Sync\API;
use Press_Sync\validation\ValidatorInterface;
use Press_Sync\validation\Taxonomy;

/**
 * Class TaxonomyValidator
 *
 * @package Press_Sync\validators
 * @since NEXT
 */
class TaxonomyValidator extends AbstractValidator implements ValidatorInterface {
	/**
	 * Validate taxonomy data.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function validate() {
		return array(
			'source'      => $this->get_source_data(),
			'destination' => $this->get_destination_data(),
		);
	}
	/**
	 * Get taxonomy data from the local WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_source_data() {
		return ( new Taxonomy() )->get_count();
	}

	/**
	 * Get taxonomy data from the remote WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_destination_data() {
		return API::get_remote_data( 'validation/taxonomy/count' );
	}

	/**
	 * Compare source and destination data.
	 *
	 * @param array $source      Source data.
	 * @param array $destination Destination data.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function compare_data( array $source, array $destination ) {
		return array();
	}
}
