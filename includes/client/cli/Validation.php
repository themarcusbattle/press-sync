<?php
namespace Press_Sync\client\cli;

use Press_Sync\validation\Taxonomy;
use Press_Sync\API;
use WP_CLI\ExitException;

/**
 * Class ValidationCommand
 *
 * @package Press_Sync\client\cli
 * @since NEXT
 */
class ValidationCommand extends AbstractCliCommand {
	/**
	 * Register our custom commands with WP-CLI.
	 *
	 * @since NEXT
	 */
	public function register_command() {
		\WP_CLI::add_command( 'press-sync validate', array( $this, 'validate' ) );
	}

	/**
	 * Validate data consistency between source site and destination site.
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 *
	 * @synopsis <validation_entity> [--remote_domain=<remote_domain>] [--remote_press_sync_key=<remote_press_sync_key>]
	 * @since NEXT

	 * @return void
	 */
	public function validate( $args, $assoc_args ) {
		if ( empty( $args ) ) {
			\WP_CLI::warning( 'You must choose an entity type to validate.' );
			return;
		}

		$validation_entity = filter_var( $args[0], FILTER_SANITIZE_STRING );

		if ( ! method_exists( $this, $validation_entity ) ) {
			\WP_CLI::warning( "{$validation_entity} is not a valid entity type." );
			return;
		}

		// Call the method in this class that handles the selected entity to validate.
		$this->{$validation_entity}( $assoc_args );
	}

	/**
	 * Get validation data for the Taxonomy entity.
	 *
	 * @throws ExitException Exception if url parameter is not passed in multisite.
	 * @since NEXT
	 */
	private function taxonomies( $args ) {
		if ( is_multisite() && ! \WP_CLI::get_config( 'url' ) ) {
			\WP_CLI::error( 'You must include the --url parameter when calling this command on WordPress multisite.' );
		}

		$taxonomy       = new Taxonomy();
		$taxonomy_count = $taxonomy->get_unique_taxonomy_count();
		$term_count     = $taxonomy->get_term_count_by_taxonomy();

		\WP_CLI::line( 'Local domain results:' );
		\WP_CLI::line( '' );
		\WP_CLI\Utils\format_items( 'table', array( array( 'unique_taxonomies' => $taxonomy_count ) ), array( 'unique_taxonomies' ) );
		\WP_CLI\Utils\format_items( 'table', $term_count, array( 'taxonomy_name', 'number_of_terms' ) );

		$json = API::get_remote_data( 'validation/taxonomy/count' );

		if ( $taxonomy_count === $json['unique_taxonomies'] && $term_count === $json['term_count_by_taxonomy'] ) {
			\WP_CLI::success( 'Taxonomies and term counts on remote domain are identical to the values printed above.' );
			return;
		}

		\WP_CLI::line( 'Remote domain results:' );
		\WP_CLI\Utils\format_items( 'table', array( array( 'unique_taxonomies' => $json['unique_taxonomies'] ) ), array( 'unique_taxonomies' ) );
		\WP_CLI\Utils\format_items( 'table', $json['term_count_by_taxonomy'], array( 'taxonomy_name', 'number_of_terms' ) );

		if ( $taxonomy_count !== $json['unique_taxonomies'] ) {
			\WP_CLI::warning( 'Discrepancy in number of unique taxonomies.' );
		}

		if ( $term_count !== $json['term_count_by_taxonomy'] ) {
			\WP_CLI::warning( 'Discrepancy in taxonomy term counts.' );
		}
	}
}
