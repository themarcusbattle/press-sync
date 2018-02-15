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
		$this->output( $this->data['source'], 'Local taxonomy data:' );
		$this->output( $this->data['destination'], 'Remote taxonomy data:' );

		$this->output_comparison_statements( $this->data['source'], $this->data['destination'] );
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

		if ( $message ) {
			\WP_CLI::line( $message );
		}

		$format     = 'table';
		$fields     = array_keys( $data[0] );
		$assoc_args = compact( 'format', 'fields' );
		$formatter  = new Formatter( $assoc_args );
		$formatter->display_items( $data, true );

		// \WP_CLI\Utils\format_items( 'table', $data['term_count_by_taxonomy'], array( 'taxonomy_name', 'number_of_terms' ) );
		// \WP_CLI\Utils\format_items( 'table', $data['post_terms'], array( 'taxonomy', 'term', 'post_count' ) );
		// \WP_CLI::line( "Unique taxonomies: {$data['unique_taxonomies']}" );
	}


	/**
	 * Output statements comparing local and remote data sets.
	 *
	 * @param array $local_data  Local install taxonomy data.
	 * @param array $remote_data Remote install taxonomy data.
	 *
	 * @since NEXT
	 */
	private function output_comparison_statements( $local_data, $remote_data ) {
		if ( $local_data['unique_taxonomies'] === $remote_data['unique_taxonomies']
		     && $local_data['term_count_by_taxonomy'] === $remote_data['term_count_by_taxonomy'] ) {
			\WP_CLI::success( 'Taxonomies and term counts on remote domain are identical to the values printed above.' );

			return;
		}

		if ( $local_data['unique_taxonomies'] !== $remote_data['unique_taxonomies'] ) {
			\WP_CLI::warning( 'Discrepancy in number of unique taxonomies.' );
		}

		if ( $local_data['term_count_by_taxonomy'] !== $remote_data['term_count_by_taxonomy'] ) {
			\WP_CLI::warning( 'Discrepancy in taxonomy term counts.' );
		}
	}
}
