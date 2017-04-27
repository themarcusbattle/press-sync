<?php

class Press_Sync_Media_Handler {
	public function attach_featured_image( $post_id, $post_args ) {

		// Post does not have a featured image so bail early.
		if ( empty( $post_args['featured_image'] ) ) {
			return false;
		}

		// Allow download_url() to use an external request to retrieve featured images.
		add_filter( 'http_request_host_is_external', array( $this, 'allow_sync_external_host' ), 10, 3 );

		$request = new WP_REST_Request( 'POST' );
		$request->set_body_params( $post_args['featured_image'] );

		// Download the attachment
		$attachment 	= $this->insert_new_media( $request, true );
		$thumbnail_id 	= isset( $attachment['id'] ) ? $attachment['id'] : 0;

		$response = set_post_thumbnail( $post_id, $thumbnail_id );

		// Remove filter that allowed an external request to be made via download_url().
		remove_filter( 'http_request_host_is_external', array( $this, 'allow_sync_external_host' ) );

	}

	/**
	 * @param      $request
	 * @param bool $return_local
	 */
	public function insert_new_media( $request, $return_local = false ) {

		$data['id'] = 0;

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

		if ( $media_id = $this->media_exists( $attachment_url ) ) {

			$data['id'] = $media_id;
			$data['message'] = 'file already exists';

			return ( $return_local ) ? $data : wp_send_json_error( $data );

		}

		// 1) Download the url
		$temp_file = download_url( $attachment_url, 5000 );

		$file_array['name'] = basename( $attachment_url );
		$file_array['tmp_name'] = $temp_file;

		if ( is_wp_error( $temp_file ) ) {
			@unlink( $file_array['tmp_name'] );
			return ( $return_local ) ? $data : wp_send_json_error( $data );
		}

		$attachment_id = media_handle_sideload( $file_array, 0, '', $attachment_args );

		// Check for handle sideload errors.
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $file_array['tmp_name'] );
			return ( $return_local ) ? $data : wp_send_json_error( $data );
		}

		$data['id'] = $attachment_id;

		return ( $return_local ) ? $data : wp_send_json_success( $data );

	}

	public function media_exists( $media_url ) {

		global $wpdb;

		$media_url = basename( $media_url );

		$sql = "SELECT ID FROM $wpdb->posts WHERE guid LIKE '%%%s%%' LIMIT 1;";
		$prepared_sql = $wpdb->prepare( $sql, $media_url );

		$media_id = $wpdb->get_var( $prepared_sql );

		if ( $media_id ) {
			return $media_id;
		}

		return 0;

	}
}
