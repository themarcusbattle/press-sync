<?php
namespace Press_Sync\client\cli\command\validate;

use Press_Sync\client\output\TaxonomyRenderFactory;
use Press_Sync\validators\TaxonomyValidator;
use Press_Sync\client\output\OutputInterface;
use WP_CLI\ExitException;

/**
 * Class TaxonomySubcommand
 *
 * @package Press_Sync\client\cli\command\validate
 * @since NEXT
 */
class TaxonomySubcommand extends AbstractValidateSubcommand {
	/**
	 * Taxonomy constructor.
	 *
	 * @param array $args Associative args from the parent command.
	 * @since NEXT
	 */
	public function __construct( $args ) {
		$this->args      = $args;
		$this->validator = new TaxonomyValidator( array(
			'format' => $this->get_data_output_format(),
		) );
	}
	/**
	 * Get validation data for the Taxonomy entity.
	 *
	 * @throws ExitException Exception if url parameter is not passed in multisite.
	 * @since NEXT
	 */
	public function validate() {
		$this->check_multisite_params();

		$data        = $this->validator->validate();
		$output_data = array();

		foreach ( $data as $data_location => $location ) {
			foreach ( $location as $data_set => $values ) {
				$output_data[ $data_set ][ $data_location ] = $values;
			}
		}

		foreach ( $output_data as $key => $datum ) {
			/* @var $output OutputInterface */
			$output_renderer = TaxonomyRenderFactory::create( $key, $datum );

			if ( ! is_wp_error( $output_renderer ) ) {
				$output_renderer->render();
				continue;
			}

			\WP_CLI::error( $output_renderer->get_error_message() );
		}
	}
}
