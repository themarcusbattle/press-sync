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
	public function get_data( $count ) {
		return array(
			'count'      => $this->get_count(),
			'sample'     => $this->get_sample_posts_data( $count ),
			'sample_tax' => $this->get_sample_terms_data(),
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
	 * @param int $count
	 *
	 * @return array
	 */
	private function get_random_posts( $count = 5 ) {
		$query = new \WP_Query( array(
			'post_type'      => 'any',
			'posts_per_page' => $count ?? 5,
			'orderby'        => 'rand', // @codingStandardsIgnoreLine
		) );

		$posts = $query->get_posts();

		// Sort posts by post ID.
		usort( $posts, [ $this, 'sort_posts_by_id' ] );

		wp_reset_postdata();

		return $posts;
	}

	private function sort_posts_by_id( $a, $b ) {
		return strcmp( $a->ID, $b->ID );
	}

	/**
	 * Get a sample number of posts.
	 *
	 * @param int $count
	 */
	public function get_sample_posts_data( $count = 5 ) {
		return $this->format_sample_post_data( $this->get_random_posts( $count ) );
	}

	/**
	 * Get a collection of posts to use for comparison against a sample.
	 *
	 * @since NEXT
	 *
	 * @param array $ids Array of post IDs.
	 *
	 * @return array
	 */
	public function get_comparison_posts( array $ids ) {
		$query = new \WP_Query( array(
			'posts_per_page' => count( $ids ),
			'meta_query'     => array(
				array(
					'key'     => 'press_sync_post_id',
					'value'   => $ids,
					'compare' => 'IN',
				),
			),
		) );

		$posts = $query->get_posts();

		usort( $posts, [ $this, 'sort_posts_by_id' ] );

		wp_reset_postdata();

		return $this->format_sample_post_data( $posts );
	}

	/**
	 * Get the taxonomy term assignments for a random sample of posts.
	 *
	 * @param int $count Number of posts to retrieve.
	 *
	 * @return array
	 */
	public function get_sample_terms_data( $count = 5 ) {
		return $this->format_sample_post_terms( $this->get_random_posts( $count ) );
	}

	/**
	 * Compare the taxonomy term assignments for a set of posts.
	 *
	 * @param array $ids Array of post IDs.
	 *
	 * @return array
	 */
	public function get_comparison_terms( $ids ) {
		return [];
	}

	/**
	 * @param $posts
	 *
	 * @return array
	 */
	public function format_sample_post_data( $posts ) {
		$data = array();

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

	/**
	 * @param array $posts
	 */
	public function format_sample_post_terms( array $posts ) {
		$post_terms = array();

		foreach ( $posts as $key => $post ) {
			$terms = array(
				'ID'    => $post->ID,
				'terms' => $this->get_post_terms( $post->ID ),
			);

			$ps_id = get_post_meta( $post->ID, 'press_sync_post_id' );

			if ( $ps_id ) {
				$terms['press_sync_post_id'] = $ps_id[0];
			}

			$post_terms[] = $terms;
		}

		return $post_terms;
	}

	/**
	 * @param $post_id
	 *
	 * @return array
	 */
	private function get_post_terms( $post_id ) {
		$post_terms = array();

		foreach ( get_taxonomies() as $taxonomy ) {
			$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );

			if ( $terms ) {
				$post_terms[ $taxonomy ] = $terms;
			}
		}

		return $post_terms;
	}
}
