<?php
use Press_Sync_API_Validator as Validator;
use Press_Sync_Data_Synchronizer as Synchronizer;

/**
 * Class Press_Sync_API_Route_Attachment
 */
class Press_Sync_API_Route_Attachment extends Press_Sync_API_Abstract_Route_Post_Type {
	/**
	 * Press_Sync_API_Route_Attachment constructor.
	 *
	 * @param Press_Sync_API_Validator     $validator    Data validation helper class.
	 * @param Press_Sync_Data_Synchronizer $synchronizer Post synchronization helper class.
	 */
	public function __construct( Validator $validator, Synchronizer $synchronizer ) {
		$this->validator    = $validator;
		$this->synchronizer = $synchronizer;
	}

	/**
	 * Register endpoints for this API route.
	 */
	public function register_routes() {
		register_rest_route( 'press-sync/v1', '/attachment', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'sync_media' ),
			'permission_callback' => array( $this->validator, 'validate_sync_key' ),
		) );
	}

	/**
	 * // @TODO Figure out how to reuse insert_new_media method in the Media_Handler class.
	 * @param      $attachment_args
	 * @param      $duplicate_action
	 * @param bool $force_update
	 *
	 * @return mixed
	 */
	public function sync_media( $attachment_args, $duplicate_action, $force_update = false ) {

		$response['id'] = 0;

		// Attachment URL does not exist so bail early.
		if ( ! array_key_exists( 'attachment_url', $attachment_args ) ) {
			return $response;
		}

		$attachment_url = $attachment_args['attachment_url'];

		unset( $attachment_args['attachment_url'] );

		require_once( ABSPATH . '/wp-admin/includes/image.php' );
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		require_once( ABSPATH . '/wp-admin/includes/media.php' );

		if ( $media_id = $this->synchronizer->media_handler->media_exists( $attachment_url ) ) {

			$response['id'] = $media_id;
			$response['message'] = 'file already exists';

			return $response;

		}

		// 1) Download the url
		$temp_file = download_url( $attachment_url, 5000 );

		$file_array['name'] = basename( $attachment_url );
		$file_array['tmp_name'] = $temp_file;

		if ( is_wp_error( $temp_file ) ) {
			@unlink( $file_array['tmp_name'] );
			return $response;
		}

		$attachment_id = media_handle_sideload( $file_array, 0, '', $attachment_args );

		// Check for handle sideload errors.
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $file_array['tmp_name'] );
			return $response;
		}

		$response['id'] = $attachment_id;

		return $response;

	}
}
