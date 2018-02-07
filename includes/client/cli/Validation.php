<?php
namespace Press_Sync\client\cli;

use Press_Sync\validation\Post;
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
	 * ## OPTIONS
	 *
	 * <validation_entity>
	 * : The type of entity to validate.
	 * options:
	 *   - posts
	 *   - taxonomies
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
	 * @param array $args Associative arguments from the validate command.
	 *
	 * @throws ExitException Exception if url parameter is not passed in multisite.
	 * @return void
	 * @since NEXT
	 */
	private function taxonomies( $args ) {
		if ( is_multisite() && ! \WP_CLI::get_config( 'url' ) ) {
			\WP_CLI::error( 'You must include the --url parameter when calling this command on WordPress multisite.' );
		}

		$count          = ( new Taxonomy() )->get_count();
		$taxonomy_count = $count['unique_taxonomies'];
		$term_count     = $count['term_count_by_taxonomy'];

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

	/**
	 * Get validation data for Post entity.
	 *
	 * @param array $args Associative arguments from the validate command.
	 *
	 * @throws ExitException Throw exception if --url argument is missing on multisite.
	 * @since NEXT
	 */
	private function posts( $args ) {
		if ( is_multisite() && ! \WP_CLI::get_config( 'url' ) ) {
			\WP_CLI::error( 'You must include the --url parameter when calling this command on WordPress multisite.' );
		}

		$post_count_data   = ( new Post() )->get_count();
		$json              = API::get_remote_data( 'validation/post/count' );
		$post_count_values = $this->prepare_post_data_for_table_rendering( $post_count_data );
		$json_values       = $this->prepare_post_data_for_table_rendering( $json );

		$this->render_post_data_table( $post_count_values );
		$this->render_post_data_table( $json_values );


		if ( $post_count_values !== $json_values ) {
			\WP_CLI::warning( 'Discrepancy in post counts.' );
		}
	}

	private function render_post_data_table( $table_values ) {
		\WP_CLI\Utils\format_items(
			'table',
			array_filter(
				$table_values,
				function ( $table_values ) {
					return $table_values;
				}
			),
			array_keys( $table_values[0] )
		);
	}

	private function prepare_post_data_for_table_rendering( $count ) {
		$table_values = array();

		foreach ( $count as $post_type => $values ) {
			$new_array              = array();
			$new_array['post_type'] = $post_type;

			foreach ( get_object_vars( $values ) as $key => $value ) {
				$new_array[ $key ] = $value;
			}

			$table_values[] = $new_array;
		}

		return $table_values;
	}

	private function get_table_values( $count ) {
		return $count[0];
	}
}
