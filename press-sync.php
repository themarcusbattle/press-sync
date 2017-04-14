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

	public function __construct() {

		if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/third-party/CMB2/init.php' ) ) {
			file_exists( plugin_dir_path( __FILE__ ) . 'includes/third-party/CMB2/init.php' );
		}

	}

	public function hooks() {

		add_action( 'admin_menu', array( $this, 'admin_pages' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );

		// CMB2 Fields | Fields to save sync data
		add_action( 'cmb2_admin_init', array( $this, 'press_sync_metabox' ) );
		add_action( 'cmb2_render_connection_status', array( $this, 'render_connection_status_field' ), 10, 5 );
		add_action( 'cmb2_render_sync_button', array( $this, 'render_sync_button_field' ), 10, 5 );

		add_action( 'wp_ajax_sync_wp_data', array( $this, 'sync_wp_data_via_ajax' ) );

		add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );

		add_filter( 'press_sync_prepare_post_args_to_sync', array( $this, 'prepare_woo_order_args_to_sync' ), 10, 1 );

		add_action( 'press_sync_insert_new_post', array( $this, 'insert_woo_order_items' ), 10, 2 );

	}

	public function load_scripts() {

		wp_enqueue_script( 'press-sync', plugins_url( 'assets/js/press-sync.js', __FILE__ ), true );
		wp_localize_script( 'press-sync', 'press_sync', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

	}

	public function register_api_endpoints() {

		register_rest_route( 'press-sync/v1', '/post', array(
			'methods' => 'POST',
			'callback' => array( $this, 'insert_new_post' ),
		) );

		register_rest_route( 'press-sync/v1', '/page', array(
			'methods' => 'POST',
			'callback' => array( $this, 'insert_new_post' ),
		) );

		register_rest_route( 'press-sync/v1', '/attachment', array(
			'methods' => 'POST',
			'callback' => array( $this, 'insert_new_media' ),
		) );

		register_rest_route( 'press-sync/v1', '/user', array(
			'methods' => 'POST',
			'callback' => array( $this, 'insert_new_user' ),
		) );

	}

	public function admin_pages() {
		add_management_page( __( 'Press Sync','press-sync' ), __( 'Sync Site','press-sync' ), 'manage_options', 'press-sync', array( $this, 'show_press_sync_menu_page' ) );
	}

	public function show_press_sync_menu_page() {

		?>
		<div class="wrap cmb2-options-page">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( 'press_sync_metabox', 'press-sync-options' ); ?>
			<h2>Sync Data</h2>
			<button class="press-sync-button">Sync</button>
		</div>
		<?php

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
			'post' => 'Posts',
			'page' => 'Pages',
			'attachment' => 'Media',
			'user'	=> 'Users',
		);

		$custom_post_types = get_post_types( array( '_builtin' => false ), 'objects' );

		foreach ( $custom_post_types as $cpt ) {
			$objects[ $cpt->name ] = $cpt->label;
		}

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

	public function sync_wp_data_via_ajax() {

		$this->init_connection();

		$sync_method = cmb2_get_option( 'press-sync-options', 'sync_method' );
		$objects_to_sync = cmb2_get_option( 'press-sync-options', 'objects_to_sync' );

		$prepare_object = ! in_array( $objects_to_sync, array( 'attachment', 'comment', 'user' ) ) ? 'post' : $objects_to_sync;

		// Build out the url
		$url = cmb2_get_option( 'press-sync-options', 'connected_server' );
		$url = untrailingslashit( $url ) . '/wp-json/press-sync/v1/' . $prepare_object;

		// Prepare the correct sync method
		$sync_class 	= 'prepare_' . $prepare_object . '_args_to_sync';

		$total_objects 	= $this->count_objects_to_sync( $objects_to_sync );
		$taxonomies 	= get_object_taxonomies( $objects_to_sync );
		$paged 			= 1;

		while ( $objects = $this->get_objects_to_sync( $objects_to_sync, $paged, $taxonomies ) ) {

			foreach ( $objects as $object ) {
				$args = $this->$sync_class( $object );
				$this->send_data_to_remote_server( $url, $args );
			}

			$paged++;

		}

		wp_die();

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

		print_r( $body );
	}

	public function insert_new_post( $request ) {

		$post_args = $request->get_params();

		if ( $post = $this->post_exists( $post_args ) ) {

			// Attach featured image
			$this->attach_featured_image( $post['ID'], $post_args );

			// Check if the post has been modified
			if ( strtotime( $post_args['post_modified'] ) > strtotime( $post['post_modified'] ) ) {

				$post_args['ID'] = $post['ID'];

			} else {

				$data['id'] = $post['ID'];
				$data['message'] = 'post already exists';

				return wp_send_json_error( $data );

			}

		}

		// Check for post parent and update
		if ( isset( $post_args['post_parent'] ) && $post_parent_id = $post_args['post_parent'] ) {

			$post_parent_args['post_type'] = $post_args['post_type'];
			$post_parent_args['meta_input']['press_sync_post_id'] = $post_parent_id;

			$parent_post = $this->post_exists( $post_parent_args );

			$post_args['post_parent'] = ( $parent_post ) ? $parent_post['ID'] : 0;

		}

		$post_args['post_author'] = $this->get_press_sync_author_id( $post_args['post_author'] );

		$post_id = wp_insert_post( $post_args );

		if ( is_wp_error( $post_id ) ) {
			return wp_send_json_error();
		}

		// Set taxonomies for custom post type
		if ( ! in_array( $post_args['post_type'], array( 'post', 'page' ) ) ) {

			if ( isset( $post_args['tax_input'] ) ) {

				foreach ( $post_args['tax_input'] as $taxonomy => $terms )	{
					wp_set_object_terms( $post_id, $terms, $taxonomy, false );
				}

			}

		}

		// Attach featured image
		$this->attach_featured_image( $post_id, $post_args );

		// Run any secondary commands
		do_action( 'press_sync_insert_new_post', $post_id, $post_args );

		$data['id'] = $post_id;

		return wp_send_json_success( $data );

	}

	public function insert_new_media( $request, $return_local = false ) {

		$data['id'] = 0;

		require_once( ABSPATH . '/wp-admin/includes/image.php' );
	    require_once( ABSPATH . '/wp-admin/includes/file.php' );
	    require_once( ABSPATH . '/wp-admin/includes/media.php' );

	    $attachment_args = $request->get_params();
		$attachment_url = $attachment_args['attachment_url'];

		unset( $attachment_args['attachment_url'] );

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
	        return wp_send_json_error( $data );
	    }

		$attachment_id = media_handle_sideload( $file_array, 0, '', $attachment_args );

		// Check for handle sideload errors.
	    if ( is_wp_error( $attachment_id ) ) {
	        @unlink( $file_array['tmp_name'] );
	        return wp_send_json_error( $data );
	    }

	    $data['id'] = $attachment_id;

		return wp_send_json_success( $data );

	}

	public function post_exists( $post_args ) {

		$press_sync_post_id = isset( $post_args['meta_input']['press_sync_post_id'] ) ? $post_args['meta_input']['press_sync_post_id'] : 0;

		$query_args = array(
			'post_type' 		=> $post_args['post_type'],
			'posts_per_page' 	=> 1,
			'meta_key'			=> 'press_sync_post_id',
			'meta_value'		=> $press_sync_post_id,
			'post_status'		=> 'any',
		);

		$post = get_posts( $query_args );

		if ( $post ) {
			return (array) $post[0];
		}

		return false;

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

	public function insert_new_user( $request ) {

		$user_args = $request->get_params();
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
		$data['user_id'] = $user_id;

		return wp_send_json_success( $data );

	}

	public function get_post_by_orig_id( $press_sync_post_id ) {

		global $wpdb;

		$sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'press_sync_post_id' AND meta_value = $press_sync_post_id";

		return $wpdb->get_var( $sql );

	}

	public function update_links( $object_args ) {

		$post_content = isset( $object_args['post_content'] ) ? $object_args['post_content'] : '';

		if ( $post_content ) {

			$post_content = str_ireplace( $this->current_domain, $this->new_domain, $post_content );

			$object_args['post_content'] = $post_content;

		}

		return $object_args;

	}

	public function get_featured_image( $post_id ) {

		$thumbnail_id 				= get_post_meta( $post_id, '_thumbnail_id', true );

		if ( ! $thumbnail_id ) {
			return false;
		}

		$media 						= get_post( $thumbnail_id, ARRAY_A );
		$media['attachment_url'] 	= home_url( 'wp-content/uploads/' . get_post_meta( $thumbnail_id, '_wp_attached_file', true ) );

		return $media;

	}

	public function get_press_sync_author_id( $user_id ) {
		$args = array(
			'fields'		=> array('ID'),
			'meta_key'		=> 'press_sync_user_id',
			'meta_value'	=> $user_id
		);

		$user = get_users( $args );

		if ( $user ) {
			return $user[0]->ID;
		}

		return $user_id;

	}

	public function attach_featured_image( $post_id, $post_args ) {

		$request = new WP_REST_Request( 'POST' );
		$request->set_body_params( $post_args['featured_image'] );

		// Download the attachment
		$attachment 	= $this->insert_new_media( $request, true );
		$thumbnail_id 	= isset( $attachment['id'] ) ? $attachment['id'] : 0;

		$response = set_post_thumbnail( $post_id, $thumbnail_id );
		
	}

}


add_action( 'plugins_loaded', array( Press_Sync::init(), 'hooks' ), 10, 1 );
