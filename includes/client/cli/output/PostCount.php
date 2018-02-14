<?php
namespace Press_Sync\client;

use Press_Sync\client\output\AbstractOutput;
use WP_CLI\Formatter;

/**
 * Class PostCount
 *
 * @package Press_Sync\client
 */
class PostCount extends AbstractOutput {
	/**
	 * @return mixed|void
	 */
	public function render() {
		$this->data = $this->prepare_colorized_output( $this->data );
		$this->output( $this->prepare( $this->data['source'] ), 'Local post counts by type and status:' );
		$this->output( $this->prepare( $this->data['destination'] ), 'Remote post counts by type and status:' );
	}

	/**
	 * Output data to the CLI.
	 *
	 * @param array  $data    Array of post data.
	 * @param string $message Optional message to print before the data table.
	 *
	 * @since NEXT
	 */
	public function output( $data, $message = '' ) {
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
	 *
	 */
	public function prepare( $post_data ) {
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
		foreach ( $data['destination'] as $post_type => $status ) {
			foreach ( $status as $status_name => $count ) {
				$data['destination'][ $post_type ][ $status_name ] = \WP_CLI::colorize( $data['comparison'][ $post_type ][ $status_name ] );
			}
		}

		return $data;
	}
}
