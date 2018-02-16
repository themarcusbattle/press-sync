<?php
namespace Press_Sync\validators;

use Press_Sync\API;
use Press_Sync\validation\ValidatorInterface;
use Press_Sync\validation\Taxonomy;

/**
 * Class TaxonomyValidator
 *
 * @package Press_Sync\validators
 * @since   NEXT
 */
class TaxonomyValidator extends AbstractValidator implements ValidatorInterface {
	/**
	 * Validate taxonomy data.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function validate() {
		$this->source_data      = $this->get_source_data();
		$this->destination_data = $this->get_destination_data();

		return array(
			'source'      => $this->source_data,
			'destination' => $this->destination_data,
			'comparison'  => $this->get_comparison_data( $this->source_data, $this->destination_data ),
		);
	}

	/**
	 * Get taxonomy data from the local WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_source_data() {
		return ( new Taxonomy() )->get_data();
	}

	/**
	 * Get taxonomy data from the remote WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_destination_data() {
		$count = API::get_remote_data( 'validation/taxonomy/count' );

		return array(
			'count' => array(
				'unique_taxonomies'      => $count['unique_taxonomies'],
				'term_count_by_taxonomy' => $count['term_count_by_taxonomy'],
			),
			'post_terms' => $count['post_terms'],
		);
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
	public function get_comparison_data( array $source, array $destination ) {
		return array(
			'count' => $this->compare_count( $source['count'], $destination['count'] ),
			'post_terms' => $this->compare_post_terms( $source['post_terms'], $destination['post_terms'] ),
		);
	}

	/**
	 * @param $source
	 * @param $destination
	 *
	 * @return array
	 */
	private function compare_count( $source, $destination ) {
		$data = array();

		foreach ( $source['term_count_by_taxonomy'] as $taxonomy_name => $count ) {
			$data[ $taxonomy_name ] = array(
				'taxonomy_name'     => $taxonomy_name,
				'term_count'        => $count['number_of_terms'],
				'destination_count' => 0,
				'migrated'          => false,
			);
		}

		foreach ( $destination['term_count_by_taxonomy'] as $taxonomy_name => $count ) {
			if ( ! isset( $data[ $taxonomy_name ] ) ) {
				$data[ $taxonomy_name ] = array(
					'taxonomy_name'     => $taxonomy_name,
					'term_count'        => 0,
					'destination_count' => $count['number_of_terms'],
					'migrated'          => false,
				);

				continue;
			}

			$data[ $taxonomy_name ]['destination_count'] = $this->apply_diff_to_values( $count['number_of_terms'], $data[ $taxonomy_name ]['term_count'] );
			$data[ $taxonomy_name ]['migrated']          = true;
		}

		return $data;
	}

	private function compare_post_terms( $source, $destination ) {
		$data = array();

		return $data;
	}
}
