<?php
namespace Press_Sync\validation;

/**
 * Class Post
 *
 * @package Press_Sync\validation
 */
class Post implements CountInterface {
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
