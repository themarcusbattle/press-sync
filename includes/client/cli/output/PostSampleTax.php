<?php
namespace Press_Sync\client\output;

/**
 * Class PostSampleTax
 *
 * @package Press_Sync\client\output
 */
class PostSampleTax extends AbstractOutput {
	/**
	 * @return mixed|void
	 */
	public function render() {
		\WP_CLI::line( 'Ran post sample taxonomies' );
	}
}
