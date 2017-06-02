<?php

class Press_Sync_API extends WP_REST_Controller {

	/**
	 * Parent plugin class.
	 *
	 * @var   Press_Sync
	 * @since 0.1.0
	 */
	protected $plugin = null;

	/**
	 * Prefix for meta keys
	 *
	 * @var string
	 * @since  0.1.0
	 */
	protected $prefix = 'press_sync_api_';

	/**
	 * Constructor.
	 *
	 * @since  0.1.0
	 *
	 * @param  Press_Sync $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Add our hooks.
	 *
	 * @since  0.1.0
	 */
	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );
		add_action( 'press_sync_sync_post', array( $this, 'add_p2p_connections' ), 10, 2 );
	}

	/**
	 * Register the api endpoints
	 *
	 * @since 0.1.0
	 */
	public function register_api_endpoints() {

		register_rest_route( 'press-sync/v1', '/status', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_connection_status_via_api' ),
		) );

		register_rest_route( 'press-sync/v1', '/status/(?P<id>\d+)', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_post_sync_status_via_api' ),
		) );

		register_rest_route( 'press-sync/v1', '/sync', array(
			'methods'             => array( 'GET', 'POST' ),
			'callback'            => array( $this, 'sync_objects' ),
			'permission_callback' => array( $this, 'validate_sync_key' ),
		) );

		/* register_rest_route( 'press-sync/v1', '/sync/(?P<id>\d+)', array(
			'methods' => array( 'GET', 'POST' ),
			'callback' => array( $this, 'sync_objects' ),
		) ); */

	}

	/**
	 * Gets the connection status via API request
	 *
	 * @since 0.1.0
	 * @return void JSON data on success. JSON error object on failure.
	 */
	public function get_connection_status_via_api() {

		if ( ! $this->validate_sync_key() ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * Gets the post sync status via API request
	 *
	 * @param WP_Rest_Request $request
	 *
	 * @since 0.2.0
	 * @return array
	 */
	public function get_post_sync_status_via_api( $request ) {

		$press_sync_post_id = $request->get_param( 'id' );

		if ( ! $press_sync_post_id ) {
			wp_send_json_error();
		}

		// Look for the post on the server
		$post = $this->get_post_by_orig_id( $press_sync_post_id );

		if ( ! $post ) {
			return array(
				'remote_post_id' => $post->ID,
				'status'         => 'not synced',
			);
		}

		return array(
			'remote_post_id'              => $post->ID,
			'remote_post_modified'        => $post->post_modified,
			'remote_post_gmt_offset'      => get_option( 'gmt_offset' ),
			'remote_post_modified_offset' => date( 'Y-m-d H:i:s', strtotime( $post->post_modified ) + ( get_option( 'gmt_offset' ) * 60 * 60 ) ),
			'status'                      => 'synced',
		);

	}

	/**
	 * Validate the supplied press_sync_key by the sending server.
	 * Target server can't receive data without a valid press_sync_key.
	 *
	 * @since 0.1.0
	 */
	public function validate_sync_key() {

		$press_sync_key_from_remote = isset( $_REQUEST['press_sync_key'] ) ? $_REQUEST['press_sync_key'] : '';
		$press_sync_key             = $this->plugin->press_sync_option( 'press_sync_key' );

		if ( ! $press_sync_key || ( $press_sync_key_from_remote != $press_sync_key ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Sync all of the object received from the local server
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Request
	 *
	 * @return WP_REST_Response
	 */
	public function sync_objects( $request ) {

		$objects_to_sync  = $request->get_param( 'objects_to_sync' );
		$objects          = $request->get_param( 'objects' );
		$duplicate_action = ( $request->get_param( 'duplicate_action' ) ) ? $request->get_param( 'duplicate_action' ) : 'skip';
		$force_update     = $request->get_param( 'force_update' );

		if ( ! $objects_to_sync ) {
			wp_send_json_error( array(
				'debug' => __( 'Not sure which WP object you want to sync', 'press-sync' )
			) );
		}

		if ( ! $objects ) {
			wp_send_json_error( array(
				'debug' => __( 'No data available to sync', 'press-sync' )
			) );
		}

		// If the method is to pull then how do
		if ( 'GET' == $request->get_method() ) {

			$where_clause = "ID IN ('" . implode( "''", $objects ) . "')";
			$taxonomies   = get_object_taxonomies( $objects_to_sync );

			return $this->plugin->get_objects_to_sync( $objects_to_sync, 1, $taxonomies, $where_clause );

		}

		// Speed up bulk queries by pausing MySQL commits
		global $wpdb;

		$wpdb->query( 'SET AUTOCOMMIT = 0;' );
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );

		$responses = array();

		foreach ( $objects as $object ) {
			$sync_method = "sync_{$objects_to_sync}";
			$responses[] = $this->$sync_method( $object, $duplicate_action, $force_update );
		}

		// Commit all recent updates
		$wpdb->query( 'COMMIT;' );
		$wpdb->query( 'SET AUTOCOMMIT = 1;' );
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		return $responses;

	}

	/**
	 * Syncs a post of any type
	 *
	 * @since 0.1.0
	 *
	 * @param array  $post_args
	 * @param string $duplicate_action
	 *
	 * @return array
	 */
	public function sync_post( $post_args, $duplicate_action, $force_update = false ) {

		if ( ! $post_args ) {
			return false;
		}

		// Replace embedded media
		if ( $post_args['embedded_media'] ) {

			foreach ( $post_args['embedded_media'] as $embedded_media ) {

				$attachment = $this->sync_media( array(
					'attachment_url' => $embedded_media
				) );

				$attachment_url            = isset( $attachment['attachment_url'] ) ? $attachment['attachment_url'] : $embedded_media;
				$post_args['post_content'] = str_ireplace( $embedded_media, $attachment_url, $post_args['post_content'] );

			}

		}

		// Set the correct post author
		$post_args['post_author'] = $this->get_press_sync_author_id( $post_args['post_author'] );

		// Check for post parent and update IDs accordingly
		if ( isset( $post_args['post_parent'] ) && $post_parent_id = $post_args['post_parent'] ) {

			$post_parent_args['post_type']                        = $post_args['post_type'];
			$post_parent_args['meta_input']['press_sync_post_id'] = $post_parent_id;

			$parent_post = $this->get_synced_post( $post_parent_args );

			$post_args['post_parent'] = ( $parent_post ) ? $parent_post['ID'] : 0;

		}

		// Check to see if the post exists
		$local_post = $this->get_synced_post( $post_args );

		// Check to see a non-synced duplicate of the post exists
		if ( 'sync' == $duplicate_action && ! $local_post ) {
			$local_post = $this->get_non_synced_duplicate( $post_args['post_name'], $post_args['post_type'] );
		}

		// Update the existing ID of the post if present
		$post_args['ID'] = isset( $local_post['ID'] ) ? $local_post['ID'] : 0;

		// Determine which content is newer, local or remote
		if ( ! $force_update && $local_post && ( strtotime( $local_post['post_modified'] ) >= strtotime( $post_args['post_modified'] ) ) ) {

			// If we're here, then we need to keep our local version
			$response['remote_post_id'] = $post_args['meta_input']['press_sync_post_id'];
			$response['local_post_id']  = $local_post['ID'];
			$response['message']        = __( 'Local version is newer than remote version', 'press-sync' );

			// Assign a press sync ID
			$this->add_press_sync_id( $local_post['ID'], $post_args );

			return array( 'debug' => $response );

		}

		// Add categories
		if ( isset( $post_args['tax_input']['category'] ) && $post_args['tax_input']['category'] ) {

			require_once( ABSPATH . '/wp-admin/includes/taxonomy.php' );

			foreach ( $post_args['tax_input']['category'] as $category ) {
				wp_insert_category( array(
					'cat_name' => $category
				) );
				$post_args['post_category'][] = $category;
			}

		}

		// Insert/update the post
		$local_post_id = wp_insert_post( $post_args );

		// Bail if the insert didn't work
		if ( is_wp_error( $local_post_id ) ) {
			return array( 'debug' => $local_post_id );
		}

		// Attach featured image
		$this->attach_featured_image( $local_post_id, $post_args );

		// Attach any comments
		$comments = isset( $post_args['comments'] ) ? $post_args['comments'] : array();
		$this->attach_comments( $local_post_id, $comments );

		// Set taxonomies for custom post type
		// if ( ! in_array( $post_args['post_type'], array( 'post', 'page' ) ) ) {

		if ( isset( $post_args['tax_input'] ) ) {

			foreach ( $post_args['tax_input'] as $taxonomy => $terms ) {
				wp_set_object_terms( $local_post_id, $terms, $taxonomy, false );
			}

		}

		// }

		// Run any secondary commands
		do_action( 'press_sync_sync_post', $post_id, $post_args );

		return array(
			'debug' => array(
				'remote_post_id' => $post_args['meta_input']['press_sync_post_id'],
				'local_post_id'  => $local_post_id,
				'message'        => __( 'The post has been synced with the remote server', 'press-sync' ),
			)
		);

	}

	public function sync_media( $attachment_args, $duplicate_action = 'skip', $force_update = false ) {

		$response['attachment_id'] = 0;

		// Attachment URL does not exist so bail early.
		if ( ! array_key_exists( 'attachment_url', $attachment_args ) ) {
			return $response;
		}

		$attachment_url = $attachment_args['attachment_url'];

		unset( $attachment_args['attachment_url'] );

		require_once( ABSPATH . '/wp-admin/includes/image.php' );
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		require_once( ABSPATH . '/wp-admin/includes/media.php' );

		if ( $attachment_id = $this->media_exists( $attachment_url ) ) {

			$response['attachment_id']  = $attachment_id;
			$response['message']        = 'file already exists';
			$response['attachment_url'] = wp_get_attachment_url( $attachment_id );

			return $response;

		}

		// 1) Download the url
		$temp_file = download_url( $attachment_url, 5000 );

		$file_array['name']     = basename( $attachment_url );
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

		$response['attachment_id']  = $attachment_id;
		$response['attachment_url'] = wp_get_attachment_url( $attachment_id );

		return $response;

	}

	public function sync_user( $user_args, $duplicate_action, $force_update = false ) {

		$username = isset( $user_args['user_login'] ) ? $user_args['user_login'] : '';

		// Check to see if the user exists
		$user = get_user_by( 'login', $username );

		if ( ! $user ) {

			$user_id = wp_insert_user( $user_args );

			if ( is_wp_error( $user_id ) ) {
				return wp_send_json_error();
			}

			$user = get_user_by( 'id', $user_id );

		} else {
			$user_id = $user->ID;
		}

		// Update the meta
		foreach ( $user_args['meta_input'] as $usermeta_key => $usermeta_value ) {
			update_user_meta( $user_id, $usermeta_key, $usermeta_value );
		}

		// Asign user role
		$user->add_role( $user_args['role'] );

		// Prepare response
		$response['user_id'] = $user_id;
		$response['blog_id'] = get_current_blog_id();

		return $response;

	}

	public function get_synced_post( $post_args ) {

		global $wpdb;

		// Capture the press sync post ID
		$press_sync_post_id = isset( $post_args['meta_input']['press_sync_post_id'] ) ? $post_args['meta_input']['press_sync_post_id'] : 0;

		$sql = "
			SELECT post_id AS ID, post_type, post_modified FROM $wpdb->postmeta AS meta
			LEFT JOIN $wpdb->posts AS posts ON posts.ID = meta.post_id
			WHERE meta.meta_key = 'press_sync_post_id' AND meta.meta_value = %d AND posts.post_type = %s
			LIMIT 1
		";

		$prepared_sql = $wpdb->prepare( $sql, $press_sync_post_id, $post_args['post_type'] );
		$post         = $wpdb->get_row( $prepared_sql, ARRAY_A );

		return ( $post ) ? $post : false;

	}

	public function media_exists( $media_url ) {

		global $wpdb;

		$media_url = basename( $media_url );

		$sql          = "SELECT ID FROM $wpdb->posts WHERE guid LIKE '%%%s%%' LIMIT 1;";
		$prepared_sql = $wpdb->prepare( $sql, $media_url );

		$media_id = $wpdb->get_var( $prepared_sql );

		if ( $media_id ) {
			return $media_id;
		}

		return 0;

	}

	public function comment_exists( $comment_args = array() ) {

		$press_sync_comment_id = isset( $comment_args['meta_input']['press_sync_comment_id'] ) ? $comment_args['meta_input']['press_sync_comment_id'] : 0;
		$press_sync_source     = isset( $comment_args['meta_input']['press_sync_source'] ) ? $comment_args['meta_input']['press_sync_source'] : 0;

		$query_args = array(
			'number'     => 1,
			'meta_query' => array(
				array(
					'key'     => 'press_sync_comment_id',
					'value'   => $press_sync_comment_id,
					'compare' => '='
				),
				array(
					'key'     => 'press_sync_source',
					'value'   => $press_sync_source,
					'compare' => '='
				),
			)
		);

		$comment = get_comments( $query_args );

		if ( $comment ) {
			return (array) $comment[0];
		}

		return false;

	}

	public function get_post_by_orig_id( $press_sync_post_id ) {

		global $wpdb;

		$sql = "SELECT ID, post_modified
		FROM $wpdb->posts AS posts
		LEFT JOIN $wpdb->postmeta AS meta ON meta.post_id = posts.ID
		WHERE meta.meta_key = 'press_sync_post_id' AND meta.meta_value = $press_sync_post_id";

		return $wpdb->get_row( $sql );

	}

	/**
	 * Returns the original author ID of the synced post
	 *
	 * @since 0.1.0
	 *
	 * @param integer $user_id
	 *
	 * @return integer $user_id
	 */
	public function get_press_sync_author_id( $user_id ) {

		if ( ! $user_id ) {
			return 1;
		}

		global $wpdb;

		$sql          = "SELECT user_id AS ID FROM $wpdb->usermeta WHERE meta_key = 'press_sync_user_id' AND meta_value = %d";
		$prepared_sql = $wpdb->prepare( $sql, $user_id );

		$press_sync_user_id = $wpdb->get_var( $prepared_sql );

		return ( $press_sync_user_id ) ? $press_sync_user_id : 1;

	}

	public function attach_featured_image( $post_id, $post_args ) {

		// Post does not have a featured image so bail early.
		if ( empty( $post_args['featured_image'] ) ) {
			return false;
		}

		// Allow download_url() to use an external request to retrieve featured images.
		add_filter( 'http_request_host_is_external', array( $this, 'allow_sync_external_host' ), 10, 3 );

		// Download the attachment
		$attachment   = $this->sync_media( $post_args['featured_image'], true );
		$thumbnail_id = isset( $attachment['id'] ) ? $attachment['id'] : 0;

		$response = set_post_thumbnail( $post_id, $thumbnail_id );

		// Remove filter that allowed an external request to be made via download_url().
		remove_filter( 'http_request_host_is_external', array( $this, 'allow_sync_external_host' ) );

	}

	/**
	 * Filter http_request_host_is_external to return true and allow external requests for the HTTP request.
	 *
	 * @param  bool   $allow Should external requests be allowed.
	 * @param  string $host  IP of the requested host.
	 * @param  string $url   URL of the requested host.
	 *
	 * @return bool
	 */
	public function allow_sync_external_host( $allow, $host, $url ) {
		// Return true to allow an external request to be made via download_url().
		$allow = true;

		return $allow;
	}

	public function attach_comments( $post_id, $comments ) {

		if ( empty( $post_id ) || ! $comments ) {
			return;
		}

		foreach ( $comments as $comment_args ) {

			// Check to see if the comment already exists
			if ( $comment = $this->comment_exists( $comment_args ) ) {
				continue;
			}

			// Set Comment Post ID to correct local Post ID
			$comment_args['comment_post_ID'] = $post_id;

			// Get the comment author ID
			$comment_args['user_id'] = $this->get_press_sync_author_id( $comment_args['post_author'] );

			$comment_id = wp_insert_comment( $comment_args );

			if ( ! is_wp_error( $comment_id ) ) {

				foreach ( $comment_args['meta_input'] as $meta_key => $meta_value ) {
					update_comment_meta( $comment_id, $meta_key, $meta_value );
				}
			}

		}

	}

	public function add_p2p_connections( $post_id, $post_args ) {

		if ( ! class_exists( 'P2P_Autoload' ) || ! $post_args['p2p_connections'] ) {
			return;
		}

		$connections = isset( $post_args['p2p_connections'] ) ? $post_args['p2p_connections'] : array();

		if ( ! $connections ) {
			return;
		}

		foreach ( $connections as $connection ) {

			$p2p_from = $this->get_post_id_by_press_sync_id( $connection['p2p_from'] );
			$p2p_to   = $this->get_post_id_by_press_sync_id( $connection['p2p_to'] );
			$p2p_type = $connection['p2p_type'];

			$response = p2p_type( $p2p_type )->connect( $p2p_from, $p2p_to );

		}

	}

	public function get_post_id_by_press_sync_id( $press_sync_post_id ) {

		global $wpdb;

		$sql     = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'press_sync_post_id' AND meta_value = $press_sync_post_id";
		$post_id = $wpdb->get_var( $sql );

		return $post_id;
	}

	public function insert_comments( $post_id, $post_args ) {
		// Post ID empty or post does not have any comments so bail early.
		if ( empty( $post_id ) || ( ! array_key_exists( 'comments', $post_args ) && empty( $post_args['comments'] ) ) ) {
			return false;
		}

		foreach ( $post_args['comments'] as $comment ) {
			$comment['comment_post_ID'] = $post_id;
			if ( isset( $comment['comment_post_ID'] ) ) {
				wp_insert_comment( $comment );
			}
		}
	}

	/**
	 * Checks to see if a non-synced duplicate exists
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_name
	 * @param string $post_type
	 *
	 * @return WP_Post
	 */
	public function get_non_synced_duplicate( $post_name, $post_type ) {

		global $wpdb;

		$sql          = "SELECT ID, post_type, post_modified FROM $wpdb->posts WHERE post_name = %s AND post_type = %s";
		$prepared_sql = $wpdb->prepare( $sql, $post_name, $post_type );

		$post = $wpdb->get_row( $prepared_sql, ARRAY_A );

		return ( $post ) ? $post : false;

	}

	/**
	 * Assign a WP Object the missing press sync ID
	 *
	 * @since 0.1.0
	 *
	 * @param array $object_args
	 *
	 * @return boolean
	 */
	public function add_press_sync_id( $object_id, $object_args ) {

		if ( ! isset( $object_args['post_type'] ) ) {
			return false;
		}

		$press_sync_post_id = isset( $object_args['meta_input']['press_sync_post_id'] ) ? $object_args['meta_input']['press_sync_post_id'] : '';
		$press_sync_source  = isset( $object_args['meta_input']['press_sync_source'] ) ? $object_args['meta_input']['press_sync_source'] : '';

		update_post_meta( $object_id, 'press_sync_post_id', $press_sync_post_id );
		update_post_meta( $object_id, 'press_sync_source', $press_sync_source );

	}

}
