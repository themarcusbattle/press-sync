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
	 * @return void
	 * @since NEXT
	 */
	public function validate() {
		$this->check_multisite_params();

		$count          = $this->get_source_data();
		$json           = $this->get_destination_data();
		$taxonomy_count = $count['unique_taxonomies'];
		$term_count     = $count['term_count_by_taxonomy'];

		\WP_CLI::line( 'Local domain results:' );
		\WP_CLI::line( '' );
		\WP_CLI\Utils\format_items( 'table', array( array( 'unique_taxonomies' => $taxonomy_count ) ),
			array( 'unique_taxonomies' ) );
		\WP_CLI\Utils\format_items( 'table', $term_count, array( 'taxonomy_name', 'number_of_terms' ) );

		if ( $taxonomy_count === $json['unique_taxonomies'] && $term_count === $json['term_count_by_taxonomy'] ) {
			\WP_CLI::success( 'Taxonomies and term counts on remote domain are identical to the values printed above.' );

			return;
		}

		\WP_CLI::line( 'Remote domain results:' );
		\WP_CLI\Utils\format_items( 'table', array( array( 'unique_taxonomies' => $json['unique_taxonomies'] ) ),
			array( 'unique_taxonomies' ) );
		\WP_CLI\Utils\format_items( 'table', $json['term_count_by_taxonomy'],
			array( 'taxonomy_name', 'number_of_terms' ) );

		if ( $taxonomy_count !== $json['unique_taxonomies'] ) {
			\WP_CLI::warning( 'Discrepancy in number of unique taxonomies.' );
		}

		if ( $term_count !== $json['term_count_by_taxonomy'] ) {
			\WP_CLI::warning( 'Discrepancy in taxonomy term counts.' );
		}
	}

	public function prepare_output() {

	}

	public function output() {

	}

	/**
	 * @return array
	 */
	public function get_source_data() {
		return ( new Taxonomy() )->get_count();
	}

	/**
	 * @return array
	 */
	public function get_destination_data() {
		return API::get_remote_data( 'validation/taxonomy/count' );
	}
}
