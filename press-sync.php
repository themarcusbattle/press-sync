<?php
/*
Plugin Name: Press Sync
Description: The easiest way to synchronize posts, media and users between two WordPress sites
Version: 0.1.0
License: GPL
Author: Marcus Battle
Author URI: http://marcusbattle.com
Text Domain: press-sync
*/

class Press_Sync {

	/**
	 * Plugin class
	 *
	 * @var   Press_Sync
	 * @since 0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * Domain of local server
	 *
	 * @var string
	 * @since  0.1.0
	 */
	public $local_domain = null;

	/**
	 * Domain of remote server
	 *
	 * @var string
	 * @since  0.1.0
	 */
	public $remote_domain = null;

	/**
	 * Create an instance of the Press Sync object
	 *
	 * @since 0.1.0
	 */
	static function init() {

		if ( self::$single_instance === null ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;

	}

	/**
	 * Add the plugin hooks
	 *
	 * @since 0.1.0
	 */
	public function hooks() {

		// Include other files
		$this->includes();

		// Initialize plugin classes
		$this->plugin_classes();

	}

	/**
	 * Files to be loaded when the base plugin class loads.
	 *
	 * @since 0.1.0
	 */
	public function includes() {

		// Load CMB2 support for fields
		if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/third-party/CMB2/init.php' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . 'includes/third-party/CMB2/init.php' );
		}

	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  0.1.0
	 */
	public function plugin_classes() {
		$this->dashboard 	= new Press_Sync_Dashboard( $this );
		$this->api 			= new Press_Sync_API( $this );
	}

	/**
	 * Includes a page for display in the WP Admin
	 *
	 * @since 0.1.0
	 *
	 * @param string $filename
	 * @return boolean
	 */
	public function include_page( $filename ) {

		$filename_parts = explode( '/', $filename );
		$controller 	= isset( $filename_parts[0] ) ? $filename_parts[0] : '';
		$file			= isset( $filename_parts[1] ) ? $filename_parts[1] : $controller;

		$filename = plugin_dir_path( __FILE__ ) . "views/{$controller}/html-" . $file . '.php';

		if ( ! file_exists( $filename ) ) {
			return false;
		}

		ob_start();
		include( $filename );
		$html = ob_get_contents();
		ob_end_clean();

		echo $html;

		return true;

	}

	/**
	 * Include a file from the includes directory
	 *
	 * @since  0.1.0
	 *
	 * @param  	string $filename Name of the file to be included.
	 * @return 	boolean	Result of include call.
	 */
	public static function include_file( $filename ) {

		$file = self::dir( $filename . '.php' );

		if ( file_exists( $file ) ) {
			return include_once( $file );
		}

		return false;
	}

	/**
	 * This plugin's directory.
	 *
	 * @since  0.1.0
	 *
	 * @param  	string	$path	(optional) appended path.
	 * @return	string	Directory and path.
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * Returns the specified press sync option
	 *
	 * @since 0.1.0
	 *
	 * @param string $option
	 * @return string
	 */
	public function press_sync_option( $option ) {
		$press_sync_options = get_option( 'press-sync-options' );
		return isset( $press_sync_options[ $option ] ) ? $press_sync_options[ $option ] : '';
	}

	/**
	 * Initialize the connection variables
	 *
	 * @since 0.1.0
	 */
	public function init_connection() {
		$this->local_domain = untrailingslashit( home_url() );
		$this->remote_domain = untrailingslashit( cmb2_get_option( 'press-sync-options', 'connected_server' ) );
	}

	/**
	 * Checks the connection to the remote server and returns the connection status
	 *
	 * @since 0.1.0
	 *
	 * @param 	string 	$url
	 * @return 	boolean
	 */
	public function check_connection( $url = '' ) {

		$url = ( $url ) ? $url : cmb2_get_option( 'press-sync-options', 'connected_server' );
		$press_sync_key = cmb2_get_option( 'press-sync-options', 'remote_press_sync_key' );

		$remote_get_args = array(
			'timeout'	=> 30
		);

		$url .= "wp-json/press-sync/v1/status?press_sync_key=$press_sync_key";

		$response = wp_remote_get( $url, $remote_get_args );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 == $response_code ) {
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

			return isset( $response_body['success'] ) ? $response_body['success'] : false;
		}

		return false;

	}

