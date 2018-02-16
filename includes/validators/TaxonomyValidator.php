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
		return array(
			'count'      => API::get_remote_data( 'validation/taxonomy/count' ),
			'post_terms' => API::get_remote_data( 'validation/taxonomy/post_terms' ),
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
			'count'      => $this->compare_count( $source['count'], $destination['count'] ),
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

	/**
	 * Build array of source and destination post count data.
	 *
	 * @param array $source Source data.
	 * @param array $destination Destination data.
	 *
	 * @return array
	 */
	private function compare_post_terms( $source, $destination ) {
		$data = array();

		foreach ( $source as $key => $term_data ) {
			$data[ $key ] = array(
				'term'              => $term_data['slug'],
				'taxonomy'          => $term_data['taxonomy'],
				'count'             => $term_data['count'],
				'destination_count' => 0,
			);
		}

		foreach ( $destination as $key => $term_data ) {
			if ( ! isset( $data[ $key ] ) ) {
				$data[ $key ] = array(
					'term'              => $term_data['slug'],
					'taxonomy'          => $term_data['taxonomy'],
					'count'             => 0,
					'destination_count' => $term_data['count'],
					'migrated'          => true,
				);

				continue;
			}

			$data[ $key ]['destination_count'] = $destination[ $key ]['count'];
		}

		foreach ( $data as $key => $term ) {
			$data[ $key ]['migrated'] = $data[ $key ]['count'] === $data[ $key ]['destination_count'];
		}

		$random_terms = $this->get_random_terms( $data );

		foreach ( $random_terms as $key => $term ) {
			$random_terms[ $key ]['destination_count'] = $this->apply_diff_to_values( $term['count'], $term['destination_count'] );
		}

		return $random_terms;
	}

	/**
	 * Reduce the full array set to a random set of terms for output.
	 *
	 * @param array $data Data to select random terms from.
	 *
	 * @return array
	 */
	private function get_random_terms( array $data ) {
		$array_size      = count( $data );
		$sample_size     = $array_size >= $this->args['sample_count'] ? $this->args['sample_count'] : $array_size;
		$random_keys     = array_rand( $data, $sample_size );
		$randomized_data = array_filter( $data, function ( $term ) use ( $random_keys ) {
			$key = $term['term'] . '-' . $term['taxonomy'];
			return in_array( $key, $random_keys, true );
		} );

		ksort( $randomized_data );

		return $randomized_data;
	}
}
