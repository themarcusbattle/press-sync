<?php
namespace Press_Sync\client\cli;

use Press_Sync\validation\Taxonomy;

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
	 * @return void
	 *
	 * @since NEXT
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
		$this->{$validation_entity}();
	}

	/**
	 * Get validation data for the Taxonomy entity.
	 *
	 * @since NEXT
	 */
	private function taxonomies() {
		$taxonomy = new Taxonomy();

		\WP_CLI\Utils\format_items( 'table', array( array( 'unique_taxonomies' => $taxonomy->get_unique_taxonomy_count() ) ), array( 'unique_taxonomies' ) );
		\WP_CLI\Utils\format_items( 'table', $taxonomy->get_term_count_by_taxonomy(), array( 'taxonomy_name', 'number_of_terms' ) );
	}
}
