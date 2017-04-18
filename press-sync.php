<?php
/*
Plugin Name: Press Sync
Description: Sync WordPress sites. Includes attachments, users and WooCommerce Support
Version: 0.1.0
License: GPL
Author: Marcus Battle
Author URI: http://marcusbattle.com
*/

class Press_Sync {

	protected static $single_instance = null;

	public $current_domain = null;

	public $new_domain = null;

	static function init() {

		if ( self::$single_instance === null ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;

	}

	public function __construct() { }

	public function hooks() {

		// Include other files.
		$this->includes();

		// Initialize plugin classes.
		$this->plugin_classes();


		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );

		// CMB2 Fields | Fields to save sync data
		add_action( 'cmb2_admin_init', array( $this, 'press_sync_metabox' ) );
		add_action( 'cmb2_render_connection_status', array( $this, 'render_connection_status_field' ), 10, 5 );
		add_action( 'cmb2_render_sync_button', array( $this, 'render_sync_button_field' ), 10, 5 );

		add_action( 'wp_ajax_sync_wp_data', array( $this, 'sync_wp_data_via_ajax' ) );
		add_action( 'wp_ajax_get_objects_to_sync_count', array( $this, 'get_objects_to_sync_count_via_ajax' ) );

		add_filter( 'press_sync_prepare_post_args_to_sync', array( $this, 'prepare_woo_order_args_to_sync' ), 10, 1 );

		add_action( 'press_sync_insert_new_post', array( $this, 'insert_woo_order_items' ), 10, 2 );

	}

	/**
	 * Include a file from the includes directory.
	 *
	 * @since  0.1.0
	 *
	 * @param  string $filename Name of the file to be included.
	 * @return boolean          Result of include call.
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
	 * @param  string $path (optional) appended path.
	 * @return string       Directory and path.
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
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
		$this->dashboard = new Press_Sync_Dashboard( $this );
		$this->api = new Press_Sync_API( $this );
	}

	/**
	 * Includes a page for display in the WP Admin
	 *
	 * @since 0.1.0
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
		$res = ob_get_contents();
		ob_end_clean();

		echo $res;

		return true;

	}

	/**
	 * Returns the specified press sync option
	 *
	 * @since 0.1.0
	 *
	 * @param
	 * @return string
	 */
	public function press_sync_option( $option ) {
		$press_sync_options = get_option( 'press-sync-options' );
		return isset( $press_sync_options[ $option ] ) ? $press_sync_options[ $option ] : '';
	}

