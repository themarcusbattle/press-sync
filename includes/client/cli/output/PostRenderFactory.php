<?php
namespace Press_Sync\client\output;

/**
 * Class PostRenderFactory
 *
 * @package Press_Sync\client\output
 */
class PostRenderFactory {
	/**
	 * Render data set to the CLI.
	 *
	 * @since NEXT
	 * @param string $key  Data key.
	 * @param array  $data Data values.
	 * @return OutputInterface|\WP_Error
	 */
	public static function create( string $key, array $data ) {
		switch ( $key ) {
			case 'count':
				return new PostCount( $data );
			case 'sample':
				return new PostSample( $data );
			case 'sample_tax':
				return new PostSampleTax( $data );
			default:
				return new \WP_Error( 'data_not_found', 'Data provided for processing does not adhere to contract.' );
		}
	}
}
