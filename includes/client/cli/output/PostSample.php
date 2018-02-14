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
		$this->output( $this->prepare( $this->data['comparison'] ), 'Sample comparison of local vs. remote data:' );
	}

	/**
	 * Output the data in a table.
	 *
	 * @param array  $data Data to output.
	 * @param string $message Message to display with the output.
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
	 * Prepare the table to be output in the CLI.
	 *
	 * @param array $data Data to prepare for output.
	 *
	 * @return array
	 */
	public function prepare( array $data ) {
		foreach ( $data as $index => $post_sample ) {
			foreach ( $post_sample as $key => $value ) {
				if ( 'post_id' === $key ) {
					$data[ $index ][ $key ] = $value;

					continue;
				}

				$data[ $index ][ $key ] = $this->get_result_icon( $value );
			}
		}

		return $data;
	}
}