	/**
	 * Return the objects to be synced to the remote server
	 *
	 * @since 0.1.0
	 *
	 * @param string $objects_to_sync
	 * @param integer $paged
	 * @param array $taxonomies
	 * @return array $objects
	 */
	public function get_objects_to_sync( $objects_to_sync, $paged = 1, $taxonomies, $where_clause = '' ) {

		if ( 'user' == $objects_to_sync ) {
			$objects = $this->get_users_to_sync( $paged );
		} else {
			$objects = $this->get_posts_to_sync( $objects_to_sync, $paged, $taxonomies, $where_clause );
		}

		return $objects;

	}

	/**
	 * Return the posts to sync
	 *
	 * @since 0.1.0
	 *
	 * @param string $objects_to_sync
	 * @param integer $paged
	 * @param array $taxonomies
	 * @return array posts
	 */
	public function get_posts_to_sync( $objects_to_sync, $paged = 1, $taxonomies, $where_clause = '' ) {

		global $wpdb;

		$offset = ( $paged > 1 ) ? ( $paged - 1 ) * 5 : 0;

		$where_clause 	= ( $where_clause ) ? ' AND ' . $where_clause : '';
		$sql 			= "SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_status NOT IN ('auto-draft','trash') $where_clause ORDER BY post_date DESC LIMIT 5 OFFSET %d";
		$prepared_sql 	= $wpdb->prepare( $sql, $objects_to_sync, $offset );

		// Get the results
		$results 	= $wpdb->get_results( $prepared_sql, ARRAY_A );
		$posts 		= array();

		if ( $results ) {

			foreach ( $results as $object ) {

				$object['tax_input'] 							= $this->get_relationships( $object['ID'], $taxonomies );
				$object['meta_input'] 							= get_post_meta( $object['ID'] );
				$object['meta_input']['press_sync_post_id'] 	= $object['ID'];
				$object['meta_input']['press_sync_source']		= home_url();
				$object['meta_input']['press_sync_gmt_offset'] 	= get_option('gmt_offset');

				array_push( $posts, $object );

			}

		}

		return $posts;

	}

	/**
	 * Returns the users to sync
	 *
	 * @since 0.1.0
	 *
	 * @param int $paged
	 * @return WP_Users
	 */
	public function get_users_to_sync( $paged = 1 ) {

		$query_args = array(
			'number'	=> 5,
			'offset'	=> ( $paged > 1 ) ? ( $paged - 1 ) * 5 : 0,
			'paged'		=> $paged
		);

		$query = new WP_User_Query( $query_args );

		$results 	= $query->get_results();
		$users 		= array();

		if ( $results ) {

			foreach ( $results as $user ) {

				// Get user ro;e
				$role = $user->roles[0];

				// Get user data
				$user = (array) $user->data;
				$user_meta = get_user_meta( $user['ID'] );

				foreach ( $user_meta as $key => $value ) {
					$user['meta_input'][ $key ] = $value[0];
				}

				$user['meta_input']['press_sync_user_id']	= $user['ID'];
				$user['meta_input']['press_sync_source']	= home_url();
				$user['role'] = $role;

				unset( $user['ID'] );

				array_push( $users, $user );

			}

		}

		return $users;

	}

