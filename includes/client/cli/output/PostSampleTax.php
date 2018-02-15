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
		$this->prepare( $this->data );
		// $this->output( $this->data, 'Post taxonomies and entities:' );
		\WP_CLI::line( 'Ran post sample taxonomies' );
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
		$fields     = array_keys( $data[0] );
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
	public function prepare( array $data ) {
		$source      = $data['source'];
		$destination = $data['destination'];

		if ( $source ) {

		}

		// @TODO Fix source and destination as with post sample to ensure parity.

		/*
		 * Return an array with all the data the source has: post ID, taxonomy name, term, and whether the destination has it.
		 *
		 * Each array would contain an array:
		 * [
		 *     'post_id' => ID,
		 *     'taxonomy_name' => taxonomy
		 *     'term'=> 'term',
		 *     'migrated' => X or O
		 * ]
		 */

	}
}
