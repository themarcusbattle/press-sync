<?php
namespace Press_Sync\validators;

use Press_Sync\API;
use Press_Sync\validation\ValidatorInterface;
use Press_Sync\validation\Post;

/**
 * Class PostValidator
 *
 * @package Press_Sync\validators
 * @since NEXT
 */
class PostValidator implements ValidatorInterface {
	/**
	 * Validate the Post data.
	 *
	 * @return array
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
		return ( new Post() )->get_count();
	}

	/**
	 * Get taxonomy data from the remote WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_destination_data() {
		return API::get_remote_data( 'validation/post/count' );
	}
}
