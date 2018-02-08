<?php

namespace Press_Sync;

/**
 * The Press_Sync_API class.
 */
class API extends \WP_REST_Controller {

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
	 * Set true to only fix post terms.
	 *
	 * @var bool
	 * @since 0.7.1
	 */
	private $fix_terms = false;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Press_Sync $plugin Main plugin object.
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
			'methods' => 'GET',
			'callback' => array( $this, 'get_connection_status_via_api' ),
		) );

		register_rest_route( 'press-sync/v1', '/status/(?P<id>\d+)', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_post_sync_status_via_api' ),
		) );

		register_rest_route( 'press-sync/v1', '/sync', array(
			'methods' => array( 'GET', 'POST' ),
			'callback' => array( $this, 'sync_objects' ),
			'permission_callback' => array( $this, 'validate_sync_key' ),
		) );

		register_rest_route( 'press-sync/v1', '/progress/', array(
			'methods'             => array( 'GET' ),
			'callback'            => array( $this, 'get_sync_progress' ),
			'permission_callback' => array( $this, 'validate_sync_key' ),
			'args'                => array( 'post_type', 'press_sync_key', 'preserve_ids' ),
		) );

		/*
		@todo Complete the individual post syncing.
		register_rest_route( 'press-sync/v1', '/sync/(?P<id>\d+)', array(
			'methods' => array( 'GET', 'POST' ),
			'callback' => array( $this, 'sync_objects' ),
		) );
		 */

	}

	/**
	 * Gets the connection status via API request.
	 *
	 * @since 0.1.0
	 *
	 * @return JSON
	 */
	public function get_connection_status_via_api() {

		if ( ! $this->validate_sync_key() ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * Gets the post sync status via API request.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Request $request The WP_Request object.
	 *
	 * @return JSON
	 */
	public function get_post_sync_status_via_api( $request ) {

		$press_sync_post_id = $request->get_param( 'id' );

		if ( ! $press_sync_post_id ) {
			wp_send_json_error();
		}

		// Look for the post on the site.
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
	 * Validate the supplied press_sync_key by the sending site.
	 * Target site can't receive data without a valid press_sync_key.
	 *
	 * @since 0.1.0
	 */
	public function validate_sync_key() {

		$press_sync_key_from_remote = isset( $_REQUEST['press_sync_key'] ) ? $_REQUEST['press_sync_key'] : '';
		$press_sync_key = get_option( 'ps_key' );

		if ( ! $press_sync_key || ( $press_sync_key_from_remote !== $press_sync_key ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Sync all of the object received from the local site.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Request $request The WP_Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function sync_objects( $request ) {

		$objects_to_sync             = $request->get_param( 'objects_to_sync' );
		$objects                     = $request->get_param( 'objects' );
		$duplicate_action            = $request->get_param( 'duplicate_action' ) ? $request->get_param( 'duplicate_action' ) : 'skip';
		$force_update                = $request->get_param( 'force_update' );
		$this->skip_assets           = (bool) $request->get_param( 'skip_assets' );
		$this->preserve_ids          = (bool) $request->get_param( 'preserve_ids' );
		$this->fix_terms             = (bool) $request->get_param( 'fix_terms' );
		$this->content_threshold     = absint( $request->get_param( 'ps_content_threshold' ) );
		$this->ps_meta_repair_fields = $request->get_param( 'ps_meta_repair_fields' );

		if ( $this->ps_meta_repair_fields ) {
			$this->ps_meta_repair_fields = explode( ',', $this->ps_meta_repair_fields );
			$this->ps_meta_repair_fields = array_map( 'trim', $this->ps_meta_repair_fields );
		}

		if ( ! $objects_to_sync ) {
			wp_send_json_error( array(
				'debug' => __( 'Not sure which WP object you want to sync.', 'press-sync' ),
			) );
		}

		if ( ! $objects ) {
			wp_send_json_error( array(
				'debug' => __( 'No data available to sync.', 'press-sync' ),
			) );
		}

		// If the method is to pull then how do.
		if ( 'GET' === $request->get_method() ) {

			$where_clause = "ID IN ('" . implode( "''", $objects ) . "')";
			$taxonomies   = get_object_taxonomies( $objects_to_sync );
			return $this->plugin->get_objects_to_sync( $objects_to_sync, 1 ,$taxonomies ,$where_clause );

		}

		// Speed up bulk queries by pausing MySQL commits.
		global $wpdb;

		// $wpdb->query( 'SET AUTOCOMMIT = 0;' );
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );

		// Trust the HTML we're syncing is clean.
		remove_filter( 'content_save_pre', 'wp_filter_post_kses' );

		$responses = array();

		$objects_to_sync = in_array( $objects_to_sync, array( 'attachment', 'comment', 'user', 'option', 'taxonomy_term' ), true ) ? $objects_to_sync : 'post';

		foreach ( $objects as $object ) {
			$sync_method = "sync_{$objects_to_sync}";
			$responses[] = $this->$sync_method( $object, $duplicate_action, $force_update );
		}

		add_filter( 'content_save_pre', 'wp_filter_post_kses' );

		return $responses;

	}

	/**
	 * Syncs a post of any type.
	 *
	 * @since 0.1.0
	 *
	 * @param array   $post_args        The WP Posts to sync.
	 * @param string  $duplicate_action A flag to direct whether or not content is duplicated.
	 * @param boolean $force_update     A flag to overwrite existing content.
	 * @TODO add filter for incoming post data before save.
	 *
	 * @return array
	 */
	public function sync_post( $post_args, $duplicate_action, $force_update = false ) {

		if ( ! $post_args ) {
			return false;
		}

		// Check to see if the post exists.
		$local_post = $this->get_synced_post( $post_args );

		// Check to see a non-synced duplicate of the post exists.
		if ( 'sync' === $duplicate_action && ! $local_post ) {
			$local_post = $this->get_non_synced_duplicate( $post_args );
		}

		if ( $this->fix_terms ) {
			if ( ! $local_post ) {
				return array(
					'debug' => array(
						'message' => __( 'Could not find a local post to attach the terms to.', 'press-sync' ),
					),
				);
			}
			return $this->fix_term_relationships( $local_post['ID'], $post_args );
		}

		if ( ! empty( $this->ps_meta_repair_fields ) ) {
			if ( ! $local_post ) {
				return array(
					'debug' => array(
						'message' => __( 'Could not find a local post to repair meta for.', 'press-sync' ),
					),
				);
			}
			return $this->maybe_repair_meta_fields( $local_post, $post_args );
		}

		$post_args['ID'] = isset( $local_post['ID'] ) ? $local_post['ID'] : 0;

		// Replace embedded media.
		if ( isset( $post_args['embedded_media'] ) ) {

			foreach ( $post_args['embedded_media'] as $attachment_args ) {

				$attachment_id = $this->sync_attachment( $attachment_args );

				if ( abinst( $attachment_id ) ) {

					$sync_source = $post_args['meta_input']['press_sync_source'];
					$attachment_url = str_ireplace( $sync_source, home_url(), $attachment_args['attachment_url'] );

					$post_args['post_content'] = str_ireplace( $attachment_args['attachment_url'], $attachment_url, $post_args['post_content'] );
				}
			}
		}

		// Set the correct post author.
		$post_args['post_author'] = $this->get_press_sync_author_id( $post_args['post_author'] );

		// Check for post parent and update IDs accordingly.
		if ( ! $this->preserve_ids && isset( $post_args['post_parent'] ) && $post_parent_id = $post_args['post_parent'] ) {

			$post_parent_args['post_type'] = $post_args['post_type'];
			$post_parent_args['meta_input']['press_sync_post_id'] = $post_parent_id;

			$parent_post = $this->get_synced_post( $post_parent_args, false );

			$post_args['post_parent'] = ( $parent_post ) ? $parent_post['ID'] : 0;
		}

		// Keep the ID because we found a regular ol' duplicate.
		if ( $this->preserve_ids && ! $local_post && ! empty( $post_args['ID'] ) ) {
			$post_args['import_id'] = $post_args['ID'];
			unset( $post_args['ID'] );
		}

		// Determine which content is newer, local or remote.
		if ( ! $force_update && $local_post && ( strtotime( $local_post['post_modified'] ) >= strtotime( $post_args['post_modified'] ) ) ) {

			// If we're here, then we need to keep our local version.
			$response['remote_post_id'] = $post_args['meta_input']['press_sync_post_id'];
			$response['local_post_id']  = $local_post['ID'];
			$response['message']        = __( 'Local version is newer than remote version', 'press-sync' );

			// Assign a press sync ID.
			$this->add_press_sync_id( $local_post['ID'], $post_args );

			return array( 'debug' => $response );

		}

		// Add categories.
		if ( ! empty( $post_args['tax_input']['category'] ) ) {

			require_once( ABSPATH . '/wp-admin/includes/taxonomy.php' );

			foreach ( $post_args['tax_input']['category'] as $category ) {
				wp_insert_category( array(
					'cat_name'             => $category['name'],
					'category_description' => $category['description'],
					'category_nicename'    => $category['slug'],
				) );

				$post_args['post_category'][] = $category['slug'];
			}
		}

		// Insert/update the post.
		$local_post_id = wp_insert_post( $post_args, true );

		// Bail if the insert didn't work.
		if ( is_wp_error( $local_post_id ) ) {
			trigger_error( sprintf( 'Error inserting post: ', $local_post_id->get_error_message() ) );
			return array( 'debug' => $local_post_id );
		}

		// Attach featured image.
		$featured_result = $this->attach_featured_image( $local_post_id, $post_args );

		// Attach any comments.
		$comments = isset( $post_args['comments'] ) ? $post_args['comments'] : array();
		$this->attach_comments( $local_post_id, $comments );

		$this->attach_terms( $local_post_id, $post_args );

		// Run any secondary commands.
		do_action( 'press_sync_sync_post', $local_post_id, $post_args );

		return array(
			'debug' => array(
				'remote_post_id'  => $post_args['meta_input']['press_sync_post_id'],
				'local_post_id'   => $local_post_id,
				'message'         => __( 'The post has been synced with the remote site', 'press-sync' ),
				'featured_result' => $featured_result,
			),
		);
	}

	/**
	 * Syncs a WP attachment.
	 *
	 * @since 0.1.0
	 *
	 * @param array   $attachment_args  The WP Attachment to sync.
	 * @param string  $duplicate_action A flag to direct whether or not content is duplicated.
	 * @param boolean $force_update     A flag to overwrite existing content.
	 *
	 * @return int
	 */
	public function sync_attachment( $attachment_args, $duplicate_action = 'skip', $force_update = false ) {
		$attachment_id = false;
		$import_id     = false;

		// Attachment URL does not exist so bail early.
		if ( ! $this->skip_assets && ! array_key_exists( 'attachment_url', $attachment_args ) ) {
			return false;
		}
		// Check to see if the post exists.
		$local_attachment = $this->get_synced_post( $attachment_args );
		echo '<pre>', print_r($local_attachment, true); die;

		if ( isset( $attachment_args['ID'] ) ) {
			$import_id = $attachment_args['ID'];
		}

		try {
			if ( ! $this->skip_assets ) {
				$attachment = $this->maybe_upload_remote_attachment( $attachment_args );

				// ID will only be set if we find the attachment is already here.
				if ( ! isset( $attachment['ID'] ) ) {
					$filename = $attachment['filename'];
					unset( $attachment['filename'] );

					$attachment_id = wp_insert_attachment( $attachment, $filename, 0 );

					if ( is_wp_error( $attachment_id ) ) {
						throw new \Exception( 'There was an error creating the attachment: ' . $attachment_id->get_error_message() );
					}

					// Generate the metadata for the attachment, and update the database record.
					$attachment_data = wp_generate_attachment_metadata( $attachment_id, $filename );
					wp_update_attachment_metadata( $attachment_id, $attachment_data );
				}
			} else {
				// Look for a duplicate.
				if ( $duplicate = $this->get_non_synced_duplicate( $attachment_args ) ) {
					$attachment_id = $duplicate['ID'];
				} else {
					$attachment_args['post_author'] = $this->get_press_sync_author_id( $attachment_args['post_author'] );
					if ( isset( $attachment_args['ID'] ) ) {
						$attachment_args['import_id'] = $attachment_args['ID'];
						unset( $attachment_args['ID'] );
					}

					$attachment_id = wp_insert_post( $attachment_args );
				}

				$this->update_post_meta_array( $attachment_id, $attachment_args['meta_input' ] );
			}

			if ( $attachment_id && $import_id ) {
				update_post_meta( $attachment_id, 'press_sync_post_id', $import_id );
			}
		}
		catch( \Exception $e ) {
			// @TODO log it more!
			error_log( $e->getMessage() );
		}

		return $attachment_id;
	}

	/**
	 * Syncs a WP User.
	 *
	 * @since 0.1.0
	 *
	 * @param array   $user_args        The WP User to sync.
	 * @param string  $duplicate_action A flag to direct whether or not content is duplicated.
	 * @param boolean $force_update     A flag to overwrite existing content.
	 *
	 * @return array
	 */
	public function sync_user( $user_args, $duplicate_action, $force_update = false ) {

		$username = isset( $user_args['user_login'] ) ? $user_args['user_login'] : '';

		// Check to see if the user exists.
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

		// Update the meta.
		foreach ( $user_args['meta_input'] as $usermeta_key => $usermeta_value ) {
			if ( 0 === strpos( $usermeta_key, 'press_sync_' ) ) {
				$usermeta_key = $this->maybe_make_multisite_key( $usermeta_key );
			}

			update_user_meta( $user_id, $usermeta_key, $usermeta_value );
		}

		// Asign user role.
		$user->add_role( $user_args['role'] );

		// Prepare response.
		$response['user_id'] = $user_id;
		$response['blog_id'] = get_current_blog_id();

		return $response;

	}

	/**
	 * Syncs a WP Option.
	 *
	 * @since 0.2.0
	 *
	 * @param  array $option_args The WP Options to sync.
	 *
	 * @return array
	 */
	public function sync_option( $option_args ) {

		$option_name  = isset( $option_args['option_name'] ) ? $option_args['option_name'] : '';
		$option_value = isset( $option_args['option_value'] ) ? $option_args['option_value'] : '';

		if ( empty( $option_value ) || empty( $option_name ) ) {
			return false;
		}

		$response['option_id'] = update_option( $option_name, $option_value, $option_args['autoload'] );

		return $response;
	}

	/**
	 * Return the synced post.
	 *
	 * @since 0.1.0
	 *
	 * @param array $post_args The WP Post args to query.
	 * @since 0.4.5
	 * @param bool $respect_post_type Set false to look for a post regardless of post type.
	 *
	 * @return WP_Post
	 */
	public function get_synced_post( $post_args, $respect_post_type = true ) {

		global $wpdb;

		// Capture the press sync post ID.
		$press_sync_post_id = isset( $post_args['meta_input']['press_sync_post_id'] ) ? $post_args['meta_input']['press_sync_post_id'] : 0;

		$sql = "
			SELECT post_id AS ID, post_title, post_type, post_modified FROM $wpdb->postmeta AS meta
			LEFT JOIN $wpdb->posts AS posts ON posts.ID = meta.post_id
			WHERE meta.meta_key = 'press_sync_post_id' AND meta.meta_value = %d";

		$prepare_args = array( $press_sync_post_id );

		if  ( $respect_post_type ) {
			$sql           .= ' AND posts.post_type = %s ';
			$prepare_args[] = $post_args['post_type'];
		}

		$sql .= ' LIMIT 1';

		$prepared_sql = $wpdb->prepare( $sql, $prepare_args );
		$post         = $wpdb->get_row( $prepared_sql, ARRAY_A );

		return ( $post ) ? $post : false;

	}

	/**
	 * Check to see if the media already exists locally by url.
	 *
	 * @since 0.1.0
	 *
	 * @param string $media_url The WP Attachment url.
	 *
	 * @return integer
	 */
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

	/**
	 * Check to see if the comment already exists locally.
	 *
	 * @since 0.1.0
	 *
	 * @param array $comment_args The WP Comment.
	 *
	 * @return boolean
	 */
	public function comment_exists( $comment_args = array() ) {

		$press_sync_comment_id = isset( $comment_args['meta_input']['press_sync_comment_id'] ) ? $comment_args['meta_input']['press_sync_comment_id'] : 0;
		$press_sync_source     = isset( $comment_args['meta_input']['press_sync_source'] ) ? $comment_args['meta_input']['press_sync_source'] : 0;

		$query_args = array(
			'number'      => 1,
			'meta_query'  => array(
				array(
					'key'     => 'press_sync_comment_id',
					'value'   => $press_sync_comment_id,
					'compare' => '=',
				),
				array(
					'key'     => 'press_sync_source',
					'value'   => $press_sync_source,
					'compare' => '=',
				),
			),
		);

		$comment = get_comments( $query_args );

		if ( $comment ) {
			return (array) $comment[0];
		}

		return false;

	}

	/**
	 * Get the post by the original id.
	 *
	 * @since 0.1.0
	 *
	 * @param integer $press_sync_post_id The Post ID of the remote post.
	 *
	 * @return WP_post
	 */
	public function get_post_by_orig_id( $press_sync_post_id ) {

		global $wpdb;

		$sql = "SELECT ID, post_modified
			FROM $wpdb->posts AS posts
			LEFT JOIN $wpdb->postmeta AS meta ON meta.post_id = posts.ID
			WHERE meta.meta_key = 'press_sync_post_id' AND meta.meta_value = $press_sync_post_id";

		return $wpdb->get_row( $sql );
	}

	/**
	 * Returns the original author ID of the synced post.
	 *
	 * @since 0.1.0
	 *
	 * @param integer $user_id The WP User ID.
	 *
	 * @return integer $user_id
	 */
	public function get_press_sync_author_id( $user_id ) {
		if ( ! $user_id ) {
			/**
			 * Filter for when we don't have a post author ID.
			 *
			 * @since 0.8.0
			 *
			 * @param  int $user_id The ID to use when we don't get an author.
			 * @return int
			 */
			return apply_filters( 'press_sync_unknown_author', 1 );
		}

		global $wpdb;

		$usermeta_key = $this->maybe_make_multisite_key( 'press_sync_user_id' );
		$sql          = "SELECT user_id AS ID FROM {$wpdb->usermeta} WHERE meta_key = '{$usermeta_key}' AND meta_value = %d";
		$prepared_sql = $wpdb->prepare( $sql, $user_id );

		$press_sync_user_id = $wpdb->get_var( $prepared_sql );

		return ( $press_sync_user_id ) ? $press_sync_user_id : 1;

	}

	/**
	 * Attach a featured image to the specified post.
	 *
	 * @since 0.1.0
	 *
	 * @param integer $post_id   The WP Post ID.
	 * @param array   $post_args The WP Post args.
	 */
	public function attach_featured_image( $post_id, $post_args ) {

		// Post does not have a featured image so bail early.
		if ( empty( $post_args['featured_image'] ) ) {
			return false;
		}

		// Allow download_url() to use an external request to retrieve featured images.
		add_filter( 'http_request_host_is_external', array( $this, 'allow_sync_external_host' ), 10, 3 );

		// Download the attachment.
		$attachment   = $this->sync_attachment( $post_args['featured_image'] );
		$thumbnail_id = absint( $attachment ) ?: 0;
		$response     = update_post_meta( $post_id, '_thumbnail_id', $thumbnail_id );

		// Remove filter that allowed an external request to be made via download_url().
		remove_filter( 'http_request_host_is_external', array( $this, 'allow_sync_external_host' ) );

		return $response ? '' : "Error attaching thumbnail {$thumbnail_id} to post {$post_id}";
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

	/**
	 * Attach the comments to the specified post.
	 *
	 * @since 0.1.0
	 *
	 * @param integer $post_id  The WP Post ID.
	 * @param array   $comments The WP Comments.
	 */
	public function attach_comments( $post_id, $comments ) {

		if ( empty( $post_id ) || ! $comments ) {
			return;
		}

		foreach ( $comments as $comment_args ) {

			// Check to see if the comment already exists.
			if ( $comment = $this->comment_exists( $comment_args ) ) {
				continue;
			}

			// Set Comment Post ID to correct local Post ID.
			$comment_args['comment_post_ID'] = $post_id;

			// Get the comment author ID.
			$comment_args['user_id'] = $this->get_press_sync_author_id( $comment_args['post_author'] );

			$comment_id = wp_insert_comment( $comment_args );

			if ( ! is_wp_error( $comment_id ) ) {

				foreach ( $comment_args['meta_input'] as $meta_key => $meta_value ) {
					update_comment_meta( $comment_id, $meta_key, $meta_value );
				}
			}
		}
	}

	/**
	 * Recreate the P2P connections.
	 *
	 * @since 0.1.0
	 *
	 * @param integer $post_id   The WP Post ID.
	 * @param array   $post_args The WP Post args.
	 */
	public function add_p2p_connections( $post_id, $post_args ) {

		if ( ! class_exists( '\P2P_Autoload' ) || ! $post_args['p2p_connections'] ) {
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

	/**
	 * Get the current post ID by the press sync ID.
	 *
	 * @since 0.1.0
	 *
	 * @param integer $press_sync_post_id The Press Sync Post ID.
	 *
	 * @return integer $post_id
	 */
	public function get_post_id_by_press_sync_id( $press_sync_post_id ) {

		global $wpdb;

		$sql     = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'press_sync_post_id' AND meta_value = $press_sync_post_id";
		$post_id = $wpdb->get_var( $sql );

		return $post_id;
	}

	/**
	 * Insert the comments for the specified post.
	 *
	 * @since 0.1.0
	 *
	 * @param integer $post_id   The WP Post ID.
	 * @param array   $post_args The WP Post args.
	 */
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
	 * Checks to see if a non-synced duplicate exists.
	 *
	 * @since 0.1.0
	 *
	 * @since 0.6.0
	 * @param string $post_args The post arguments for the post being synced.
	 *
	 * @return WP_Post
	 */
	public function get_non_synced_duplicate( $post_args ) {
		$duplicate_post = false;

		// @TODO post name and content checks should be their own methods...later, in the Post sync class.

		// Post name check.
		if ( ! empty( $post_args['post_name'] ) ) {
			global $wpdb;

            $sql          = "SELECT ID, post_title, post_content, post_type, post_modified FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s";
            $prepared_sql = $wpdb->prepare( $sql, $post_args['post_name'], $post_args['post_type'] );

			// get_row will return "null" or void on failure; in the words of Elvis "Well now goodbye `false` pretender".
			$duplicate_post = $wpdb->get_row( $prepared_sql, ARRAY_A ) ?: false;
		}

		// Post content check.
		$content_threshold = $this->content_threshold;

		if ( $duplicate_post && false !== $content_threshold && 0 !== absint( $content_threshold ) ) {
			$content_threshold = absint( $content_threshold );

			// Calculate how similar the post contents are (is?).
			similar_text( $duplicate_post['post_content'], $post_args['post_content'], $similarity );

			if ( $similarity <= $content_threshold ) {
				$duplicate_post = false;
			}
		}

		return $duplicate_post;
	}

	/**
	 * Assign a WP Object the missing press sync ID
	 *
	 * @since 0.1.0
	 *
	 * @param integer $object_id   The object ID.
	 * @param array   $object_args The object args.
	 *
	 * @return boolean
	 */
	public function add_press_sync_id( $object_id, $object_args ) {

		if ( ! isset( $object_args['post_type'] ) ) {
			return false;
		}

		$press_sync_post_id = isset( $object_args['meta_input']['press_sync_post_id'] ) ? $object_args['meta_input']['press_sync_post_id'] : '';
		$press_sync_source = isset( $object_args['meta_input']['press_sync_source'] ) ? $object_args['meta_input']['press_sync_source'] : '';

		update_post_meta( $object_id, 'press_sync_post_id', $press_sync_post_id );
		update_post_meta( $object_id, 'press_sync_source', $press_sync_source );

	}

    /**
     * Returns the IDs of synced objects of the given post type.
     *
     * @since 0.6.0
     *
     * @param WP_REST_Request $request The REST request.
     */
    public function get_sync_progress( $request ) {
        try {
            $post_type = $request->get_param( 'post_type' );

            if ( ! post_type_exists( $post_type ) ) {
                $post_type = 'post';
            }

            if ( (bool) $request->get_param( 'preserve_ids' ) ) {
                $sql = <<<SQL
SELECT DISTINCT
    ID
FROM
    {$GLOBALS['wpdb']->posts} p
WHERE
    p.post_status NOT IN ( 'auto-draft', 'trash' )
AND
    p.post_type = %s
SQL;
            } else {

            $sql = <<<SQL
SELECT DISTINCT
	pm.meta_value
FROM
	{$GLOBALS['wpdb']->postmeta} pm
WHERE
	pm.meta_key = 'press_sync_post_id'
AND
    pm.post_id IN(
        SELECT ID FROM
            {$GLOBALS['wpdb']->posts} p
        WHERE
            p.post_status NOT IN( 'auto-draft', 'trash' )
        AND
            p.post_type = %s
    )
SQL;
            }

			$query  = $GLOBALS['wpdb']->prepare( $sql, $post_type );
			$synced = $GLOBALS['wpdb']->get_col( $query );

			wp_send_json_success( array(
				'synced' => $synced,
			) );
		} catch ( \Exception $e ) {
			wp_send_json_error();
		}
	}

	private function maybe_upload_remote_attachment( $attachment_args ) {
		$attachment_url       = isset( $attachment_args['details']['url'] ) ? $attachment_args['details']['url'] : $attachment_args['attachment_url'];
		$attachment_post_date = isset( $attachment_args['details']['post_date'] ) ? $attachment_args['details']['post_date'] : $attachment_args['post_date'];
		$attachment_title     = isset( $attachment_args['post_title'] ) ? $attachment_args['post_title'] : '';
		$attachment_name      = isset( $attachment_args['post_name'] ) ? $attachment_args['post_name'] : '';

		// Check to see if the file already exists.
		if ( $attachment_id = $this->plugin->file_exists( $attachment_url, $attachment_post_date ) ) {
			return array( 'ID' => $attachment_id );
		}

		$attachment_metadata = $this->get_attachment_metadata_from_request( $attachment_args );
		$temp_file           = false;

		require_once( ABSPATH . '/wp-admin/includes/image.php' );
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		require_once( ABSPATH . '/wp-admin/includes/media.php' );

		// Download the url.
		$temp_file = download_url( $attachment_url, 5000 );

		// Move the file to the proper uploads folder.
		if ( is_wp_error( $temp_file ) ) {
			// @TODO log it!
			throw new \Exception( 'Something went wrong when downloading the attachment temp file: ' . $temp_file->get_error_message() );
		}

		// Array based on $_FILE as seen in PHP file uploads.
		$file = array(
			'name'     => basename( $attachment_url ),
			'type'     => 'image/png',
			'tmp_name' => $temp_file,
			'error'    => 0,
			'size'     => filesize( $temp_file ),
		);

		$overrides = array( 'test_form' => false, 'test_size' => true, 'action' => 'custom' );

		if ( false !== $temp_file ) {
			// Move the temporary file into the uploads directory.
			$results = wp_handle_upload( $file, $overrides, $attachment_post_date );

			// Delete the temporary file.
			@unlink( $temp_file );
		}

		// Upload the file into WP Media Library.
		if ( $results['file'] ) {
			$filename  = $results['file']; // Full path to the file.
			$local_url = $results['url'];  // URL to the file in the uploads dir.
			$type      = $results['type']; // MIME type of the file.
		}

		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => $local_url,
			'post_mime_type' => $type,
			'post_title'     => $attachment_title ?: preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'filename'       => $filename,
		);

		if ( strlen( $attachment_name ) ) {
			$attachment['post_name'] = $attachment_name;
		}

		return $attachment;
	}

	/**
	 * Bulk updates meta data from an array.
	 *
	 * @since 0.6.0
	 *
	 * @param int   $post_id   The ID of the post you want to update meta on.
	 * @param array $meta_data An array with keys and values also contained in an array ala get_post_meta( $ID ).
	 */
	private function update_post_meta_array( $post_id, $meta_data = array() ) {
		foreach ( $meta_data as $field => $values ) {
			if ( is_array( $values ) ) {
				update_post_meta( $post_id, $field, current( $values ) );
				continue;

				// Handle $values as an array.
				if ( 1 === count( $values ) ) {
					update_post_meta( $post_id, $field, maybe_unserialize( current( $values ) ) );
				} else {
					// Also handle multiple keys by removing and re-adding.
					delete_post_meta( $post_id, $field );
					foreach ( $values as $value ) {
						add_post_meta( $post_id, $field, maybe_unserialize( $value ) );
					}
				}
			} else {
				update_post_meta( $post_id, $field, maybe_unserialize( $values ) );
			}
		}
	}

	/**
	 * Sync a taxonomy term to the remote site.
	 *
	 * @since 0.7.0
	 * @param array $object_args The term taxonomy args.
	 * @return string
	 */
	public function sync_taxonomy_term( $object_args ) {
		try {
			if ( ! taxonomy_exists( $object_args['taxonomy'] ) ) {
				throw new \Exception( sprintf( 'The taxonomy %s does not exist, cannot insert terms.', $object_args['taxonomy'] ) );
			}

			$term_ids = term_exists( $object_args['name'], $object_args['taxonomy'] );

			if ( ! is_array( $term_ids ) ) {
				$term_ids = wp_insert_term( $object_args['name'], $object_args['taxonomy'], array(
					'slug'        => $object_args['slug'],
					'description' => $object_args['description'],
				) );

				if ( is_wp_error( $term_ids ) ) {
					throw new \Exception( sprintf(
						'There was an issue inserting term "%s" into taxonomy "%s": %s',
						$object_args['name'],
						$object_args['taxonomy'],
						$term_ids->get_error_message()
					) );
				}
			}

			if ( ! empty( $object_args['meta_input'] ) ) {
				$this->maybe_update_term_meta( $term_ids['term_id'], $object_args['meta_input'] );
			}
		} catch ( \Exception $e ) {
			trigger_error( $e->getMessage() );
			return array(
				'debug' => $e->getMessage(),
			);
		}

		return array(
			'debug' => array(
				'message' => __( 'The taxonomy term was succesfully added.', 'press-sync' ),
			),
		);
	}

	/**
	 * Attach a post's terms.
	 *
	 * @since 0.7.1
	 * @param int   $post_id   The Id of the post to attach terms to.
	 * @param array $post_args The post arguments.
	 */
	private function attach_terms( $post_id, $post_args ) {
		// Set taxonomies for custom post type.
		if ( isset( $post_args['tax_input'] ) ) {
			foreach ( $post_args['tax_input'] as $taxonomy => $terms ) {
				$this->maybe_create_new_terms( $taxonomy, $terms );
				wp_set_object_terms( $post_id, wp_list_pluck( $terms, 'slug' ), $taxonomy, false );
			}
		}
	}

	/**
	 * Fix a post's relationships.
	 *
	 * @since 0.7.1
	 * @param  int   $post_id   The ID of the post to fix relationships for.
	 * @param  array $post_args The arguments for the incoming post.
	 * @return array
	 */
	private function fix_term_relationships( $post_id, $post_args ) {
		$this->attach_terms( $post_id, $post_args );
		return array(
			'debug' => array(
				'message' => __( 'Fixed term relationships.', 'press-sync' ),
			),
		);
	}

	/**
	 * Make a key multisite-specific by injecting the current blog ID.
	 *
	 * @since 0.8.0
	 * @param  string  $key The meta key to make blog-specific.
	 * @return string.
	 */
	private function maybe_make_multisite_key( $key ) {
		// Only on multisite.
		if ( ! is_multisite() ) {
			return $key;
		}

		static $multisite_key_regex = '/^press_sync_\d+_/';
		preg_match( $multisite_key_regex, $key, $matches );

		// It's already a multisite key, don't doubledown.
		if ( ! empty( $matches ) ) {
			return $key;
		}

		$blog_id = get_current_blog_id();
		return strtr( $key, array( 'press_sync_' => "press_sync_{$blog_id}_" ) );
	}

	/**
	 * Maybe create terms that don't exist.
	 *
	 * While wp_set_object_terms does create terms that don't exist, we can't also insert
	 * meta data such as slug and description, or termmeta.
	 *
	 * @since 0.8.0
	 *
	 * @param string $taxonomy The taxonomy to insert the term to.
	 * @param array  $terms    Array of term data.
	 */
	private function maybe_create_new_terms( $taxonomy, $terms ) {
		foreach ( $terms as $term ) {
			if ( term_exists( $term['slug'], $taxonomy ) ) {
				continue;
			}

			$term_id = $this->create_term( $term, $taxonomy );

			if ( ! empty( $term['meta_input'] ) ) {
				$this->maybe_update_term_meta( $term_id, $term['meta_input'] );
			}
		}
	}

	/**
	 * Create a term.
	 *
	 * @since 0.8.0
	 *
	 * @param array  $term     The term info to insert.
	 * @param string $taxonomy The taxonomy to attach the term to.
	 */
	private function create_term( $term, $taxonomy ) {
		$term_result = wp_insert_term( $term['name'], $taxonomy, array(
			'slug'        => $term['slug'],
			'description' => $term['description'],
		) );

		if ( is_wp_error( $term_result ) ) {
			trigger_error( sprintf( __( 'Could not insert new term "%s": %s.', 'press-sync' ), $term['name'], $term_result->get_error_message() ) );
		}

		return $term_result['term_id'];
	}

	/**
	 * Update term meta for a term.
	 *
	 * @since 0.8.0
	 * @param int   $term_id   The ID of the term to add meta to.
	 * @param array $term_meta The meta for the term.
	 */
	private function maybe_update_term_meta( $term_id, $term_meta ) {
		foreach ( $term_meta as $meta_key => $meta_value ) {
			$meta_value  = is_array( $meta_value ) ? current( $meta_value ) : $meta_value;
			$meta_result = update_term_meta( $term_id, $meta_key, $meta_value );

			if ( is_wp_error( $meta_result ) ) {
				trigger_error( sprintf( __( 'Error updating term meta, ambiguous term ID: %s', 'press-sync' ), $meta_result->get_error_message() ) );
			}

			if ( false === $meta_result ) {
				trigger_error( sprintf( __( 'Could not add term meta for term %d.', 'press-sync' ), $term_id ) );
			}
		}
	}

	/**
	 * Function for handling meta repair task.
	 *
	 * @since NEXT
	 * @param  array $local_post An array of post data from the local post.
	 * @param  array $post_args  Array of incoming post arguments.
	 * @return array
	 */
	private function maybe_repair_meta_fields( $local_post, $post_args ) {
		$meta_result = array();

		foreach ( $post_args['meta_input'] as $field => $value ) {
			if ( false === strpos( $field, 'press_sync_' ) && ! in_array( $field, $this->ps_meta_repair_fields ) ) {
				continue;
			}

			$result = update_post_meta( $local_post['ID'], $field, $value );
			$meta_result[] = array(
				'field'         => $field,
				'value'         => $value,
				'update_result' => var_export( $result, 1 ),
			);
		}

		return array(
			'debug' => array(
				'remote_post_id'  => $post_args['meta_input']['press_sync_post_id'],
				'local_post_id'   => $local_post['ID'],
				'message'         => __( 'The post meta has been synced with the remote site', 'press-sync' ),
				'update_result'   => $meta_result,
			),
		);
	}
}
