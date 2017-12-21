<?php

class Press_Sync_Dashboard {

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
	protected $prefix = 'press_sync_dashboard_';

	/**
	 * Constructor.
	 *
	 * @since  0.1.0
	 *
	 * @param  WDS_Fordham_Library_Calendar $plugin Main plugin object.
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

		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'admin_notices', array( $this, 'error_notice' ) );

		// CMB2 hooks
		add_action( 'cmb2_admin_init', array( $this, 'init_press_sync_dashboard_metabox' ) );
		add_action( 'cmb2_admin_init', array( $this, 'init_press_sync_settings_metabox' ) );
		add_action( 'cmb2_render_connection_status', array( $this, 'render_connection_status_field' ), 10, 5 );

		// AJAX Requests
		add_action( 'wp_ajax_get_objects_to_sync_count', array( $this, 'get_objects_to_sync_count_via_ajax' ) );
		add_action( 'wp_ajax_sync_wp_data', array( $this, 'sync_wp_data_via_ajax' ) );

	}

	/**
	 * Initialize the menu page
	 *
	 * @since 0.1.0
	 */
	public function add_menu_page() {
		add_management_page( __( 'Press Sync','press-sync' ), __( 'Press Sync','press-sync' ), 'manage_options', 'press-sync', array( $this, 'show_menu_page' ) );
	}

	/**
	 * Display the menu page in the 'Tools' section
	 *
	 * @since 0.1.0
	 */
	public function show_menu_page() {
		$selected_tab 	= isset( $_REQUEST['tab'] ) ? 'dashboard/' . $_REQUEST['tab'] : 'dashboard';
		$this->plugin->include_page( $selected_tab );
	}

