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
class PostValidator extends AbstractValidator implements ValidatorInterface {
	/**
	 * Validate the Post data.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function validate() {
		$this->source_data      = $this->get_source_data();
		$this->destination_data = $this->get_destination_data();

		$this->normalize_sample_for_comparison( 'sample', $this->source_data['sample'], $this->destination_data['sample'] );
		$this->normalize_sample_for_comparison( 'sample_tax', $this->source_data['sample_tax'], $this->destination_data['sample_tax'] );

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
		return ( new Post() )->get_data( $this->args['sample_count'] );
	}

	/**
	 * Get taxonomy data from the remote WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_destination_data() {
		return array(
			'count'      => API::get_remote_data( 'validation/post/count' ),
			'sample'     => API::get_remote_data(
				'validation/post/sample',
				array(
					'type' => 'posts',
					'ids'  => $this->get_source_sample_ids(),
				)
			),
			'sample_tax' => API::get_remote_data(
				'validation/post/sample',
				array(
					'type' => 'terms',
					'ids'  => $this->get_source_sample_ids(),
				)
			),
		);
	}

	/**
	 * Get the sample post IDs from the source data.
	 *
	 * These same IDs should be compared on the destination site.
	 *
	 * @return array
	 */
	private function get_source_sample_ids() {
		$ids = array();

		foreach ( $this->source_data['sample'] as $post ) {
			$ids[] = $post['ID'];
		}

		return $ids;
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
			'sample'     => $this->compare_sample( $source['sample'], $destination['sample'] ),
			'sample_tax' => $this->compare_sample_tax( $source['sample_tax'], $destination['sample_tax'] ),
		);
	}

	/**
	 * Compare counts between source and destination.
	 *
	 * @since NEXT
	 * @param  array $source      Source counts.
	 * @param  array $destination Destination counts.
	 * @return array
	 */
	private function compare_count( $source, $destination ) {
		$comparison = array();

		foreach ( $source as $post_type => $source_statuses ) {
			foreach ( $source_statuses as $source_status => $source_status_count ) {
				$destination_status = 0;

				if ( isset( $destination[ $post_type ][ $source_status ] ) ) {
					$destination_status = $destination[ $post_type ][ $source_status ];
				}

				$comparison[ $post_type ][ $source_status ] = $this->apply_diff_to_values( $source_status_count, $destination_status );
			}
		}

		return $comparison;
	}

	/**
	 * We ran into an issue where posts were not being properly compared because their indexes were out of sync
	 * because destination results might not match source results. These blocks re-key the source and destination
	 * data by post ID, then populates the destination array with empty data for each missing post. This allows
	 * us to compare values correctly by looping through the source_index.
	 *
	 * @param array $source      Local post data.
	 * @param array $destination Remote post data.
	 */
	private function normalize_sample_for_comparison( $data_index, $source, $destination ) {
		$source_index      = $this->index_data_by_post_id( $source );
		$destination_index = $this->index_data_by_post_id( $destination );

		foreach ( $source_index as $key => $source_post ) {
			$source_index[ $key ]['migrated'] = 'yes';

			if ( isset( $destination_index[ $key ] ) ) {
				$destination_index[ $key ]['migrated'] = 'yes';

				continue;
			}

			foreach ( $source_post as $index => $value ) {
				if ( 'ID' === $index ) {
					$destination_index[ $key ][ $index ] = $key;

					continue;
				}

				$destination_index[ $key ][ $index ] = null;
			}

			$destination_index[ $key ]['migrated'] = 'no';
		}

		$this->source_data[ $data_index ]      = $this->index_data_by_post_id( array_values( $source_index ) );
		$this->destination_data[ $data_index ] = $this->index_data_by_post_id( array_values( $destination_index ) );
	}

	/**
	 * Take an array of post data and update it to be an associative array indexed by post ID.
	 *
	 * @param array $post_data Array of post data.
	 *
	 * @return array
	 */
	private function index_data_by_post_id( $post_data ) {
		$data = array();

		foreach ( $post_data as $post ) {
			$data[ $post['ID'] ] = $post;
		}

		return $data;
	}

	/**
	 * Compare the sample data between the source and destination sites.
	 *
	 * Builds a table of true/false values for each data point that is compared.
	 *
	 * @TODO This has gotten messy. See comments and refactor.
	 *
	 * @param array $source Data from the source site.
	 * @param $destination
	 *
	 * @return array
	 */
	private function compare_sample( $source, $destination ) {
		$comparison_output = array();

		foreach ( $source as $index => $post_data ) {
			$post = array();

			foreach ( $post_data as $key => $data ) {
				if ( 'ID' === $key ) {
					$post['post_id'] = $data;

					continue;
				}

				$post [ $key ] = $this->compare_sample_values( $key, $source[ $index ], $destination[ $index ] ) ? 'yes' : 'no';
				$post[ $key ]  = $this->apply_diff_to_values( $source[ $index ][ $key ], $destination[ $index ][ $key ], $post[ $key ] );
			}

			$comparison_output[] = $post;
		}

		return $comparison_output;
	}

	/**
	 * Compare values of metadata between source and destination sites.
	 *
	 * Source is treated as the truth. Destination must have all of the same meta keys with the same meta values for
	 * this function to return true.
	 *
	 * @param string $key              Key to compare.
	 * @param array  $source_data      Data from the local site.
	 * @param array  $destination_data Data from the remote site.
	 *
	 * @return bool
	 */
	public function compare_sample_values( $key, $source_data, $destination_data ) {
		if ( is_null( $destination_data ) ) {
			return false;
		}

		// Non-meta comparisons are a simple equivalency check.
		if ( 'meta' !== $key ) {
			return $source_data[ $key ] === $destination_data[ $key ];
		}

		// Compare meta values.
		$valid_meta = true;

		foreach ( $source_data[ $key ] as $index => $value ) {
			if ( ! isset( $destination_data[ $key ][ $index ] ) || $source_data[ $key ][ $index ] !== $destination_data[ $key ][ $index ] ) {
				$valid_meta = false;

				break;
			}
		}

		return $valid_meta;
	}

	/**
	 * Compare taxonomy data for individual posts.
	 *
	 * @param array $source      Source data.
	 * @param array $destination Destination data.
	 *
	 * @return array
	 */
	public function compare_sample_tax( $source, $destination ) {
		$data = array();

		foreach ( $source as $post_id => $post_data ) {
			foreach ( $post_data['terms'] as $taxonomy_key => $taxonomy ) {
				$data[] = array(
					'post_id'        => $post_id,
					'taxonomy'       => $taxonomy_key,
					'terms_migrated' => $this->apply_diff_to_values(
						$source[ $post_id ]['terms'],
						$destination[ $post_id ]['terms'],
						$this->check_all_terms(
							$source[ $post_id ]['terms'],
							$destination[ $post_id ]['terms']
						)
					),
				);
			}
		}

		return $data;
	}

	/**
	 * Compare source and destination terms for a particular post to confirm whether all terms have been migrated.
	 *
	 * @param array $source Source data.
	 * @param array $destination Destination data.
	 *
	 * @return bool
	 */
	private function check_all_terms( $source, $destination ) {
		if ( is_null( $destination ) ) {
			return 'no';
		}

		if ( count( $source ) !== count( $destination ) ) {
			return 'no';
		}

		foreach ( $source as $taxonomy_key => $taxonomy ) {
			foreach ( $taxonomy as $term_key => $term ) {
				if ( ! isset( $destination[ $taxonomy_key ][ $term_key ] )
					|| $destination[ $taxonomy_key ][ $term_key ] !== $source[ $taxonomy_key ][ $term_key ] ) {
					return 'no';
				}
			}
		}

		return 'yes';
	}
}
