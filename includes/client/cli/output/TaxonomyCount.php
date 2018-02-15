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
		$this->output( $this->data['comparison'], 'Taxonomy terms count:' );

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

		// \WP_CLI\Utils\format_items( 'table', $data['term_count_by_taxonomy'], array( 'taxonomy_name', 'number_of_terms' ) );
		// \WP_CLI\Utils\format_items( 'table', $data['post_terms'], array( 'taxonomy', 'term', 'post_count' ) );
		// \WP_CLI::line( "Unique taxonomies: {$data['unique_taxonomies']}" );
	}
}
