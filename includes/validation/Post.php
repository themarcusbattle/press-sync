<?php
namespace Press_Sync\validation;

/**
 * Class Post
 *
 * @package Press_Sync\validation
 * @since   NEXT
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

	/**
	 * Get a sample number of posts.
	 *
	 * @param int $count
	 */
	public function get_sample( $count = 5 ) {
		$query = new \WP_Query( array(
			'post_type'      => 'any',
			'posts_per_page' => $count ?? 5,
			'orderby'        => 'rand', // @codingStandardsIgnoreLine
		) );

		$posts = $query->get_posts();
		$data  = array();

		foreach ( $posts as $post ) {
			$author = get_userdata( $post->post_author );
			$data[] = array(
				'ID'      => $post->ID,
				'type'    => $post->post_type,
				'author'  => $author->user_login,
				'content' => $post->post_content,
				'meta'    => get_post_meta( $post->ID ),
			);
		}

		return $data;
	}
}
