<?php
namespace Press_Sync\validation;

/**
 * Class Taxonomy
 *
 * @package Press_Sync\validation
 * @since   NEXT
 */
class Taxonomy implements CountInterface {
	/**
	 * Get the number of unique taxonomies in this WordPress installation.
	 *
	 * @return int
	 * @since NEXT
	 */
	public function get_unique_taxonomy_count() {
		return count( get_taxonomies() );
	}

	/**
	 * Get the number of terms for each taxonomy in this WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_term_count_by_taxonomy() {
		$terms = [];

		foreach ( get_taxonomies() as $name => $taxonomy ) {
			$terms[] = array(
				'taxonomy_name'   => $name,
				'number_of_terms' => count(
					get_terms(
						array(
							'taxonomy'   => $taxonomy,
							'hide_empty' => false,
						)
					) ),
			);
		}

		return $terms;
	}

	/**
	 * Get an indexed array of post counts by taxonomy and term.
	 *
	 * @TODO Let's split up some of these responsibilities.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_post_count_by_taxonomy_term() {
		$taxonomies = array();

		foreach ( get_taxonomies() as $name => $taxonomy ) {
			$taxonomies[ $name ] = array();

			foreach (
				get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
					)
				) as $term
			) {
				$query = new \WP_Query(
					array(
						'fields'         => 'ids',
						'post_type'      => 'any',
						'posts_per_page' => - 1,
						'tax_query'      => array(
							array(
								'taxonomy' => $taxonomy,
								'field'    => 'slug',
								'terms'    => $term->slug,
							),
						),
					)
				);

				$taxonomies[ $name ][ $term->slug ] = count( $query->get_posts() );

				wp_reset_postdata();
			}
		}

		$indexed_array = array();

		foreach ( $taxonomies as $tax_name => $taxonomy ) {
			foreach ( $taxonomy as $term_name => $post_count ) {
				$indexed_array[] = array(
					'taxonomy'   => $tax_name,
					'term'       => $term_name,
					'post_count' => $post_count,
				);
			}
		}

		return $indexed_array;
	}


	/**
	 * Get taxonomy-related counts.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_count() {
		return array(
			'unique_taxonomies'      => $this->get_unique_taxonomy_count(),
			'term_count_by_taxonomy' => $this->get_term_count_by_taxonomy(),
			'post_terms'             => $this->get_post_count_by_taxonomy_term(),
		);
	}
}
