<?php

/**
 * Helper class responsible for handling WordPress media attachments.
 *
 * Class Press_Sync_Media_Handler
 */
class Press_Sync_Media_Handler {
	/**
	 * Sets an image attachment
	 *
	 * @param int   $post_id   ID of the WP_Post that needs an image.
	 * @param array $post_args Array of arguments.
	 */
	public function attach_featured_image( $post_id, $post_args ) {
		// We don't have a post ID or the post does not have a featured image so bail early.
		if ( ! $post_id || empty( $post_args['featured_image'] ) ) {
			return;
		}

		// Allow download_url() to use an external request to retrieve featured images.
		add_filter( 'http_request_host_is_external', array( $this, 'allow_sync_external_host' ), 10, 3 );

		$request = new WP_REST_Request( 'POST' );
		$request->set_body_params( $post_args['featured_image'] );

		// Download the attachment.
		$attachment = $this->insert_new_media( $request, true );

		if ( isset( $attachment['id'] ) ) {
			set_post_thumbnail( $post_id, $attachment['id'] );
		}

		// Remove filter that allowed an external request to be made via download_url().
		remove_filter( 'http_request_host_is_external', array( $this, 'allow_sync_external_host' ) );
	}

	/**
	 * Sideload media into the media library.
	 *
	 * @param WP_REST_Request $request      Request object.
	 * @param bool            $return_local Whether to return the local version of the media.
	 *
	 * @return int
	 */
	public function insert_new_media( $request, $return_local = false ) {
		$data['id']     = 0;
		$attachment_args = $request->get_params();

		// Attachment URL does not exist so bail early.
		if ( ! array_key_exists( 'attachment_url', $attachment_args ) ) {
			return ( $return_local ) ? $data : wp_send_json_error( $data );
		}

		$attachment_url = $attachment_args['attachment_url'];

		unset( $attachment_args['attachment_url'] );

		require_once( ABSPATH . '/wp-admin/includes/image.php' );
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		require_once( ABSPATH . '/wp-admin/includes/media.php' );

		$media_id = $this->media_exists( $attachment_url );

		if ( $media_id ) {
			$data['id']      = $media_id;
			$data['message'] = 'file already exists';

			return ( $return_local ) ? $data : wp_send_json_error( $data );
		}

		// 1) Download the url
		$temp_file = download_url( $attachment_url, 5000 );

		$file_array['name'] = basename( $attachment_url );
		$file_array['tmp_name'] = $temp_file;

		// Something went wrong, let's bail.
		if ( is_wp_error( $temp_file ) ) {
			@unlink( $file_array['tmp_name'] ); // @codingStandardsIgnoreLine @TODO Maybe don't suppress errors.
			return ( $return_local ) ? $data : wp_send_json_error( $data );
		}

		$attachment_id = media_handle_sideload( $file_array, 0, '', $attachment_args );

		// Check for handle sideload errors.
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $file_array['tmp_name'] ); // @codingStandardsIgnoreLine @TODO Maybe don't suppress errors.
			return ( $return_local ) ? $data : wp_send_json_error( $data );
		}

		$data['id'] = $attachment_id;

		return ( $return_local ) ? $data : wp_send_json_success( $data );
	}

	/**
	 * Check whether media already exists in the library.
	 *
	 * @param string $media_url URL to the media file.
	 *
	 * @return int|string
	 */
	public function media_exists( $media_url ) {
		global $wpdb;

		$sql          = "SELECT ID FROM $wpdb->posts WHERE guid LIKE '%%%s%%' LIMIT 1;";
		$prepared_sql = $wpdb->prepare( $sql, basename( $media_url ) );
		$media_id     = $wpdb->get_var( $prepared_sql );

		return $media_id ? $media_id : 0;
	}

	/**
	 * Filter http_request_host_is_external to return true and allow external requests for the HTTP request.
	 *
	 * @param  bool   $allow  Should external requests be allowed.
	 * @param  string $host   IP of the requested host.
	 * @param  string $url    URL of the requested host.
	 *
	 * @return bool
	 */
	public function allow_sync_external_host( $allow, $host, $url ) {
		// Return true to allow an external request to be made via download_url().
		$allow = true;

		return $allow;
	}
}