	public function load_scripts() {

		wp_enqueue_script( 'press-sync', plugins_url( 'assets/js/press-sync.js', __FILE__ ), true );
		wp_localize_script( 'press-sync', 'press_sync', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

	}

	public function press_sync_metabox() {

		$option_key = 'press_sync_';

		$cmb_options = new_cmb2_box( array(
			'id'      => $option_key . 'metabox',
			'title'   => __( 'Press Sync Metabox', 'press-sync' ),
			'hookup'  => false, // Do not need the normal user/post hookup
			'show_on' => array(
				// These are important, don't remove
				'key'   => 'options-page',
				'value' => array( 'press_sync_options' )
			),
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Connected Server', 'press-sync' ),
			'id'      => 'connected_server',
			'type'    => 'text',
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Connection Status', 'press-sync' ),
			'id'      => 'connection_status',
			'type'    => 'connection_status',
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Sync Method', 'press-sync' ),
			'id'      => 'sync_method',
			'type'    => 'select',
			'options' => array(
				'push' => 'Push'
			)
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Objects to Sync', 'press-sync' ),
			'id'      => 'objects_to_sync',
			'type'    => 'select',
			'options' => array( $this, 'objects_to_sync' )
		) );

	}

	public function objects_to_sync( $objects = array() ) {

		$objects = array(
			'comment'		=> 'Comments',
			'attachment' 	=> 'Media',
			'page' 			=> 'Pages',
			'post' 			=> 'Posts',
			'user'			=> 'Users',
		);

		/* $custom_post_types = get_post_types( array( '_builtin' => false ), 'objects' );

		foreach ( $custom_post_types as $cpt ) {
			$objects[ $cpt->name ] = $cpt->label;
		}
		*/

		return $objects;

	}

	public function render_connection_status_field( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {

		$url = cmb2_get_option( 'press-sync-options', 'connected_server' );

		$is_connected = $this->check_connection( $url );

		if ( $is_connected ) {
			echo "<div><p>Connected</p></div>";
		} else {
			echo "<div><p>Not Connected</p></div>";
		}

	}

	public function check_connection( $url ) {

		$remote_get_args = array(
			'timeout'	=> 30
		);

		$response = wp_remote_get( $url, $remote_get_args );
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 == $response_code ) {
			return true;
		}

		return false;

	}

	public function get_objects_to_sync_count_via_ajax() {

		$objects_to_sync 	= cmb2_get_option( 'press-sync-options', 'objects_to_sync' );
		$prepare_object 	= ! in_array( $objects_to_sync, array( 'attachment', 'comment', 'user' ) ) ? 'post' : $objects_to_sync;

		$total_objects 	= $this->count_objects_to_sync( $objects_to_sync );

		$wp_object = in_array( $objects_to_sync, array( 'attachment', 'comment', 'user' ) ) ? ucwords( $objects_to_sync ) . 's' : get_post_type_object( $objects_to_sync );
		$wp_object = isset( $wp_object->labels->name ) ? $wp_object->labels->name : $wp_object;

		wp_send_json_success( array(
			'objects_to_sync'	=> $wp_object,
			'total_objects' 	=> $total_objects
		) );

	}

	public function sync_wp_data_via_ajax() {

		$this->init_connection();

		$sync_method = cmb2_get_option( 'press-sync-options', 'sync_method' );
		$objects_to_sync = cmb2_get_option( 'press-sync-options', 'objects_to_sync' );

		$prepare_object = ! in_array( $objects_to_sync, array( 'attachment', 'comment', 'user' ) ) ? 'post' : $objects_to_sync;
		$wp_object = in_array( $objects_to_sync, array( 'attachment', 'comment', 'user' ) ) ? ucwords( $objects_to_sync ) . 's' : get_post_type_object( $objects_to_sync );
		$wp_object = isset( $wp_object->labels->name ) ? $wp_object->labels->name : $wp_object;

		// Build out the url
		$url = cmb2_get_option( 'press-sync-options', 'connected_server' );
		$url = untrailingslashit( $url ) . '/wp-json/press-sync/v1/' . $prepare_object;

		// Prepare the correct sync method
		$sync_class 	= 'prepare_' . $prepare_object . '_args_to_sync';

		$total_objects 	= $this->count_objects_to_sync( $objects_to_sync );
		$taxonomies 	= get_object_taxonomies( $objects_to_sync );
		$paged 			= isset( $_POST['paged'] ) ? (int) $_POST['paged'] : 1;

		$objects = $this->get_objects_to_sync( $objects_to_sync, $paged, $taxonomies );

		// Send parsed objects to target server
		foreach ( $objects as $object ) {
			$args = $this->$sync_class( $object );
			$this->send_data_to_remote_server( $url, $args );
		}

		wp_send_json_success( array(
			'objects_to_sync'			=> $wp_object,
			'total_objects'				=> $total_objects,
			'total_objects_processed'	=> count( $objects ) ? count( $objects ) * $paged : 10 * $paged,
			'next_page'					=> $paged + 1
		) );

		/* while ( $objects = $this->get_objects_to_sync( $objects_to_sync, $paged, $taxonomies ) ) {

			foreach ( $objects as $object ) {
				$args = $this->$sync_class( $object );
				$this->send_data_to_remote_server( $url, $args );
			}

			$paged++;
			exit;
		}

		wp_die();
		*/


	}

	public function init_connection() {

		$this->current_domain = untrailingslashit( home_url() );
		$this->new_domain = untrailingslashit( cmb2_get_option( 'press-sync-options', 'connected_server' ) );

	}

	public function get_objects_to_sync( $objects_to_sync, $paged = 1, $taxonomies ) {

		if ( 'user' == $objects_to_sync ) {
			$objects = $this->get_users_to_sync( $paged );
		} else {
			$objects = $this->get_posts_to_sync( $objects_to_sync, $paged, $taxonomies );
		}

		return $objects;

	}

	public function get_posts_to_sync( $objects_to_sync, $paged = 1, $taxonomies ) {

		$query_args = array(
			'post_type' => $objects_to_sync,
			'posts_per_page' => 10,
			'post_status' => 'any',
			'paged' => $paged,
			'order'	=> 'ASC',
			'orderby'	=> 'post_parent'
		);

		$query = new WP_Query( $query_args );

		$posts = array();

		if ( $query->posts ) {

			foreach ( $query->posts as $object ) {

				$object = (array) $object;

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
	 * @param int $paged
	 *
	 * @return WP_Users
	 */
	public function get_users_to_sync( $paged = 1 ) {

		$query_args = array(
			'number'	=> 10,
			'offset'	=> ( $paged > 1 ) ? ( $paged - 1 ) * 10 : 0,
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

	public function get_relationships( $object_id, $taxonomies ) {

		foreach ( $taxonomies as $key => $taxonomy ) {
			$taxonomies[ $taxonomy ] = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'names' ) );
			unset( $taxonomies[ $key ] );
		}

		return $taxonomies;

	}

	/**
	 * Get the total number of objects to sync to another server
	 * @return integer	$total_objects
	 */
	public function count_objects_to_sync( $objects_to_sync ) {

		if ( 'user' == $objects_to_sync) {
			return $this->count_users_to_sync();
		}

		global $wpdb;

		$sql = "SELECT count(*) FROM $wpdb->posts WHERE post_type = %s";
		$prepared_sql = $wpdb->prepare( $sql, $objects_to_sync );

		$total_objects = $wpdb->get_var( $prepared_sql );

		return $total_objects;

	}

	public function count_users_to_sync() {
		$result = count_users();
		return $result['total_users'];
	}

	public function prepare_post_args_to_sync( $object_args ) {

		foreach ( $object_args['meta_input'] as $meta_key => $meta_value ) {
 			$object_args['meta_input'][ $meta_key ] = is_array( $meta_value ) ? $meta_value[0] : $meta_value;
		}

		$object_args = $this->update_links( $object_args );

		$object_args = apply_filters( 'press_sync_prepare_post_args_to_sync', $object_args );

		// Send Featured image information along to be imported
		$object_args['featured_image'] = $this->get_featured_image( $object_args['ID'] );

		unset( $object_args['ID'] );

		return $object_args;

	}

	public function prepare_user_args_to_sync( $user_args ) {

		// Remove the user password
		$user_args['user_pass'] = NULL;

		return $user_args;
	}

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

	public function prepare_woo_order_args_to_sync( $object_args ) {

		if ( 'shop_order' != $object_args['post_type'] ) {
			return $object_args;
		}

		// Get Order Items
		global $wpdb;

		$press_sync_post_id = isset( $object_args['meta_input']['press_sync_post_id'] ) ? $object_args['meta_input']['press_sync_post_id'] : 0;
		$order_items_table = $wpdb->prefix . 'woocommerce_order_items';
		$order_itemmeta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';

		$sql = "SELECT * FROM $order_items_table WHERE order_id = $press_sync_post_id";
		$order_items = $wpdb->get_results( $sql, ARRAY_A );

		$object_args['meta_input']['_woocommerce_order_items'] = $order_items;

		foreach ( $order_items as $order_item ) {

			$sql = "SELECT * FROM $order_itemmeta_table WHERE order_item_id = %d";
			$prepared_sql = $wpdb->prepare( $sql, $order_item['order_item_id'] );

			$order_itemmeta = $wpdb->get_results( $prepared_sql, ARRAY_A );

			$object_args['meta_input']['_woocommerce_order_itemmeta'][ $order_item['order_item_id'] ] = $order_itemmeta;

		}

		return $object_args;

	}

	public function send_data_to_remote_server( $url, $args ) {

		$args = array(
			'timeout'	=> 30,
			'body'	=> $args,
		);

		$response 	= wp_remote_post( $url, $args );
		$body 		= wp_remote_retrieve_body( $response );

	}

	public function insert_woo_order_items( $post_id, $post_args ) {

		if ( 'shop_order' != $post_args['post_type'] ) {
			return;
		}

		if ( ! isset( $post_args['meta_input']['_woocommerce_order_items'] ) || empty( $post_args['meta_input']['_woocommerce_order_items'] ) ) {
			return;
		}

		foreach ( $post_args['meta_input']['_woocommerce_order_items'] as $order_item ) {

			// Get the product by the original ID
			$order_id = $this->get_post_by_orig_id( $order_item['order_id'] );
			$order['order_item_name'] = $order_item['order_item_name'];
			$order['order_item_type'] = $order_item['order_item_type'];

			$order_item_id = wc_add_order_item( $order_id, $order );

			$order_itemmeta = isset( $post_args['meta_input']['_woocommerce_order_itemmeta'][ $order_item['order_item_id'] ] ) ? $post_args['meta_input']['_woocommerce_order_itemmeta'][ $order_item['order_item_id'] ] : array();

			if ( ! $order_itemmeta ) {
				continue;
			}

			foreach ( $order_itemmeta as $itemmeta ) {

				$result = wc_add_order_item_meta( $order_item_id, $itemmeta['meta_key'], $itemmeta['meta_value'] );

			}

		}

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
