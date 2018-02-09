<?php
namespace Press_Sync\client\cli\command\validate;

use Press_Sync\validators\PostValidator;
use WP_CLI\ExitException;
use WP_CLI\Formatter;

/**
 * Class PostSubcommand
 *
 * @package Press_Sync\client\cli\command\validate
 * @since NEXT
 */
class PostSubcommand extends AbstractValidateSubcommand {
	/**
	 * PostValidateSubcommand constructor.
	 *
	 * @param array $args Associative args from the parent CLI command.
	 * @since NEXT
	 */
	public function __construct( $args ) {
		$this->args      = $args;
		$this->validator = new PostValidator( array(
			'format' => $this->get_data_output_format(),
		) );
	}

	/**
	 * Get validation data for Post entity.
	 *
	 * @throws ExitException Throw exception if --url argument is missing on multisite.
	 * @since NEXT
	 */
	public function validate() {
		$this->check_multisite_params();

		$data = $this->validator->validate();
		$data = $this->prepare_colorized_output( $data );

		$this->output( $this->prepare_output( $data['source']['count'] ), 'Local post counts by type and status:' );
		$this->output( $this->prepare_output( $data['destination']['count'] ), 'Remote post counts by type and status:' );
	}

	/**
	 * Output data to the CLI.
	 *
	 * @param array  $data    Array of post data.
	 * @param string $message Optional message to print before the data table.
	 *
	 * @since NEXT
	 */
	private function output( $data, $message = '' ) {
		if ( $message ) {
			\WP_CLI::line( $message );
		}

		$format     = 'table';
		$fields     = array_keys( $data[0] );
		$assoc_args = compact( 'format', 'fields' );
		$formatter  = new Formatter( $assoc_args );
		$formatter->display_items( $data, true );
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

	/**
	 * Prepare colorized output of value differences on destination site.
	 *
	 * @param array $data Data to colorize.
	 *
	 * @return array
	 * @since NEXT
	 */
	private function prepare_colorized_output( $data ) {
		foreach ( $data['destination']['count'] as $post_type => $status ) {
			foreach ( $status as $status_name => $count ) {
				$data['destination']['count'][ $post_type ][ $status_name ] = \WP_CLI::colorize( $data['comparison']['count'][ $post_type ][ $status_name ] );
			}
		}

		return $data;
	}
}
