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
 */
class PostValidator implements ValidatorInterface {
	/**
	 * @var
	 */
	private $args;

	/**
	 * PostValidator constructor.
	 *
	 * @param $args
	 */
	public function __construct( $args ) {
		$this->args = $args;
	}

	/**
	 * Get validation data for Post entity.
	 *
	 * @param array $args Associative arguments from the validate command.
	 *
	 * @throws ExitException Throw exception if --url argument is missing on multisite.
	 * @since NEXT
	 */
	public function validate() {
		try {
			if ( is_multisite() && ! \WP_CLI::get_config( 'url' ) ) {
				throw new ExitException();
			}

			$post_count_data        = ( new Post() )->get_count();
			$remote_post_count_data = API::get_remote_data( 'validation/post/count' );

			$prepared_post_count_data        = $this->prepare_post_data_for_output( $post_count_data );
			$prepared_remote_post_count_data = $this->prepare_post_data_for_output( $remote_post_count_data );

			\WP_CLI::line( 'Local post counts by type and status:' );
			$this->output_post_data_table( $prepared_post_count_data );

			\WP_CLI::line( 'Remote post counts by type and status:' );
			$this->output_post_data_table( $prepared_remote_post_count_data );

			if ( $prepared_post_count_data !== $prepared_remote_post_count_data ) {
				\WP_CLI::warning( 'Discrepancy in post counts.' );
			}
		} catch ( ExitException $e ) {
			\WP_CLI::error( 'You must include the --url parameter when calling this command on WordPress multisite.' );
		}
	}

	/**
	 *
	 */
	public function get_source_data() {
		// TODO: Implement get_source_data() method.
	}

	/**
	 *
	 */
	public function get_destination_data() {
		// TODO: Implement get_destination_data() method.
	}

	/**
	 * Outputs the post data table in the CLI.
	 *
	 * @param array $post_data Table data.
	 */
	private function output_post_data_table( $post_data ) {
		\WP_CLI\Utils\format_items( 'table', $post_data, array_keys( $post_data[0] ) );
	}

	/**
	 * @param $post_data
	 *
	 * @return array
	 */
	private function prepare_post_data_for_output( $post_data ) {
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
