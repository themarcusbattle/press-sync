<?php
namespace Press_Sync\client\cli\command\validate;

use Press_Sync\API;
use Press_Sync\validation\Post;
use Press_Sync\validation\ValidatorInterface;
use WP_CLI\ExitException;

/**
 * Class PostValidator
 *
 * @package Press_Sync\client\cli\command\validate
 * @since NEXT
 */
class PostValidator extends AbstractValidator implements ValidatorInterface {
	/**
	 * PostValidator constructor.
	 *
	 * @param array $args Associative args from the parent CLI command.
	 * @since NEXT
	 */
	public function __construct( $args ) {
		$this->args = $args;
	}

	/**
	 * Get validation data for Post entity.
	 *
	 * @throws ExitException Throw exception if --url argument is missing on multisite.
	 * @since NEXT
	 */
	public function validate() {
		$this->check_multisite_params();

		$post_count_data        = $this->prepare_output( $this->get_source_data() );
		$remote_post_count_data = $this->prepare_output( $this->get_destination_data() );

		$this->output( $post_count_data, 'Local post counts by type and status:' );
		$this->output( $remote_post_count_data, 'Remote post counts by type and status:' );

		if ( $post_count_data !== $remote_post_count_data ) {
			\WP_CLI::warning( 'Discrepancy in post counts.' );
		}
	}

	/**
	 * Get data from the local WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_source_data() {
		return ( new Post() )->get_count();
	}

	/**
	 * Get data from the remote WordPress installation.
	 *
	 * @return array
	 * @since NEXT
	 */
	public function get_destination_data() {
		return API::get_remote_data( 'validation/post/count' );
	}


	/**
	 * Output data to the CLI.
	 *
	 * @param array  $post_data Array of post data.
	 * @param string $message   Optional message to print before the data table.
	 *
	 * @since NEXT
	 */
	private function output( $post_data, $message = '' ) {
		if ( $message ) {
			\WP_CLI::line( $message );
		}

		\WP_CLI\Utils\format_items( 'table', $post_data, array_keys( $post_data[0] ) );
	}

	/**
	 * Prepare data for output to the CLI.
	 *
	 * @param array $post_data Array of post data.
	 *
	 * @return array
	 * @since NEXT
	 */
	private function prepare_output( $post_data ) {
		$table_values = array();

		foreach ( $post_data as $post_type => $post_status_count ) {
			$new_array              = array();
			$new_array['post_type'] = $post_type;

			foreach ( $post_status_count as $status => $count ) {
				$new_array[ $status ] = $count;
			}

			$table_values[] = $new_array;
		}

		return $table_values;
	}
}
