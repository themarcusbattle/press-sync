<?php

namespace Press_Sync;

/**
 * The Dashboard.
 *
 * @since 0.1.0
 */
class Dashboard {

	/**
	 * Parent plugin class.
	 *
	 * @var   Press_Sync
	 * @since 0.1.0
	 */
	protected $plugin = null;

	/**
	 * The Constructor.
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

		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'admin_notices', array( $this, 'error_notice' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// AJAX Requests.
		add_action( 'wp_ajax_get_objects_to_sync_count', array( $this, 'get_objects_to_sync_count_via_ajax' ) );
		add_action( 'wp_ajax_sync_wp_data', array( $this, 'sync_wp_data_via_ajax' ) );

	}

	/**
	 * Initialize the Press Sync menu page.
	 *
	 * @since 0.1.0
	 */
	public function add_menu_page() {
		add_management_page( __( 'Press Sync','press-sync' ), __( 'Press Sync &trade;','press-sync' ), 'manage_options', 'press-sync', array( $this, 'show_menu_page' ) );
	}

	/**
	 * Display the menu page in the 'Tools' section.
	 *
	 * @since 0.1.0
	 */
	public function show_menu_page() {

		$selected_tab = isset( $_REQUEST['tab'] ) ? 'dashboard/' . $_REQUEST['tab'] : 'dashboard/dashboard';
		$this->plugin->include_page( $selected_tab );
	}

	/**
	 * Load all of the scripts for the dashboard.
	 *
	 * @since 0.1.0
	 */
	public function load_scripts() {
		wp_enqueue_script( 'press-sync', plugins_url( 'assets/js/press-sync.js', dirname( __FILE__ ) ), true );
		wp_localize_script( 'press-sync', 'press_sync', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	/**
	 * Registers teh Press Sync settings via WP.
	 *
	 * @since 0.3.0
	 */
	public function register_settings() {

		// Post Sync.
		register_setting( 'press-sync', 'ps_allowed_post_types' );

		register_setting( 'press-sync-bulk-sync', 'ps_sync_method' );
		register_setting( 'press-sync-bulk-sync', 'ps_objects_to_sync' );
		register_setting( 'press-sync-bulk-sync', 'ps_options_to_sync' );
		register_setting( 'press-sync-bulk-sync', 'ps_duplicate_action' );
		register_setting( 'press-sync-bulk-sync', 'ps_force_update' );
		register_setting( 'press-sync-bulk-sync', 'ps_ignore_comments' );

		// Credentials page.
		register_setting( 'press-sync', 'ps_key' );
		register_setting( 'press-sync', 'ps_remote_domain' );
		register_setting( 'press-sync', 'ps_remote_query_args' );
		register_setting( 'press-sync', 'ps_remote_key' );

        // @TODO update option names and locations below this line.
        // Export page.
		register_setting( 'press-sync-export', 'ps_options' );
		register_setting( 'press-sync-export', 'ps_ignore_comments' );
		register_setting( 'press-sync-export', 'ps_request_buffer_time' );
		register_setting( 'press-sync-export', 'ps_start_object_offset' );
		register_setting( 'press-sync-export', 'ps_only_sync_missing' );
        register_setting( 'press-sync-export', 'ps_testing_post' );
		register_setting( 'press-sync-export', 'ps_skip_assets' );

        // Import page.
        register_setting( 'press-sync-import', 'ps_content_threshold' );
	}

	/**
	 * Displays an error notice if the local press sync key is not set.
	 *
	 * @since 0.1.0
	 */
	public function error_notice() {

		$press_sync_key = get_option( 'ps_key' );

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
	 * Get the total number of objects to sync
	 *
	 * @since 0.1.0
	 *
	 * @return JSON
	 */
	public function get_objects_to_sync_count_via_ajax() {

		$this->plugin->prepare_options( get_option( 'ps_options_to_sync' ) );

		$objects_to_sync = get_option( 'ps_objects_to_sync' );
		$prepare_object  = ! in_array( $objects_to_sync, array( 'attachment', 'comment', 'user', 'options' ) ) ? 'post' : $objects_to_sync;
		$total_objects   = $this->plugin->count_objects_to_sync( $objects_to_sync );

		$wp_object       = in_array( $objects_to_sync, array( 'attachment', 'comment', 'user' ) ) ? ucwords( $objects_to_sync ) . 's' : get_post_type_object( $objects_to_sync );
		$wp_object       = isset( $wp_object->labels->name ) ? $wp_object->labels->name : $wp_object;

		wp_send_json_success( array(
			'objects_to_sync'  => $wp_object,
			'total_objects'    => $total_objects,
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

		$this->plugin->prepare_options( get_option( 'ps_options_to_sync' ) );

		$objects_to_sync = get_option( 'ps_objects_to_sync' );

		wp_send_json_success( $this->plugin->sync_batch( $objects_to_sync ) );
	}

}