	/**
	 * Return the taxonomies (categories, tags and custom taxonomies) associated with the WP Object provided
	 *
	 * @since 0.1.0
	 *
	 * @param integer $object_id
	 * @param WP_Taxonomy $taxonomies
	 * @return array $taxonomies
	 */
	public function get_relationships( $object_id, $taxonomies ) {

		foreach ( $taxonomies as $key => $taxonomy ) {
			$taxonomies[ $taxonomy ] = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'names' ) );
			unset( $taxonomies[ $key ] );
		}

		return $taxonomies;

	}

	/**
	 * Get the total number of WP objects to sync to remote server
	 *
	 * @since 0.1.0
	 *
	 * @param string $objects_to_sync
	 * @return integer $total_objects
	 */
	public function count_objects_to_sync( $objects_to_sync ) {

		if ( 'user' == $objects_to_sync) {
			return $this->count_users_to_sync();
		}

		global $wpdb;

		$sql = "SELECT count(*) FROM $wpdb->posts WHERE post_type = %s AND post_status NOT IN ('auto-draft','trash')";
		$prepared_sql = $wpdb->prepare( $sql, $objects_to_sync );

		$total_objects = $wpdb->get_var( $prepared_sql );

		return $total_objects;

	}

	/**
	 * Get the total number of users to sync to remote server
	 *
	 * @since 0.1.0
	 *
	 * @return integer $total_users
	 */
	public function count_users_to_sync() {
		$result = count_users();
		return $result['total_users'];
	}

	/**
	 * Preare the WP Post args to sync to the remote server
	 *
	 * @since 0.1.0
	 *
	 * @param array $object_args
	 * @return array $object_args
	 */
	public function prepare_post_args_to_sync( $object_args ) {

		foreach ( $object_args['meta_input'] as $meta_key => $meta_value ) {
 			$object_args['meta_input'][ $meta_key ] = is_array( $meta_value ) ? $meta_value[0] : $meta_value;
		}

		$object_args = apply_filters( 'press_sync_prepare_post_args_to_sync', $object_args );

		$object_args['embedded_media'] = $this->get_embedded_media( $object_args['post_content'] );

		// Send Featured image information along to be imported
		$object_args['featured_image'] = $this->get_featured_image( $object_args['ID'] );

		// Get the comments for the post
		$ignore_comments = cmb2_get_option( 'press-sync-options', 'ignore_comments' );

		if ( $object_args['comment_count'] && ! $ignore_comments ) {
			$object_args['comments'] = $this->get_comments( $object_args['ID'] );
		}

		// Look for any P2P connections
		if ( class_exists('P2P_Autoload') ) {
			$object_args['p2p_connections'] = $this->get_p2p_connections( $object_args['ID'] );
		}

		unset( $object_args['ID'] );

		return $object_args;

	}

	/**
	 * Replace the local domain links in post_content with the remote server domain
	 *
	 * @since 0.1.0
	 *
	 * @param array $object_args
	 * @return array $object_args
	 */
	public function update_links( $object_args ) {

		$post_content = isset( $object_args['post_content'] ) ? $object_args['post_content'] : '';

		if ( $post_content ) {

			$post_content = str_ireplace( $this->local_domain, $this->remote_domain, $post_content );

			$object_args['post_content'] = $post_content;

		}

		return $object_args;

	}

	/**
	 * Get the featured image for a WP Post
	 *
	 * @since 0.1.0
	 *
	 * @param integer $post_id
	 * @return WP_Attachment $media
	 */
	public function get_featured_image( $post_id ) {

		$thumbnail_id 				= get_post_meta( $post_id, '_thumbnail_id', true );

		if ( ! $thumbnail_id ) {
			return false;
		}

		$media 						= get_post( $thumbnail_id, ARRAY_A );
		$media['attachment_url'] 	= home_url( 'wp-content/uploads/' . get_post_meta( $thumbnail_id, '_wp_attached_file', true ) );

		return $media;

	}

	/**
	 * Get all of the comments for a post
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id
	 * @return array
	 */
	public function get_comments( $post_id ) {

		$query_args = array(
			'post_id'	=> $post_id,
		);

		$comments =  get_comments( $query_args );

		if ( ! $comments ) {
			return false;
		}

		foreach ( $comments as $key => $comment_args ) {
			$comment_args = (array) $comment_args;
			$comments[ $key ] = $this->prepare_comment_args_to_sync( $comment_args );
		}

		return $comments;
	}

	/**
	 * Return the P2P connections for a single post
	 *
	 * @since 0.1.0
	 *
	 * @param integer $post_id
	 * @return array $p2p_connections
	 */
	public function get_p2p_connections( $post_id ) {

		global $wpdb;

		$sql = "SELECT p2p_from, p2p_to, p2p_type FROM {$wpdb->prefix}p2p WHERE p2p_from = $post_id OR p2p_to = $post_id";
		$p2p_connections = $wpdb->get_results( $sql, ARRAY_A );

		return $p2p_connections;

	}

	/**
	 * Filter user before synced with the remote server
	 *
	 * @since 0.1.0
	 *
	 * @param array $user_args
	 * @return array $user_args
	 */
	public function prepare_user_args_to_sync( $user_args ) {

		// Remove the user password
		$user_args['user_pass'] = NULL;

		return $user_args;
	}

	/**
	 * Filter attachment before synced with the remote server
	 *
	 * @since 0.1.0
	 *
	 * @param array $attachment_args
	 * @return array $attachment_args
	 */
	public function prepare_attachment_args_to_sync( $object_args ) {

		$attachment_url = $object_args['guid'];

		$args = array(
			'post_date' => $object_args['post_date'],
			'post_title'	=> $object_args['post_title'],
			'post_name'	=> $object_args['post_name'],
			'attachment_url'	=> $attachment_url,
		);

		return $args;

	}

	/**
	 * Filter comment before synced with the remote server
	 *
	 * @since 0.1.0
	 *
	 * @param array $comment_args
	 * @return array $comment_args
	 */
	public function prepare_comment_args_to_sync( $comment_args ) {

		$args = array();

		$args['comment_post_ID'] 						= $comment_args['comment_post_ID'];
		$args['comment_author'] 						= $comment_args['comment_author'];
		$args['comment_author_email'] 					= $comment_args['comment_author_email'];
		$args['comment_author_url'] 					= $comment_args['comment_author_url'];
		$args['comment_author_IP'] 						= $comment_args['comment_author_IP'];
		$args['comment_date'] 							= $comment_args['comment_date'];
		$args['comment_date_gmt'] 						= $comment_args['comment_date_gmt'];
		$args['comment_content'] 						= $comment_args['comment_content'];
		$args['comment_karma'] 							= $comment_args['comment_karma'];
		$args['comment_approved'] 						= $comment_args['comment_approved'];
		$args['comment_agent'] 							= $comment_args['comment_agent'];
		$args['comment_type'] 							= $comment_args['comment_type'];
		$args['comment_parent'] 						= $comment_args['comment_parent'];
		$args['user_id'] 								= $comment_args['user_id'];
		$args['meta_input']['press_sync_comment_id'] 	= $comment_args['comment_ID'];
		$args['meta_input']['press_sync_post_id'] 		= $comment_args['comment_post_ID'];
		$args['meta_input']['press_sync_source']		= home_url();

		return $args;
	}

	/**
	 * POST data to the remote server
	 *
	 * @since 0.1.0
	 *
	 * @param string $url
	 * @param array $args
	 * @return JSON $response_body
	 */
	public function send_data_to_remote_server( $url, $args ) {

		$args = array(
			'timeout'	=> 30,
			'body'	=> $args,
		);

		$response 		= wp_remote_post( $url, $args );
		$response_body	= wp_remote_retrieve_body( $response );

		return $response_body;
	}

	/**
	 * GET data from the remote server
	 *
	 * @param  string 	$url
	 * @param  array  	$args
	 * @return JSON 	$response_body
	 */
	public function get_data_from_remote_server( $url = '', $args = array() ) {

		$args = array(
			'timeout'	=> 30,
			'body'	=> $args,
		);

		$response 		= wp_remote_get( $url, $args );
		$response_body	= wp_remote_retrieve_body( $response );

		return $response_body;

	}

	/**
	 * Find any embedded images in the post content
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_content
	 */
	public function get_embedded_media( $post_content = '' ) {

		$embedded_media = array();

		if ( ! $post_content ) {
			return $embedded_media;
		}

		preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $post_content, $embedded_media );
		$embedded_media = array_filter( $embedded_media );

		return isset( $embedded_media[1] ) ? $embedded_media[1] : array();

	}

}

add_action( 'plugins_loaded', array( Press_Sync::init(), 'hooks' ), 10, 1 );

/**
 * Autoloads files with classes when needed.
 *
 * @since  0.1.0
 * @param  string $class_name Name of the class being requested.
 */
function press_sync_autoload_classes( $class_name ) {

	// If our class doesn't have our prefix, don't load it.
	if ( 0 !== strpos( $class_name, 'Press_Sync_' ) ) {
		return;
	}

	// Set up our filename.
	$filename = strtolower( str_replace( '_', '-', substr( $class_name, strlen( 'Press_Sync_' ) ) ) );

	// Include our file.
	Press_Sync::include_file( 'includes/class-' . $filename );
}

spl_autoload_register( 'press_sync_autoload_classes' );
