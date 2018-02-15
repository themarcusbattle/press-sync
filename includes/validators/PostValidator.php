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
	 * Source data.
	 *
	 * @var array
	 * @since NEXT
	 */
	protected $source_data = array();

	/**
	 * Validate the Post data.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function validate() {
		$data = array(
			'source'      => $this->get_source_data(),
			'destination' => $this->get_destination_data(),
		);

		$data['comparison'] = $this->get_comparison_data( $data['source'], $data['destination'] );

		return $data;
	}

	/**
	 * Get taxonomy data from the local WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_source_data() {
		$this->source_data = ( new Post() )->get_data( $this->args['sample_count'] );

		return $this->source_data;
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
			'count'  => $this->compare_count( $source['count'], $destination['count'] ),
			'sample' => $this->compare_sample( $source['sample'], $destination['sample'] ),
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
		/*
		 * We ran into an issue where posts were not being properly compared because their indexes were out of sync
		 * because destination results might not match source results. These blocks re-key the source and destination
		 * data by post ID, then populates the destination array with empty data for each missing post. This allows
		 * us to compare values correctly by looping through the source_index.
		 */
		$source_index      = array();
		$destination_index = array();

		foreach ( $source as $post ) {
			$source_index[ $post['ID'] ] = $post;
		}

		foreach ( $destination as $post ) {
			$destination_index[ $post['ID'] ] = $post;
		}

		foreach ( $source_index as $key => $source_post ) {
			$source_index[ $key ]['migrated'] = true;

			if ( ! isset( $destination_index[ $key ] ) ) {
				$destination_index[ $key ] = array(
					'ID'       => $key,
					'type'     => null,
					'author'   => null,
					'content'  => null,
					'meta'     => null,
					'migrated' => false,
				);

				continue;
			}

			$destination_index[ $key ]['migrated'] = true;
		}

		$comparison_output = array();

		foreach ( $source_index as $index => $post_data ) {
			$post = array();

			foreach ( $post_data as $key => $data ) {
				if ( 'ID' === $key ) {
					$post[ 'post_id' ] = $data;

					continue;
				}

				$result = $this->compare_sample_values( $key, $source_index[ $index ], $destination_index[ $index ] );

				$post[ $key ] = $result ? true : false;
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
}
