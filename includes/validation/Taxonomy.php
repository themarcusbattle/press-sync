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
	 */
	public function get_unique_taxonomy_count() {
		return count( get_taxonomies() );
	}

	/**
	 * Get the number of terms for each taxonomy in this WordPress installation.
	 *
	 * @return array
	 */
	public function get_term_count_by_taxonomy() {
		$terms = [];

		foreach ( get_taxonomies() as $name => $taxonomy ) {
			$terms[] = array(
				'taxonomy_name'   => $name,
				'number_of_terms' => count(
					get_terms( array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
					)
				) ),
			);
		}

		return $terms;
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
}
