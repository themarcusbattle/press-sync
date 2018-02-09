<?php
namespace Press_Sync\validation;

/**
 * Class Post
 *
 * @package Press_Sync\validation
 * @since NEXT
 */
class Post implements CountInterface {
	/**
	 * Get all of the Post data for validation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_data() {
		return array(
			'count' => $this->get_count(),
		);
	}

	/**
	 * Get the number of posts for all registered post types.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_count() {
		$posts = array();

		foreach ( get_post_types() as $type ) {
			$posts[ $type ] = wp_count_posts( $type );
		}

		return $posts;
	}
}
