<?php
/**
 * Controller for Single Object Sync
 */
class Press_Sync_Post_Sync {

	/**
	 * Parent plugin class.
	 *
	 * @var   Press_Sync
	 * @since 0.2.0
	 */
	protected $plugin = null;

	/**
	 * Prefix for meta keys
	 *
	 * @var string
	 * @since  0.2.0
	 */
	protected $prefix = 'press_sync_post_sync_';

	/**
	 * Constructor.
	 *
	 * @since  0.2.0
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
	 * @since  0.2.0
	 */
	public function hooks() {
		add_action( 'cmb2_admin_init', array( $this, 'init_press_sync_post_metabox' ) );
		add_action( 'cmb2_render_post_sync_status', array( $this, 'cmb2_render_callback_for_post_sync_status' ), 10, 5 );
	}

	/**
	 * Initializes the CMB2 metabox for post sync
	 *
	 * @since 0.2.0
	 */
	public function init_press_sync_post_metabox() {

		$metabox = new_cmb2_box( array(
			'id'      => $this->prefix . 'metabox',
			'title'   => __( 'Sync', 'press-sync' ),
			'object_types'  => array( 'post', 'page' ),
			'context'    => 'side',
			'priority'	=> 'high'
		) );

		$metabox->add_field( array(
			'id'      => 'sync_status',
			'type'    => 'post_sync_status',
		) );

	}

	/**
	 * Display the field for post sync status
	 *
	 * @since 0.2.0
	 *
	 * @return html
	 */
	public function cmb2_render_callback_for_post_sync_status( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {

		$post_sync_status = $this->check_post_sync_status( $object_id );

		if ( 'not synced' == $post_sync_status['status'] ) {
			
			echo sprintf(
				'<div id="post-sync-message">%s</div>',
				__( 'This post does not exist on the remote server.', 'press-sync' )
			);
			echo '<p style="text-align: center;"><a class="button" data-sync-method="push" data-post-id="'. $object_id .'" id="post-sync-button">Sync local -> remote</a></p>';

		} else if ( 'synced' == $post_sync_status['status'] ) {

			$local_post_modified = $this->plugin->get_press_sync_time( get_the_modified_date('Y-m-d H:i:s') );
			$remote_post_modified = $post_sync_status['remote_post_modified_offset'];

			$status = ( strtotime( $local_post_modified ) > strtotime( $remote_post_modified ) ) ? 'newer' : 'older';

			echo sprintf(
				'<div id="post-sync-message" style="text-align: center;">%s</div>',
				__( "This post is $status than the remote version.", 'press-sync' )
			);

			if ( 'newer' == $status ) {
				echo '<p style="text-align: center;"><a class="button" data-sync-method="push" data-post-id="'. $object_id .'" id="post-sync-button">Sync local -> remote</a></p>';
			} else {
				echo '<p style="text-align: center;"><a class="button" data-sync-method="pull" data-post-id="'. $object_id .'" id="post-sync-button">Sync local <- remote</a></p>';
			}

		}

	}

	/**
	 * Checks the post sync status on the remote server
	 *
	 * @since 0.2.0
	 *
	 * @param integer $post_id
	 * @return JSON
	 */
	public function check_post_sync_status( $post_id = 0 ) {

		if ( ! $post_id ) {
			return;
		}

		// Build out the url
		$url 			= cmb2_get_option( 'press-sync-options', 'connected_server' );
		$press_sync_key = cmb2_get_option( 'press-sync-options', 'remote_press_sync_key' );
		$url			= untrailingslashit( $url ) . "/wp-json/press-sync/v1/status/$post_id/?press_sync_key=" . $press_sync_key;

		$response = $this->plugin->send_data_to_remote_server( $url, $args, 'get' );
		$response = json_decode( $response, true );

		return $response;

	}

}
