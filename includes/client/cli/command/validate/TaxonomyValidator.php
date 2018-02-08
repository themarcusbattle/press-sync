<?php
namespace Press_Sync\client\cli\command\validate;

use Press_Sync\API;
use Press_Sync\validation\Taxonomy;
use Press_Sync\validation\ValidatorInterface;
use WP_CLI\ExitException;

/**
 * Class TaxonomyValidator
 *
 * @package Press_Sync\client\cli\command\validate
 * @since NEXT
 */
class TaxonomyValidator extends AbstractValidator implements ValidatorInterface {
	/**
	 * TaxonomyValidator constructor.
	 *
	 * @param array $args Associative args from the parent command.
	 * @since NEXT
	 */
	public function __construct( $args ) {
		$this->args = $args;
	}
	/**
	 * Get validation data for the Taxonomy entity.
	 *
	 * @throws ExitException Exception if url parameter is not passed in multisite.
	 * @since NEXT
	 */
	public function validate() {
		$this->check_multisite_params();

		$taxonomy_data        = $this->get_source_data();
		$remote_taxonomy_data = $this->get_destination_data();

		$this->output( $taxonomy_data, 'Local taxonomy data:' );
		$this->output( $remote_taxonomy_data, 'Remote taxonomy data:' );

		$this->output_comparison_statements( $taxonomy_data, $remote_taxonomy_data );
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

	/**
	 * Get taxonomy data from the local WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_source_data() {
		return ( new Taxonomy() )->get_count();
	}

	/**
	 * Get taxonomy data from the remote WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_destination_data() {
		return API::get_remote_data( 'validation/taxonomy/count' );
	}
}
