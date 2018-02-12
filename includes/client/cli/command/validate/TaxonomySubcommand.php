<?php
namespace Press_Sync\client\cli\command\validate;

use Press_Sync\validators\TaxonomyValidator;
use WP_CLI\ExitException;

/**
 * Class TaxonomySubcommand
 *
 * @package Press_Sync\client\cli\command\validate
 * @since NEXT
 */
class TaxonomySubcommand extends AbstractValidateSubcommand {
	/**
	 * Taxonomy constructor.
	 *
	 * @param array $args Associative args from the parent command.
	 * @since NEXT
	 */
	public function __construct( $args ) {
		$this->args      = $args;
		$this->validator = new TaxonomyValidator( array(
			'format' => $this->get_data_output_format(),
		) );
	}
	/**
	 * Get validation data for the Taxonomy entity.
	 *
	 * @throws ExitException Exception if url parameter is not passed in multisite.
	 * @since NEXT
	 */
	public function validate() {
		$this->check_multisite_params();

		$data = $this->validator->validate();

		$this->output( $data['source'], 'Local taxonomy data:' );
		$this->output( $data['destination'], 'Remote taxonomy data:' );

		$this->output_comparison_statements( $data['source'], $data['destination'] );
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

		\WP_CLI\Utils\format_items( 'table', $data['term_count_by_taxonomy'], array( 'taxonomy_name', 'number_of_terms' ) );
		\WP_CLI\Utils\format_items( 'table', $data['post_terms'], array( 'taxonomy', 'term', 'post_count' ) );
		\WP_CLI::line( "Unique taxonomies: {$data['unique_taxonomies']}" );
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
		if ( $this->validator->compare( $local_data['unique_taxonomies'], $remote_data['unique_taxonomies'] )
			&& $this->validator->compare( $local_data['term_count_by_taxonomy'], $remote_data['term_count_by_taxonomy'] ) ) {
			\WP_CLI::success( 'Taxonomies and term counts on remote domain are identical to the values printed above.' );

			return;
		}

		if ( ! $this->validator->compare( $local_data['unique_taxonomies'], $remote_data['unique_taxonomies'] ) ) {
			\WP_CLI::warning( 'Discrepancy in number of unique taxonomies.' );
		}

		if ( ! $this->validator->compare( $local_data['term_count_by_taxonomy'], $remote_data['term_count_by_taxonomy'] ) ) {
			\WP_CLI::warning( 'Discrepancy in taxonomy term counts.' );
		}
	}
}
