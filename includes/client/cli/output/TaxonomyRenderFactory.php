<?php
namespace Press_Sync\client\output;

/**
 * Class TaxonomyRenderFactory
 *
 * @package Press_Sync\client\output
 */
class TaxonomyRenderFactory {
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
				return new TaxonomyCount( $data );
			case 'post_terms':
				return new TaxonomySample( $data );
			default:
				return new \WP_Error( 'data_not_found', 'Taxonomy data provided for processing does not adhere to contract.' );
		}
	}
}
