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
	 * Get all of the Taxonomy data for validation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_data() {
		return array(
			'count'      => $this->get_count(),
			'post_terms' => $this->get_post_count_by_taxonomy_term(),
		);
	}

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
			$taxonomy_terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			$terms[ $name ] = array(
				'number_of_terms' => count( $taxonomy_terms ),
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
		$term_query = new \WP_Term_Query( array(
			'hide_empty' => false,
		) );

		$terms = $term_query->get_terms();

		$data = array();

		foreach ( $terms as $term ) {
			$data[ $term->slug . '-' . $term->taxonomy ] = array(
				'taxonomy' => $term->taxonomy,
				'count'    => $term->count,
				'slug'     => $term->slug,
			);
		}

		wp_reset_postdata();

		return $data;
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
		);
	}

	/**
	 * @param int $count Number of terms to process.
	 *
	 * @return array
	 */
	public function get_sample_meta() {
		$data = array();

		foreach ( get_taxonomies() as $taxonomy ) {
			$terms                          = get_terms( array( 'taxonomy' => $taxonomy ) );
			$data[ $taxonomy ]['terms']     = wp_list_pluck( $terms, 'slug' );
			$data[ $taxonomy ]['term_meta'] = $this->get_taxonomy_term_meta( $terms );
		}

		return $data;
	}

	/**
	 * Get the term meta from an array of terms.
	 *
	 * @param array $terms Term objects.
	 *
	 * @return array
	 */
	private function get_taxonomy_term_meta( $terms ) {
		$term_meta = array();

		foreach ( $terms as $term ) {
			$term_meta[ $term->slug ] = array();

			$meta = get_term_meta( $term->term_id );

			if ( $meta ) {
				$term_meta[ $term->slug ] = $meta;
			}
		}

		return $term_meta;
	}
}
