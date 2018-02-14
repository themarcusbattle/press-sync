<?php
namespace Press_Sync\client\cli\command\validate;

use Press_Sync\client\output\OutputInterface;
use Press_Sync\client\output\PostRenderFactory;
use Press_Sync\validators\PostValidator;
use WP_CLI\ExitException;

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
			'sample_count' => 5,
			'format'       => $this->get_data_output_format(),
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

		$data        = $this->validator->validate();
		$output_data = array();

		foreach ( $data as $data_location => $location ) {
			foreach ( $location as $data_set => $values ) {
				$output_data[ $data_set ][ $data_location ] = $values;
			}
		}

		foreach ( $output_data as $key => $datum ) {
			/* @var $output OutputInterface */
			$output_renderer = PostRenderFactory::create( $key, $datum );

			if ( ! is_wp_error( $output_renderer ) ) {
				$output_renderer->render();
				continue;
			}

			\WP_CLI::error( $output_renderer->get_error_message() );
		}
	}
}
