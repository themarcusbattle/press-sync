<?php
namespace Press_Sync\client\output;

use WP_CLI\Formatter;

/**
 * Class TaxonomyCount
 *
 * @package Press_Sync\client\output
 */
class TaxonomyCount extends AbstractOutput {
	/**
	 * @return mixed|void
	 */
	public function render() {
		\WP_CLI::line();
		$this->output( $this->prepare( $this->data['comparison'] ), 'Taxonomy terms count:' );
		\WP_CLI::log( 'Number of unique taxonomies: ' . count( $this->data['comparison'] ) );
		\WP_CLI::line();

		// $this->output_comparison_statements( $this->data['source'], $this->data['destination'] );
	}

	/**
	 * Output data in the CLI.
	 *
	 * @param array  $data    Data to output.
	 * @param string $message Optional message to render.
	 * @since NEXT
	 */
	private function output( $data, $message = '' ) {
		if ( $message ) {
			\WP_CLI::line( $message );
		}

		$format     = 'table';
		$fields     = array_keys( $data['category'] );
		$assoc_args = compact( 'format', 'fields' );
		$formatter  = new Formatter( $assoc_args );
		$formatter->display_items( $data, true );
	}

	/**
	 * Prepare the data for rendering.
	 *
	 * @param array $data Data to prepare for rendering.
	 *Ø
	 * @return array
	 */
	public function prepare( array $data ) {
		foreach ( $data as $taxonomy_name => $taxonomy ) {
			foreach ( $taxonomy as $index => $value ) {
				if ( 'destination_count' === $index || 'migrated' === $index ) {
					$data[ $taxonomy_name ][ $index ] = \WP_CLI::colorize( $value );
				}
			}
		}

		return $data;
	}
}
