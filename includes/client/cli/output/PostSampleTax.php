<?php
namespace Press_Sync\client\output;

use WP_CLI\Formatter;

/**
 * Class PostSampleTax
 *
 * @package Press_Sync\client\output
 */
class PostSampleTax extends AbstractOutput {
	/**
	 * Render the post sample taxonomy/entity data to the client.
	 *
	 * @return mixed|void
	 */
	public function render() {
		$this->output( $this->prepare( $this->data['comparison'] ), 'Sample post taxonomy comparison:' );
	}

	/**
	 * Output data to the CLI.
	 *
	 * @param array  $data    Array of post data.
	 * @param string $message Optional message to print before the data table.
	 *
	 * @since NEXT
	 */
	public function output( array $data, $message = '' ) {
		if ( $message ) {
			\WP_CLI::line( $message );
		}

		$format     = 'table';
		$fields     = array_keys( current( $data ) );
		$assoc_args = compact( 'format', 'fields' );
		$formatter  = new Formatter( $assoc_args );
		$formatter->display_items( $data, true );
	}

	/**
	 * Prepare data for rendering to the client.
	 *
	 * @param array $data Data to prepare.
	 *
	 * @return array $data
	 */
	public function prepare( $data ) {
		foreach ( $data as $key => $post_tax_data ) {
			$data[ $key ]['terms_migrated'] = \WP_CLI::colorize( $data[ $key ]['terms_migrated'] );
		}

		return $data;
	}
}
