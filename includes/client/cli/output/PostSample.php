<?php
namespace Press_Sync\client\output;

use WP_CLI\Formatter;

/**
 * Class PostSample
 *
 * @package Press_Sync\client\output
 */
class PostSample extends AbstractOutput {
	/**
	 * Render output to the client.
	 *
	 * @return void
	 */
	public function render() {
		\WP_CLI::line( 'Comparison of local vs. remote data:' );
		$this->output( $this->prepare( $this->data['source'], $this->data['destination'] ) );

		if ( count( $this->data['source'] ) !== count( $this->data['destination'] ) ) {
			\WP_CLI::warning( 'Destination sample missing posts from source.' );
		}
	}

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
	 * Prepare the table to be output in the CLI.
	 *
	 * @param $source_data
	 * @param $destination_data
	 *
	 * @return array
	 */
	public function prepare( $source_data, $destination_data ) {
		$table_output = array();

		foreach ( $source_data as $index => $post_data ) {
			foreach ( $post_data as $key => $data ) {
				if ( 'ID' === $key ) {
					$table_output[ $index ]['post_id'] = $data;

					continue;
				}

				$table_output[ $index ][ $key ] = ( $source_data[ $index ][ $key ] === $destination_data[ $index ][ $key ] ) ? 'X' : 'O';
			}
		}

		return $table_output;
	}
}
