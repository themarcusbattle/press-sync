<?php
/**
 * Press Sync Plugin
 *
 * @package PressSync
 */

namespace Press_Sync;

/**
 * The Press_Sync class.
 *
 * @since 0.1.0
 */
class Press_Sync {
	/**
	 * Default page size for sync batches.
	 *
	 * @since NEXT
	 * @var int
	 */
	const PAGE_SIZE = 5;

	/**
	 * Plugin class
	 *
	 * @var   Press_Sync
	 * @since 0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * Domain of local site
	 *
	 * @var string
	 * @since  0.1.0
	 */
	public $local_domain = null;

	/**
	 * Domain of remote site
	 *
	 * @var string
	 * @since  0.1.0
	 */
	public $remote_domain = null;

	/**
	 * Sync posts modified after this date.
	 *
	 * @var string
	 * @since 0.8.0
	 */
	private $delta_date = false;

	/**
	 * Initialize the class instance.
	 *
	 * @since 0.4.5
	 */
	public function __construct() {
		// Initialize plugin classes.
		$this->plugin_classes();
		$this->delta_date = date( 'Y-m-d 00:00:00', strtotime( get_option( 'ps_delta_date' ) ) ) ?: false;
	}

	/**
	 * Create an instance of the Press Sync object
	 *
	 * @since 0.1.0
	 */
	static function init() {

		if ( null === self::$single_instance ) {
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
		add_filter( 'http_request_host_is_external', array( $this, 'approve_localhost_urls' ), 10, 3 );
		add_filter( 'press_sync_order_to_sync_all', array( $this, 'order_to_sync_all' ), 10, 1 );
		add_filter( 'press_sync_after_prepare_post_args_to_sync', array( $this, 'maybe_remove_post_id' ) );

		add_filter( 'press_sync_get_taxonomy_term_where', array( $this, 'maybe_get_terms_for_post' ) );
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  0.1.0
	 */
	public function plugin_classes() {
		$this->dashboard = new Dashboard( $this );
		$this->api       = new API( $this );
		$this->cli       = new CLI( $this );
		$this->progress  = new Progress( $this );
	}

	/**
	 * Includes a page for display in the WP Admin.
	 *
	 * @since 0.1.0
	 *
	 * @param string $filename Tge filename of the page to display.
	 *
	 * @return boolean
	 */
	public function include_page( $filename ) {

		$filename_parts = explode( '/', $filename );
		$controller     = isset( $filename_parts[0] ) ? $filename_parts[0] : '';
		$file           = isset( $filename_parts[1] ) ? $filename_parts[1] : $controller;

		$filename = plugin_dir_path( __FILE__ ) . "../views/{$controller}/html-" . $file . '.php';

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
	 * @param string $filename Name of the file to be included.
	 *
	 * @return boolean Result of include call.
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
	 * @param string $path (optional) appended path.
	 *
	 * @return string Directory and path.
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * Initialize the connection variables
	 *
	 * @since 0.1.0
	 *
	 * @param string $remote_domain The remote site that we'll be pulling/pushing content from/to.
	 */
	public function init_connection( $remote_domain = '' ) {
		$this->local_domain  = untrailingslashit( home_url() );
		$this->remote_domain = ( $remote_domain ) ? trailingslashit( $remote_domain ) : untrailingslashit( get_option( 'ps_remote_domain' ) );
	}

	/**
	 * Checks the connection to the remote site and returns the connection status.
	 *
	 * @since 0.1.0
	 *
	 * @param string $url The url of the remote site.
	 *
	 * @return boolean
	 */
	public function check_connection( $url = '' ) {

		$url = $this->get_remote_url( $url );

		$remote_get_args = array(
			'timeout' => 30,
		);

		$response      = wp_remote_get( $url, $remote_get_args );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $response_code ) {
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

			return isset( $response_body['success'] ) ? $response_body['success'] : false;
		}

		return false;

	}

	/**
	 * Return the objects to be synced to the remote site.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $objects_to_sync The WP object to sync.
	 * @param integer $next_page       The pagination number for the next request.
	 * @param array   $taxonomies      The related taxonomies to the WP object.
	 * @param string  $where_clause    An SQL Where clause to modify the query to retrieve objects.
	 *
	 * @return array $objects          The queried objects to sync.
	 */
	public function get_objects_to_sync( $objects_to_sync, $next_page = 1, $taxonomies = [], $where_clause = '' ) {

		switch ( $objects_to_sync ) {

		case 'user':
			$objects = $this->get_users_to_sync( $next_page );
			break;

		case 'option':
			$objects = $this->get_options_to_sync( $next_page );
			break;

		case 'taxonomy_term':
			$objects = $this->get_taxonomy_term_to_sync( $next_page );
			break;

		default:
			$objects = $this->get_posts_to_sync( $objects_to_sync, $next_page, $taxonomies, $where_clause );
			break;
		}

		return $objects;

	}

	/**
	 * Return the posts to sync.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $objects_to_sync The objects to sync.
	 * @param integer $next_page       The next page of results.
	 * @param array   $taxonomies      The related taxonomies for the objects.
	 * @param string  $where_clause    An SQL Where clause to modify the query to retrieve posts.
	 *
	 * @return array  $posts           The posts to return.
	 */
	public function get_posts_to_sync( $objects_to_sync, $next_page = 1, $taxonomies = [], $where_clause = '' ) {

		global $wpdb;

		$offset       = ( $next_page > 1 ) ? ( $next_page - 1 ) * $this->settings['ps_page_size'] : 0;
		$where_clause = ( $where_clause ) ? ' AND ' . $where_clause : '';

		// @TODO let's filter the where clause in general.
		$where_clause .= $this->get_synced_object_clause( $objects_to_sync );
		$where_clause .= $this->get_posts_delta( $objects_to_sync );

		if ( $testing_post_id = absint( get_option( 'ps_testing_post' ) ) ) {
			$id_where_clause = ' AND ID = %d ';
			$where_clause   .= $wpdb->prepare( $id_where_clause, $testing_post_id );
		}

		$page_size    = $this->settings['ps_page_size'];
		$sql          = "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status NOT IN ('auto-draft','trash') {$where_clause} ORDER BY post_date DESC LIMIT {$page_size} OFFSET %d";
		$prepared_sql = $wpdb->prepare( $sql, $objects_to_sync, $offset );

		// Get the results.
		$results = $wpdb->get_results( $prepared_sql, ARRAY_A );
		$posts   = array();

		if ( $results ) {

			foreach ( $results as $object ) {

				$object['tax_input']                            = $this->get_relationships( $object['ID'], $taxonomies );
				$object['meta_input']                           = get_post_meta( $object['ID'] );
				$object['meta_input']['press_sync_post_id']     = $object['ID'];
				$object['meta_input']['press_sync_source']      = home_url();
				$object['meta_input']['press_sync_gmt_offset']  = get_option( 'gmt_offset' );

				array_push( $posts, $object );

			}
		}

		return $posts;

	}

	/**
	 * Returns the users to sync.
	 *
	 * @since 0.1.0
	 *
	 * @param int $next_page The next page of results.
	 *
	 * @return WP_Users
	 */
	public function get_users_to_sync( $next_page = 1 ) {
		$query_args = array(
			'number' => $this->settings['ps_page_size'],
			'offset' => ( $next_page > 1 ) ? ( $next_page - 1 ) * $this->settings['ps_page_size'] : 0,
			'paged'  => $next_page,
		);

		$query = new \WP_User_Query( $query_args );

		$results = $query->get_results();
		$users   = array();

		if ( $results ) {

			foreach ( $results as $user ) {

				// Get user role.
				$role = $user->roles[0];

				// Get user data.
				$user = (array) $user->data;
				$user_meta = get_user_meta( $user['ID'] );

				foreach ( $user_meta as $key => $value ) {
					$user['meta_input'][ $key ] = $value[0];
				}

				$user['meta_input']['press_sync_user_id'] = $user['ID'];
				$user['meta_input']['press_sync_source']  = home_url();
				$user['role'] = $role;

				unset( $user['ID'] );

				array_push( $users, $user );

			}
		}

		return $users;

	}

	/**
	 * Returns the options to sync.
	 *
	 * @since 0.2.0
	 *
	 * @param int $next_page The next page of results.
	 *
	 * @return array $options The results.
	 */
	public function get_options_to_sync( $next_page = 1 ) {

		global $wpdb;

		$options       = array();

		// Fill a set of string placeholders.
		$placeholders  = array_fill( 0, count( $this->options ), '%s' );
		$format_string = implode( ', ', $placeholders );
		$prepared_sql  = "SELECT * FROM {$wpdb->options} WHERE option_name IN ({$format_string})";
		$sql           = $wpdb->prepare( $prepared_sql, $this->options );
		$options       = $wpdb->get_results( $sql, ARRAY_A );

		return $options;
	}

	/**
	 * Return the taxonomies (categories, tags and custom taxonomies) associated with the WP Object provided
	 *
	 * @since 0.1.0
	 *
	 * @param integer     $object_id  The ID of the WP object.
	 * @param WP_Taxonomy $taxonomies The related WP taxonomies.
	 *
	 * @return array $taxonomies
	 */
	public function get_relationships( $object_id, $taxonomies ) {
		if ( $this->settings['ps_partial_terms'] ) {
			/**
			 * Get just the terms and taxonomies.
			 */
			$sql = <<<SQL
SELECT
	t.slug,
	tt.taxonomy
FROM
{$GLOBALS['wpdb']->terms} t
JOIN
{$GLOBALS['wpdb']->term_taxonomy} tt USING( term_id )
JOIN
{$GLOBALS['wpdb']->term_relationships} tr USING ( term_taxonomy_id )
WHERE
	tr.object_id = %d
SQL;

			$sql   = $GLOBALS['wpdb']->prepare( $sql, $object_id );
			$terms = $GLOBALS['wpdb']->get_results( $sql, ARRAY_A );

			return $terms;
		}

		foreach ( $taxonomies as $key => $taxonomy ) {
			$taxonomies[ $taxonomy ] = get_the_terms( $object_id, $taxonomy ) ?: array();
			unset( $taxonomies[ $key ] );

			// Need to get term meta as well.
			foreach ( $taxonomies[ $taxonomy ] as $term ) {
				$term->meta_input = get_term_meta( $term->term_id ) ?: array();
				$term->meta_input['press_sync_term_id'] = $term->term_id;
			}
		}

		return $taxonomies;

	}

	/**
	 * Get the total number of WP objects to sync to remote site
	 *
	 * @since 0.1.0
	 *
	 * @param string $objects_to_sync The WP objects to sync.
	 *
	 * @return integer $total_objects
	 */
	public function count_objects_to_sync( $objects_to_sync ) {

		if ( 'all' === $objects_to_sync ) {
			return $this->count_all_to_sync();
		}

		if ( 'user' === $objects_to_sync ) {
			return $this->count_users_to_sync();
		}

		if ( 'option' === $objects_to_sync ) {
			return $this->count_options_to_sync();
		}

		if ( 'taxonomy_term' === $objects_to_sync ) {
			return $this->count_taxonomy_term_to_sync();
		}

		// If it's just one post return only 1.
		if ( $testing_post_id = absint( get_option( 'ps_testing_post' ) ) ) {
			return 1;
		}

		global $wpdb;

		$where_clause  = '';
		$where_clause  = $this->get_synced_object_clause( $objects_to_sync );
		$where_clause .= $this->get_posts_delta( $objects_to_sync );

		$query         = "SELECT count(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status NOT IN ('auto-draft','trash') {$where_clause}";
		$prepared_sql  = $wpdb->prepare( $query, $objects_to_sync );

		trigger_error( sprintf( 'Queried for count of posts to sync using: %s', $prepared_sql ) );

		$total_objects = $wpdb->get_var( $prepared_sql );

		return $total_objects;
	}

	/**
	 * Get the total number of all objects to sync.
	 *
	 * @since 0.6.1
	 */
	public function count_all_to_sync() {

		$count           = 0;
		$objects_to_sync = $this->objects_to_sync( array( 'all' ) );

		foreach ( $objects_to_sync as $object => $label ) {
			$count += absint( $this->count_objects_to_sync( $object ) );
		}

		return $count;
	}

	/**
	 * Get the total number of users to sync to remote site
	 *
	 * @since 0.1.0
	 *
	 * @return integer $total_users
	 */
	public function count_users_to_sync() {

		global $wpdb;

		$sql   = "SELECT COUNT(*) FROM {$wpdb->users}";
		$count = $wpdb->get_var($sql);

		return $count;
	}

	/**
	 * Get the total number of options to sync to remove site.
	 *
	 * @since 0.1.3
	 *
	 * @return integer
	 */
	public function count_options_to_sync() {

		// Parse the WP Options to be synced.
		$this->prepare_options( get_option( 'ps_options_to_sync' ) );

		return count( $this->options );
	}

	/**
	 * Preare the WP Post args to sync to the remote site.
	 *
	 * @since 0.1.0
	 *
	 * @param array $object_args The WP Post arguments to send to the remote site.
	 *
	 * @return array $object_args
	 */
	public function prepare_post_args_to_sync( $object_args = array() ) {

		foreach ( $object_args['meta_input'] as $meta_key => $meta_value ) {
			$object_args['meta_input'][ $meta_key ] = is_array( $meta_value ) ? $meta_value[0] : $meta_value;
		}

		// @TODO document.
		$object_args = apply_filters( 'press_sync_prepare_post_args_to_sync', $object_args );

		$object_args['embedded_media'] = $this->get_embedded_media( $object_args['post_content'] );

		// Send Featured image information along to be imported.
		$object_args['featured_image'] = $this->get_featured_image( $object_args['ID'] );

		// Get the comments for the post.
		$ignore_comments = get_option( 'ps_ignore_comments' );

		if ( $object_args['comment_count'] && ! $ignore_comments ) {
			$object_args['comments'] = $this->get_comments( $object_args['ID'] );
		}

		// Look for any P2P connections.
		if ( class_exists( '\\P2P_Autoload' ) ) {
			$object_args['p2p_connections'] = $this->get_p2p_connections( $object_args['ID'] );
		}

		/**
		 * Runs after a post's arguments are prepared for syncing.
		 *
		 * @since 0.7.0
		 *
		 * @param  array $object_args The arguments after preparation.
		 * @return array
		 */
		$object_args = apply_filters( 'press_sync_after_prepare_post_args_to_sync', $object_args );

		return $object_args;

	}

	/**
	 * Preare the WP Options args to sync to the remote site.
	 *
	 * @since 0.2.0
	 *
	 * @param array $object_args The WP options arguments.
	 *
	 * @return array $object_args
	 */
	public function prepare_option_args_to_sync( $object_args = array() ) {
		return $object_args;
	}

	/**
	 * Replace the local domain links in post_content with the remote site domain.
	 *
	 * @since 0.1.0
	 *
	 * @param array $object_args The WP Object args.
	 *
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
	 * Get the featured image for a WP Post.
	 *
	 * @since 0.1.0
	 *
	 * @param integer $post_id The WP Post ID.
	 *
	 * @return WP_Attachment $media
	 */
	public function get_featured_image( $post_id ) {

		$thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );

		if ( ! $thumbnail_id ) {
			return false;
		}

		$media = get_post( $thumbnail_id, ARRAY_A );
		$media['meta_input'] = get_post_meta( $thumbnail_id );

		// Maybe filter out wp built-in meta.
		foreach ( $media['meta_input'] as $meta_key => $meta_value ) {
			if ( ! get_option( 'ps_skip_assets' ) && 0 === strpos( $meta_key, '_wp_' ) ) {
				unset( $media['meta_input'][$meta_key] );
			}

			if ( is_array( $meta_value ) ) {
				$media['meta_input'][$meta_key] = current( $meta_value );
			}
		}

		// @TODO Filterable?
		$media_urls = array(
			'local_media' => home_url( 'wp-content/uploads/' . get_post_meta( $thumbnail_id, '_wp_attached_file', true ) ),
			'guid_media'  => $media['guid'],
		);

		foreach ( $media_urls as $url ) {
			if ( get_option( 'ps_skip_assets' ) ) {
				continue;
			}

			if ( $this->is_404( $url ) ) {
				continue;
			}

			$media['attachment_url'] = $url;
			break;
		}

		$media['meta_input']['press_sync_post_id'] = $thumbnail_id;

		return $media;
	}

	/**
	 * Get all of the comments for a post.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id The WP Post ID.
	 *
	 * @return array
	 */
	public function get_comments( $post_id ) {

		$query_args = array(
			'post_id' => $post_id,
		);

		$comments = get_comments( $query_args );

		if ( ! $comments ) {
			return false;
		}

		foreach ( $comments as $key => $comment_args ) {
			$comment_args     = (array) $comment_args;
			$comments[ $key ] = $this->prepare_comment_args_to_sync( $comment_args );
		}

		return $comments;
	}

	/**
	 * Return the P2P connections for a single post.
	 *
	 * @since 0.1.0
	 *
	 * @param integer $post_id The WP Post ID.
	 *
	 * @return array $p2p_connections
	 */
	public function get_p2p_connections( $post_id ) {

		global $wpdb;

		$sql = "SELECT p2p_from, p2p_to, p2p_type FROM {$wpdb->prefix}p2p WHERE p2p_from = $post_id OR p2p_to = $post_id";
		$p2p_connections = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $p2p_connections;

	}

	/**
	 * Filter user before synced with the remote site.
	 *
	 * @since 0.1.0
	 *
	 * @param array $user_args The WP User arguments to sync.
	 *
	 * @return array $user_args
	 */
	public function prepare_user_args_to_sync( $user_args ) {

		// Remove the user password.
		// @TODO is there an issue in the API with sending passwords?
		$user_args['user_pass'] = null;

		return $user_args;
	}

	/**
	 * Filter attachment before synced with the remote site.
	 *
	 * @since 0.1.0
	 *
	 * @param array $object_args The WP Attachment arguments to sync.
	 *
	 * @return array $object_args
	 */
	public function prepare_attachment_args_to_sync( $object_args ) {

		$attachment_url = $object_args['guid'];

		$args = array(
			'post_date'      => $object_args['post_date'],
			'post_title'     => $object_args['post_title'],
			'post_name'      => $object_args['post_name'],
			'attachment_url' => $attachment_url,
			'post_type'      => $object_args['post_type'],
			'meta_input' => array(
				'press_sync_post_id' => $object_args['ID'],
			),
			'post_author' => $object_args['post_author'],
			'post_parent' => $object_args['post_parent'],
		);

		$meta = get_post_meta( $object_args['ID'] );

		foreach ( $meta as $key => $values ) {
			$args['meta_input'][ $key ] = $values[0];
		}

		if ( get_option( 'ps_skip_assets' ) ) {
			$args['guid']           = $object_args['guid'];
			$args['post_mime_type'] = $object_args['post_mime_type'];
		}

		if ( get_option( 'ps_preserve_ids' ) ) {
			$args['import_id'] = $object_args['ID'];
		}

		return $args;
	}

	/**
	 * Filter comment before synced with the remote site.
	 *
	 * @since 0.1.0
	 *
	 * @param array $comment_args The WP Comments to sync.
	 *
	 * @return array $comment_args
	 */
	public function prepare_comment_args_to_sync( $comment_args ) {

		$args = array();

		$args['comment_post_ID']                     = $comment_args['comment_post_ID'];
		$args['comment_author']                      = $comment_args['comment_author'];
		$args['comment_author_email']                = $comment_args['comment_author_email'];
		$args['comment_author_url']                  = $comment_args['comment_author_url'];
		$args['comment_author_IP']                   = $comment_args['comment_author_IP'];
		$args['comment_date']                        = $comment_args['comment_date'];
		$args['comment_date_gmt']                    = $comment_args['comment_date_gmt'];
		$args['comment_content']                     = $comment_args['comment_content'];
		$args['comment_karma']                       = $comment_args['comment_karma'];
		$args['comment_approved']                    = $comment_args['comment_approved'];
		$args['comment_agent']                       = $comment_args['comment_agent'];
		$args['comment_type']                        = $comment_args['comment_type'];
		$args['comment_parent']                      = $comment_args['comment_parent'];
		$args['user_id']                             = $comment_args['user_id'];
		$args['meta_input']['press_sync_comment_id'] = $comment_args['comment_ID'];
		$args['meta_input']['press_sync_post_id']    = $comment_args['comment_post_ID'];
		$args['meta_input']['press_sync_source']     = home_url();

		return $args;
	}

	/**
	 * POST data to the remote site.
	 *
	 * @since 0.1.0
	 *
	 * @param string $url  The url of the remote site.
	 * @param array  $args The arguments to send to the remote site.
	 *
	 * @return JSON $response_body
	 */
	public function send_data_to_remote_site( $url, $args ) {

		$args = array(
			'timeout' => 30,
			'body'    => $args,
		);

		$response      = wp_remote_post( $url, $args );
		$response_body = wp_remote_retrieve_body( $response );

		return $response_body;
	}

	/**
	 * Find any embedded images in the post content.
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_content The WP Post content to search for media.
	 */
	public function get_embedded_media( $post_content = '' ) {

		$embedded_media = array();

		if ( ! $post_content ) {
			return $embedded_media;
		}

		$doc = new \DOMDocument();

		// set error level.
		$internal_errors = libxml_use_internal_errors( true );

		$doc->loadHTML( $post_content );

		// Restore error level.
		libxml_use_internal_errors( $internal_errors );

		$images = $doc->getElementsByTagName( 'img' );

		foreach ( $images as $image ) {

			$attachment_url = $image->getAttribute( 'src' );

			if ( false === stripos( $attachment_url, $this->local_domain ) ) {
				continue;
			}

			$attachment_args['attachment_url']  = $attachment_url;
			$attachment_args['details']         = $this->get_attachment_details( $attachment_url );

			array_push( $embedded_media, $attachment_args );
		}

		unset( $doc );

		return $embedded_media;

	}

	/**
	 * Find the meta data for a specified WP Attachment via URL.
	 *
	 * @param string $attachment_url The WP Attachment url.
	 *
	 * @return array $attachment_details
	 */
	public function get_attachment_details( $attachment_url = '' ) {

		$attachment_details = array();
		$last_dash_pos      = strrpos( $attachment_url, '-' );
		$image_query_string = substr( $attachment_url, 0, $last_dash_pos );

		global $wpdb;

		$attachment = $wpdb->get_row( "SELECT ID, guid FROM $wpdb->posts WHERE guid LIKE '$image_query_string%'" );

		if ( ! $attachment ) {
			return $attachment_details;
		}

		$image          = wp_get_attachment_metadata( $attachment->ID );
		$filename_parts = explode( '/', $image['file'] );

		$attachment_details = array(
			'filename'  => $filename_parts[2],
			'post_date' => $filename_parts[0] . '/' . $filename_parts[1],
			'url'       => $attachment->guid,
			'ID'        => $attachment->ID,
		);

		return $attachment_details;
	}

	/**
	 * Check to see if a file already exists on the server.
	 *
	 * @param string $filename  The filename.
	 * @param string $post_date The date the file was created.
	 *
	 * @return int $attachment_id
	 */
	public function file_exists( $filename = '', $post_date = '' ) {

		$filename_partial_path = trailingslashit( $post_date ) . basename( $filename );
		$wp_upload_dir         = wp_upload_dir();

		if ( ! file_exists( trailingslashit( $wp_upload_dir['basedir'] ) . $filename_partial_path ) ) {
			return false;
		}

		global $wpdb;

		$attachment_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE guid LIKE '%$filename_partial_path%'" );

		return $attachment_id;
	}

	/**
	 * Filter to approve localhost urls.
	 *
	 * @param boolean $approve Bool to indicate whether the domain is approved or not.
	 * @param string  $host    The host of the url.
	 * @param string  $url     The full url.
	 *
	 * @return boolean $approve
	 */
	public function approve_localhost_urls( $approve, $host, $url ) {

		if ( 0 <= stripos( $host, '.local' ) ) {
			return true;
		}

		return $approve;
	}

	/**
	 * Perform a full synchronization of the specified content type.
	 *
	 * @param string  $content_type The WP object you want to sync.
	 * @param array   $settings     The Press Sync settings.
	 * @param integer $next_page    The pagination number for querying results.
	 * @param boolean $is_batch     Falg to determine if this is a batch sync or not.
	 * @param boolean $cli_enabled  Flag used to display WP CLI messages.
	 *
	 * @return array $response
	 */
	public function sync_object( $content_type = 'post', $settings = array(), $next_page = 1, $is_batch = false, $cli_enabled = false ) {

		$do_run                      = true;
		$settings                    = $this->parse_sync_settings( $settings );
		$settings['objects_to_sync'] = $content_type;

		$this->progress->start( 'Syncing ' . $content_type . 's', $this->count_objects_to_sync( $content_type ) );

		while ( $do_run ) {

			$response                = $this->sync_batch( $content_type, $settings, $next_page );
			$total_objects           = isset( $response['total_objects'] ) ? (int) $response['total_objects'] : 0;
			$total_objects_processed = isset( $response['total_objects_processed'] ) ? (int) $response['total_objects_processed'] : 0;
			$next_page               = isset( $response['next_page'] ) ? $response['next_page'] : 0;

			if ( $total_objects === $total_objects_processed || $is_batch ) {
				$do_run = false;
			}

			$this->progress->tick();
		}

		$this->progress->finish();

		return $response;
	}

	/**
	 * Perform a batch synchronization of the specified content type.
	 *
	 * @param string  $content_type The WP object you want to sync.
	 * @param array   $settings     The Press Sync settings.
	 * @param integer $next_page    Pagination counter for bath queries.
	 */
	public function sync_batch( $content_type = 'post', $settings = array(), $next_page = 1 ) {

		// Get all of the objects within this batch.
		$taxonomies        = get_object_taxonomies( $content_type );
		$objects           = $settings['local_folder'] ? $this->get_local_objects_to_sync( $settings['local_folder'], $content_type ) : $this->get_objects_to_sync( $content_type, $next_page, $taxonomies );
		$total_objects     = $settings['local_folder'] ? count( $objects ) : $this->count_objects_to_sync( $content_type );

		// Prepare each object to be sent to the remote site.
		$objects_args = $this->prepare_objects_to_sync( $objects, $settings );

		// Slow down the sync.
		$this->slow_down_sync();

		// Resume the sync from a previous location.
		$next_page = $this->change_the_next_page( $next_page );

		// Initialize the connection credentials.
		$this->init_connection( $settings['remote_domain'] );

		// Build out the url and send the data to the remote site.
		$url  = $this->get_remote_url( '', 'sync' );
		$logs = $this->send_data_to_remote_site( $url, $objects_args );

		return array(
			'objects_to_sync'         => $content_type,
			'total_objects'           => $total_objects,
			'total_objects_processed' => ( $next_page * $this->settings['ps_page_size'] ) - ( $this->settings['ps_page_size'] - count( $objects ) ),
			'next_page'               => $next_page + 1,
			'log'                     => $logs,
		);
	}

	/**
	 * Get the order to sync all objects.
	 *
	 * @since 0.6.1
	 *
	 * @param array $order_to_sync_all The objects in order of sync.
	 *
	 * @return array $order_to_sync_all
	 */
	public function order_to_sync_all( $order_to_sync_all = array() ) {
		// An array of all of the core WP objects in the desired order to sync.
		$order_to_sync_all = array( 'user', 'taxonomy_term', 'option', 'post', 'page', 'media' );

		// Get any CPTs.
		$custom_post_types = get_post_types( array( '_builtin' => false ), 'objects' );

		if ( $custom_post_types ) {

			foreach ( $custom_post_types as $cpt ) {
				array_push( $order_to_sync_all, $cpt->name );
			}
		}

		return $order_to_sync_all;
	}

	/**
	 * Filter to parse the sync setting provided by a sync request.
	 *
	 * @param array $settings The sync settings.
	 *
	 * @return array $settings
	 */
	public function parse_sync_settings( $settings = array() ) {

		$this->settings = wp_parse_args( $settings, array(
			'remote_domain'        => get_option( 'ps_remote_domain' ),
			'ps_remote_key'        => get_option( 'ps_remote_key' ),
			'sync_method'          => get_option( 'ps_sync_method' ),
			'objects_to_sync'      => get_option( 'ps_objects_to_sync' ),
			'duplicate_action'     => get_option( 'ps_duplicate_action' ),
			'force_update'         => get_option( 'ps_force_update', false ),
			'skip_assets'          => get_option( 'ps_skip_assets', false ),
			'options'              => get_option( 'ps_options_to_sync' ),
			'local_folder'         => '',
			'preserve_ids'         => get_option( 'ps_preserve_ids', false ),
			'fix_terms'            => get_option( 'ps_fix_terms', false ),
			'ps_content_threshold' => get_option( 'ps_content_threshold', false ),
			'ps_partial_terms'     => get_option( 'ps_partial_terms', false ),
			'ps_page_size'         => get_option( 'ps_page_size', self::PAGE_SIZE ),
		) );

		return $this->settings;
	}

	/**
	 * Prepare the arguments that need to be passed to the remote site.
	 *
	 * @since 0.6.1
	 *
	 * @param array $objects  The objects that will be sent to the remote site.
	 * @param array $settings The sync settings.
	 *
	 * @return array $settings
	 */
	public function prepare_objects_to_sync( $objects = array(), $settings = array() ) {

		// Select the proper sync function for the object to sync.
		$sync_function = $this->get_sync_function_name( $settings['objects_to_sync'] );

		if ( empty( $objects ) ) {
			return $settings;
		}

		foreach ( $objects as $key => $object ) {
			$settings['objects'][ $key ] = $this->$sync_function( $object );
		}

		return $settings;
	}

	/**
	 * Converts the WP options to an array.
	 *
	 * @param string $options The WP options to be queried.
	 */
	public function prepare_options( $options = '' ) {
		$this->options = array_filter( explode( ',', $options ) );
	}

	/**
	 * Prepares a list of WP Objects to sync.
	 *
	 * @since 0.1.0
	 *
	 * @param array $exclude The slugs of the objects to sync you want to exclude.
	 *
	 * @return array $objects A list of WP objects.
	 */
	public function objects_to_sync( $exclude = array() ) {

		$objects = array(
			'all'           => __( 'All', 'press-sync' ),
			'taxonomy_term' => __( 'Taxonomies &amp; Terms', 'press-sync' ),
			'attachment'    => __( 'Media', 'press-sync' ),
			'page'          => __( 'Pages', 'press-sync' ),
			'post'          => __( 'Posts', 'press-sync' ),
			'user'          => __( 'Users', 'press-sync' ),
			'option'        => __( 'Options', 'press-sync' ),
		);

		$custom_post_types = get_post_types( array( '_builtin' => false ), 'objects' );

		if ( $custom_post_types ) {

			$objects[] = '-- Custom Post Types --';

			foreach ( $custom_post_types as $cpt ) {
				$objects[ $cpt->name ] = $cpt->label;
			}
		}

		$objects = apply_filters( 'press_sync_objects_to_sync', $objects );

		// Remove any unwanted objects.
		foreach ( $exclude as $object_type ) {
			unset( $objects[ $object_type ] );
		}

		return $objects;
	}

	/**
	 * Search a folder for all WP Objects in local json files.
	 *
	 * @since 0.4.3
	 *
	 * @param string $local_folder The local folder that contains the json files.
	 * @param string $objects_to_sync The WP objects to sync.
	 *
	 * @return array $objects
	 */
	public function get_local_objects_to_sync( $local_folder = '', $objects_to_sync = '' ) {

		$objects = array();

		if ( empty( $local_folder ) ) {
			return $objects;
		}

		if ( 'post' === $objects_to_sync ) {
			return $this->get_local_post_json( $local_folder );
		}

		$local_path = trailingslashit( $local_folder ) . $objects_to_sync . 's.json';

		if ( ! file_exists( $local_path ) ) {
			return $objects;
		}

		$contents   = file_get_contents( $local_path );
		$objects    = json_decode( $contents, 1 );

		return $objects;
	}

	/**
	 * Search a folder for all WP posts in local json files.
	 *
	 * @since 0.4.3
	 *
	 * @param string $local_folder The local folder that contains the json files.
	 *
	 * @return array $objects
	 */
	public function get_local_post_json( $local_folder = '' ) {

		$objects = array();

		$local_path = trailingslashit( $local_folder ) . 'posts/';

		if ( ! file_exists( $local_path ) ) {
			return $objects;
		}

		$directory = new \RecursiveDirectoryIterator( $local_path );
		$iterator = new \RecursiveIteratorIterator( $directory );

		// Loop through each file to import.
		foreach ( $iterator as $file ) {

			if ( is_dir( $file ) ) {
				continue;
			}

			if ( false === stripos( $file, '.json' ) ) {
				continue;
			}

			$contents = file_get_contents( $file->getPathname() );
			$contents = json_decode( $contents, 1 );

			array_push( $objects, $contents );
		}

		return $objects;
	}

	/**
	 * Gets a WHERE clause statement to use for syncing missing objects.
	 *
	 * @since 0.6.0
	 *
	 * @param string $objects_to_sync The thing we're syncing, in this case post_type.
	 *
	 * @return string
	 */
	public function get_synced_object_clause( $objects_to_sync ) {
		$synced_posts = $this->get_synced_object_ids( $objects_to_sync );

		if ( ! get_option( 'ps_only_sync_missing' ) || empty( $synced_posts ) ) {
			return '';
		}

		$placeholders  = array_fill( 0, count( $synced_posts ), '%d' );
		$format_string = implode( ', ', $placeholders );
		$prepared_sql  = " AND ID NOT IN ({$format_string}) ";
		$sql           = $GLOBALS['wpdb']->prepare( $prepared_sql, $synced_posts );

		return $sql;
	}

	/**
	 * Checkts to see if a url is 404ing.
	 *
	 * Thanks SO: https://stackoverflow.com/questions/18473325/check-if-a-http-request-returns-404-or-page-not-found
	 *
	 * @param string $url The url we're requesting.
	 *
	 * @return boolean
	 */
	private function is_404( $url ) {

		$handle = curl_init( $url );

		curl_setopt( $handle,  CURLOPT_RETURNTRANSFER, TRUE );
		curl_exec( $handle );

		$code = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

		if ( '404' === $code ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets the remote site URL and appends query parameters.
	 *
	 * @since 0.5.1
	 *
	 * @param  string $url      A URL other than the stored remote URL to use.
	 * @param  string $endpoint The remote site endpoint.
	 * @since 0.7.0
	 * @param  array  $args     (Optional) Array of query parameters.
	 *
	 * @return string
	 */
	public function get_remote_url( $url = '', $endpoint = 'status', $args = array() ) {
		$url        = $url ? trailingslashit( $url ) : trailingslashit( get_option( 'ps_remote_domain' ) );
		$query_args = wp_parse_args( $args, array(
			'press_sync_key' => get_option( 'ps_remote_key' ),
		) );

		if ( $remote_args = get_option( 'ps_remote_query_args' ) ) {
			$remote_args = ltrim( $remote_args, '?' );
			parse_str( $remote_args, $remote_args_array );
			$query_args = array_merge( $query_args, $remote_args_array );
		}

		return  "{$url}wp-json/press-sync/v1/{$endpoint}?" . http_build_query( $query_args );
	}

	/**
	 * Converts a object to sync into it's sync function name.
	 *
	 * @since 0.6.1
	 *
	 * @param string $objects_to_sync The objects to sync.
	 *
	 * @return string $wp_object
	 */
	public function get_sync_function_name( $objects_to_sync = '' ) {
		$wp_object = in_array( $objects_to_sync, array( 'attachment', 'comment', 'user', 'option', 'taxonomy_term' ), true ) ? $objects_to_sync : 'post';
		return "prepare_{$wp_object}_args_to_sync";
	}

	/**
	 * Slows down the sync process to prevent db locking on the remote site.
	 *
	 * @since 0.6.1
	 */
	public function slow_down_sync() {

		$request_buffer_time = absint( get_option( 'ps_request_buffer_time' ) );

		if ( 0 < $request_buffer_time && 60 >= $request_buffer_time ) {
			sleep( $request_buffer_time );
		}

	}

	/**
	 * Change the $next_page value for a batch synce.
	 *
	 * Useful so that the sync can resume from a previous location.
	 *
	 * @since 0.6.1
	 *
	 * @param integer $next_page The next page in the sync process.
	 *
	 * @return integer $next_page
	 */
	public function change_the_next_page( $next_page = 1 ) {

		$page_offset = absint( get_option( 'ps_start_object_offset' ) );

		if ( 0 < $page_offset && 1 === absint( $next_page ) ) {

			$page_offset = floor( $page_offset / $this->settings['ps_page_size'] );
			$next_page  += ( $page_offset - 1);

			error_log( '----NP: ' . $next_page );
		}

		return $next_page;
	}

	/**
	 * Get the synced object IDs from options or the remote site.
	 *
	 * This gets reset whenever a sync is started on the first page, see Dashboard::sync_wp_data_via_ajax.
	 *
	 * @since 0.7.0
	 *
	 * @param  string $objects_to_sync The post type of the things to sync.
	 * @return array
	 */
	public function get_synced_object_ids( $objects_to_sync ) {
		$option_name = "ps_synced_post_session_{$objects_to_sync}";
		$last_sync   = get_option( $option_name );

		if ( is_array( $last_sync ) && ! empty( $last_sync ) ) {
			return $last_sync;
		}

		$url = $this->get_remote_url( '', 'progress', array(
			'post_type'    => $objects_to_sync,
            'preserve_ids' => (bool) get_option( 'ps_preserve_ids' ),
		) );

		$remote_get_args = array(
			'timeout' => 30,
		);

		$response      = wp_remote_get( $url, $remote_get_args );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			return array();
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $response_body['data']['synced'] ) ) {
			return array();
		}

		update_option( $option_name, $response_body['data']['synced'] );

		return $response_body['data']['synced'];
    }

	/**
	 * Removes post IDs if the option to preserve them isn't active.
	 *
	 * @since 0.7.0
	 *
	 * @param  array $object_args The object's prepared arguments.
	 * @return array
	 */
	public function maybe_remove_post_id( $object_args ) {
		$object_args['import_id'] = $object_args['ID'];
		unset( $object_args['ID'] );

		if ( true !== (bool) get_option( 'ps_preserve_ids' ) ) {
			unset( $object_args['import_id'] );
		}

		return $object_args;
	}

	/**
	 * Counts the number of taxonomies to sync.
	 *
	 * @since 0.7.0
	 * @return int
	 */
	public function count_taxonomy_term_to_sync() {
		$sql = <<<SQL
SELECT DISTINCT
	taxonomy, term_id
FROM
	{$GLOBALS['wpdb']->term_taxonomy} tt
SQL;

		/**
		 * Filter the SELECT statement for getting taxonomy terms to sync.
		 *
		 * @since 0.7.1
		 * @param  string $sql The SQL SELECT being used to get taxonomy terms.
		 * @return string
		 */
		$select = apply_filters( 'press_sync_get_taxonomy_term_select', $sql );

		/**
		 * Filter the WHERE clause when finding taxonomy terms to sync.
		 *
		 * @since 0.7.1
		 * @param  string $where The WHERE clause being used to get taxonomy terms.
		 * @return string
		 */
		$where  = apply_filters( 'press_sync_get_taxonomy_term_where', ' WHERE 1=1 ' );

		$sql = "{$select} {$where}";

		$res = $GLOBALS['wpdb']->get_results( $sql );
		return count( $res );
	}

	/**
	 * Gets the next set of taxonomies/terms to sync.
	 *
	 * @since 0.7.0
	 * @param  int   $next_page The page of results to get.
	 * @return array
	 */
	public function get_taxonomy_term_to_sync( $next_page ) {
		$offset = ( $next_page * $this->settings['ps_page_size'] ) - $this->settings['ps_page_size'];
		$select = '';
		$joins  = '';
		$where  = ' WHERE 1=1 ';

		$select = <<<SQL
SELECT DISTINCT
	tt.term_id,
	tt.taxonomy,
	tt.description,
	tt.parent,
	t.*
FROM
	{$GLOBALS['wpdb']->term_taxonomy} tt
SQL;

		/**
		 * Filter the SELECT statement for getting taxonomy terms to sync.
		 *
		 * @since 0.7.1
		 * @param  string $select The SQL SELECT being used to get taxonomy terms.
		 * @return string
		 */
		$select = apply_filters( 'press_sync_get_taxonomy_term_select', $select );

		$joins = <<<SQL
JOIN {$GLOBALS['wpdb']->terms} t ON ( t.term_id = tt.term_id )
SQL;

		/**
		 * Filter the JOIN clause when finding taxonomy terms to sync.
		 *
		 * @since 0.7.1
		 * @param  string $joins The JOIN clause being used to get taxonomy terms.
		 * @return string
		 */
		$joins = apply_filters( 'press_sync_get_taxonomy_term_joins', $joins );

		/**
		 * Filter the WHERE clause when finding taxonomy terms to sync.
		 *
		 * @since 0.7.1
		 * @param  string $where The WHERE clause being used to get taxonomy terms.
		 * @return string
		 */
		$where = apply_filters( 'press_sync_get_taxonomy_term_where', $where );

		$sql = <<<SQL
{$select}
{$joins}
{$where}
ORDER BY t.term_id ASC
LIMIT {$offset}, $this->settings['ps_page_size']
SQL;

		return $GLOBALS['wpdb']->get_results( $sql, ARRAY_A );
	}

	/**
	 * Prepares the taxonomy term data to go across the API.
	 *
	 * @since 0.7.0
	 * @param  array $taxonomy_term The tax term to prepare.
	 * @return array
	 */
	public function prepare_taxonomy_term_args_to_sync( $taxonomy_term ) {
		$taxonomy_term['meta_input'] = get_term_meta( $taxonomy_term['term_id'] );
		$taxonomy_term['meta_input']['press_sync_term_id'] = $taxonomy_term['term_id'];
		return $taxonomy_term;
	}

	/**
	 * Get terms for a specific post when we're using a test post ID.
	 *
	 * @since 0.7.1
	 * @param  string $where The WHERE clause being filtered.
	 * @return string
	 */
	public function maybe_get_terms_for_post( $where ) {
		if ( ! get_option( 'ps_testing_post' ) ) {
			return $where;
		}

		$where .= <<<SQL
AND tt.term_taxonomy_id IN (
	SELECT
		term_taxonomy_id
	FROM
		{$GLOBALS['wpdb']->term_relationships}
	WHERE
		object_id = %d
)
SQL;
		$where = $GLOBALS['wpdb']->prepare( $where, get_option( 'ps_testing_post' ) );
		return $where;
	}

	/**
	 * Get the posts delta between a given date and now.
	 *
	 * @since 0.8.0
	 * @param string $objects_to_sync The object type to sync.
	 *
	 * @return string
	 */
	protected function get_posts_delta( $objects_to_sync ) {
		if ( ! $this->delta_date ) {
			return '';
		}

		// Currently only supported for posts.
		if ( 'post' !== $objects_to_sync ) {
			return '';
		}

		return $GLOBALS['wpdb']->prepare( ' AND post_modified >= %s ', $this->delta_date );
	}
}
