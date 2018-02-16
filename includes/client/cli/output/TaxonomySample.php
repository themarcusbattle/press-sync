<?php
namespace Press_Sync\client\output;

/**
 * Class TaxonomySample
 *
 * @package Press_Sync\client\output
 */
class TaxonomySample extends AbstractOutput {
	/**
	 * Render data to the client.
	 *
	 * @since NEXT
	 */
	public function render() {
		$this->output( $this->data['comparison'], 'Post counts by taxonomy term:' );
	}

	/**
	 * @param array  $data
	 * @param string $message
	 */
	public function output( array $data, $message = '' ) {
		\WP_CLI\Utils\format_items( 'table', $data['term_count_by_taxonomy'], array( 'taxonomy_name', 'number_of_terms' ) );
	}
}
