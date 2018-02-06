<?php
namespace Press_Sync\client\cli;

use Press_Sync\validation\Taxonomy;
use Press_Sync\API;

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
	 * @since NEXT
	 */
	private function taxonomies( $args ) {
		$taxonomy = new Taxonomy();

		\WP_CLI\Utils\format_items( 'table', array( array( 'unique_taxonomies' => $taxonomy->get_unique_taxonomy_count() ) ), array( 'unique_taxonomies' ) );
		\WP_CLI\Utils\format_items( 'table', $taxonomy->get_term_count_by_taxonomy(), array( 'taxonomy_name', 'number_of_terms' ) );

		$json = $this->get_remote_data( 'taxonomy' );

		\WP_CLI::success( print_r( $json, true ) );
	}

	/**
	 * Get remote data from an API request.
	 *
	 * @since NEXT
	 * @param  string $request The requested datapoint.
	 * @return array
	 */
	public function get_remote_data( $request ) {
		$url = API::get_remote_url( 'http://cmt-single.localhost/', "validation/taxonomy", array(
			'request' => $request,
		) );

		$response = API::get_remote_response( $url );

		if ( empty( $response['body']['success'] ) ) {
			return array();
		}

		return $response['body']['data'];
	}
}
