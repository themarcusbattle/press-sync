<?php
namespace Press_Sync\client\output;

use WP_CLI\Formatter;

/**
 * Class TaxonomySample
 *
 * @package Press_Sync\client\output
 */
class TaxonomySample extends AbstractOutput {
	/**
	 * Render data to the client.
	 *
	 * @since NEXT
	 */
	public function render() {
		\WP_CLI::line();
		$this->output( $this->prepare( $this->data['comparison'] ), 'Post counts by taxonomy term:' );
		\WP_CLI::line();
	}

	/**
	 * @param array  $data
	 * @param string $message
	 */
	public function output( array $data, $message = '' ) {
		if ( $message ) {
			\WP_CLI::line( $message );
		}

		$format     = 'table';
		$fields     = array_keys( current( $data ) );
		$assoc_args = compact( 'format', 'fields' );
		$formatter  = new Formatter( $assoc_args );
		$formatter->display_items( $data, true );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function prepare( array $data ) {
		foreach ( $data as $index => $post_sample ) {
			foreach ( $post_sample as $key => $value ) {
				if ( 'migrated' === $key ) {
					$data[ $index ][ $key ] = $this->get_result_icon( $value );
				}

				if ( 'destination_count' === $key ) {
					$data[ $index ][ $key ] = \WP_CLI::colorize( $value );
				}
			}
		}

		return $data;
	}
}