	/**
	 * Load all of the scripts for the dashboard
	 *
	 * @since 0.1.0
	 */
	public function load_scripts() {
		wp_enqueue_script( 'press-sync', plugins_url( 'assets/js/press-sync.js', dirname( __FILE__ ) ), true );
		wp_localize_script( 'press-sync', 'press_sync', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	/**
	 * Initialize the CMB2 metabox for "Dashboard" tab in the dashboard
	 *
	 * @since 0.1.0
	 */
	public function init_press_sync_dashboard_metabox() {

		$cmb_options = new_cmb2_box( array(
			'id'      => $this->prefix . 'metabox',
			'title'   => __( 'Press Sync Metabox', 'press-sync' ),
			'hookup'  => false, // Do not need the normal user/post hookup
			'show_on' => array(
				// These are important, don't remove
				'key'   => 'options-page',
				'value' => array( 'press_sync_options' )
			),
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Remote Server', 'press-sync' ),
			'id'      => 'connected_server',
			'type'    => 'text',
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Remote Press Sync Key', 'press-sync' ),
			'id'      => 'remote_press_sync_key',
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
			'name'       => __( 'Objects to Sync', 'press-sync' ),
			'id'         => 'objects_to_sync',
			'type'       => 'select',
			'options_cb' => array( $this, 'objects_to_sync' )
		) );

		$cmb_options->add_field( array(
			'name'       => __( 'How do you want to handle non-synced duplicates?', 'press-sync' ),
			'id'         => 'duplicate_action',
			'type'       => 'select',
			'options'	 => array(
				'sync'		=> __( 'Sync', 'press-sync' ),
				'skip'		=> __( 'Skip (Creates a duplicate post)', 'press-sync' ),
			),
			'desc'		=> __( 'The sync option will give a non-synced duplicate a press sync ID to be synced for the future', 'press-sync' ),
			'default'	=> 'match'
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Force update?', 'press-sync' ),
			'id'      => 'force_update',
			'type'    => 'checkbox',
			'desc'	 => __( 'Force the content on the remote server to be overwritten when the sync method is "push"', 'press-sync' ),
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Ignore comments?', 'press-sync' ),
			'id'      => 'ignore_comments',
			'type'    => 'checkbox',
			'desc'	 => __( 'Checking this box ommits comments from being synced to the remote server', 'press-sync' ),
		) );

	}

	/**
	 * Initializes the CMB2 metabox for "Settings" tab in the dashboard
	 *
	 * @since 0.1.0
	 */
	public function init_press_sync_settings_metabox() {

		$prefix = $this->prefix . 'settings_';

		$cmb_options = new_cmb2_box( array(
			'id'      => $prefix . 'metabox',
			'title'   => __( 'Press Sync Settings', 'press-sync' ),
			'hookup'  => false, // Do not need the normal user/post hookup
			'show_on' => array(
				// These are important, don't remove
				'key'   => 'options-page',
				'value' => array( 'press_sync_options' )
			),
		) );

		$cmb_options->add_field( array(
			'name'  => __( 'Sync Key', 'press-sync' ),
			'id'    => 'press_sync_key',
			'desc'	=> __( 'This secure key is used to authenticate requests to your site. Without it, press sync won\'t work.','press-sync' ),
			'type'	=> 'text',
		) );

	}

	public function error_notice() {

		$press_sync_key = $this->plugin->press_sync_option('press_sync_key');

		if ( $press_sync_key ) {
			return;
		}

		?>
	    <div class="update-nag notice is-dismissable">
	        <p><?php _e( '<strong>PressSync:</strong> You must define your PressSync key before you can recieve updates from another WordPress site. <a href="tools.php?page=press-sync&tab=settings">Set it now</a>', 'press-sync' ); ?></p>
	    </div>
	    <?php
	}

	/**
	 * Prepares a list of WP Objects to sync.
	 *
	 * @since 0.1.0
	 *
	 * @return array $objects A list of WP objects.
	 */
	public function objects_to_sync() {

		$objects = array(
			'attachment' => __( 'Media', 'press-sync' ),
			'page'       => __( 'Pages', 'press-sync' ),
			'post'       => __( 'Posts', 'press-sync' ),
			'user'       => __( 'Users', 'press-sync' ),
			'options'    => __( 'Options', 'press-sync' ),
		);

		$custom_post_types = get_post_types( array( '_builtin' => false ), 'objects' );

		if ( $custom_post_types ) {

			$objects[] = '-- Custom Post Types --';

			foreach ( $custom_post_types as $cpt ) {
				$objects[ $cpt->name ] = $cpt->label;
			}
		}

		$objects = apply_filters( 'press_sync_objects_to_sync', $objects );

		return $objects;

	}

	/**
	 * Render custom CMB2 field for the connection status to the remote server
	 *
	 * @since 0.1.0
	 * @return html
	 */
	public function render_connection_status_field( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {

		$url = cmb2_get_option( 'press-sync-options', 'connected_server' );

		$is_connected = $this->plugin->check_connection( $url );

		if ( $is_connected ) {
			echo "<div><p>Connected</p></div>";
		} else {
			echo "<div><p>Not Connected</p></div>";
		}

	}

	/**
	 * Get the total number of objects to sync
	 *
	 * @since 0.1.0
	 *
	 * @return JSON
	 */
	public function get_objects_to_sync_count_via_ajax() {

		$objects_to_sync 	= cmb2_get_option( 'press-sync-options', 'objects_to_sync' );
		$prepare_object 	= ! in_array( $objects_to_sync, array( 'attachment', 'comment', 'user' ) ) ? 'post' : $objects_to_sync;

		$total_objects 	= $this->plugin->count_objects_to_sync( $objects_to_sync );

		$wp_object = in_array( $objects_to_sync, array( 'attachment', 'comment', 'user' ) ) ? ucwords( $objects_to_sync ) . 's' : get_post_type_object( $objects_to_sync );
		$wp_object = isset( $wp_object->labels->name ) ? $wp_object->labels->name : $wp_object;

		wp_send_json_success( array(
			'objects_to_sync'	=> $wp_object,
			'total_objects' 	=> $total_objects
		) );

	}

	/**
	 * Sync the data via AJAX
	 *
	 * @since 0.1.0
	 *
	 * @return JSON
	 */
	public function sync_wp_data_via_ajax() {

		$this->plugin->init_connection();

		$sync_method 		= cmb2_get_option( 'press-sync-options', 'sync_method' );
		$objects_to_sync 	= cmb2_get_option( 'press-sync-options', 'objects_to_sync' );
		$duplicate_action 	= cmb2_get_option( 'press-sync-options', 'duplicate_action' );
		$force_update 		= cmb2_get_option( 'press-sync-options', 'force_update' );

		$prepare_object = ! in_array( $objects_to_sync, array( 'attachment', 'comment', 'user' ) ) ? 'post' : $objects_to_sync;
		$wp_object = in_array( $objects_to_sync, array( 'attachment', 'comment', 'user' ) ) ? ucwords( $objects_to_sync ) . 's' : get_post_type_object( $objects_to_sync );
		$wp_object = isset( $wp_object->labels->name ) ? $wp_object->labels->name : $wp_object;

		// Build out the url
		$url 			= cmb2_get_option( 'press-sync-options', 'connected_server' );
		$press_sync_key = cmb2_get_option( 'press-sync-options', 'remote_press_sync_key' );
		$url			= untrailingslashit( $url ) . '/wp-json/press-sync/v1/sync?press_sync_key=' . $press_sync_key;

		// Prepare the correct sync method
		$sync_class 	= 'prepare_' . $prepare_object . '_args_to_sync';

		$total_objects 	= $this->plugin->count_objects_to_sync( $objects_to_sync );
		$taxonomies 	= get_object_taxonomies( $objects_to_sync );
		$paged 			= isset( $_POST['paged'] ) ? (int) $_POST['paged'] : 1;

		$objects 	= $this->plugin->get_objects_to_sync( $objects_to_sync, $paged, $taxonomies );
		$logs 		= array();

		// Prepare each object to be sent to the remote server
		foreach ( $objects as $key => $object ) {
			$objects[ $key ] = $this->plugin->$sync_class( $object );
		}

		// Prepare the remote request args
		$args['duplicate_action'] 	= $duplicate_action;
		$args['force_update']	 	= $force_update;
		$args['objects_to_sync'] 	= $prepare_object;
		$args['objects'] 			= $objects;

		$logs = $this->plugin->send_data_to_remote_server( $url, $args );

		wp_send_json_success( array(
			'objects_to_sync'			=> $wp_object,
			'total_objects'				=> $total_objects,
			'total_objects_processed'	=> count( $objects ) ? count( $objects ) * $paged : 5 * $paged,
			'next_page'					=> $paged + 1,
			'log'						=> $logs,
		) );

	}

}
