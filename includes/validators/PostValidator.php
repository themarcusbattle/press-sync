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
			'comparison'  => array(),
		);

		$data['comparison'] = $this->compare_data( $data['source']['count'], $data['destination']['count'] );

		return $data;
	}

	/**
	 * Get taxonomy data from the local WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_source_data() {
		$this->source_data = ( new Post() )->get_data();

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
			'count' => API::get_remote_data( 'validation/post/count' ),
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
	public function compare_data( array $source, array $destination ) {
		return array(
			'count' => $this->compare_counts( $source, $destination ),
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
	private function compare_counts( $source, $destination ) {
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
}
