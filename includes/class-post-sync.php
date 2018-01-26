<?php
/**
 * Press Sync Plugin
 *
 * @package PressSync
 */

namespace Press_Sync;

/**
 * The Post_Sync class.
 *
 * @since 0.7.3.1
 */
class Post_Sync {

	/**
	 * The constructor.
	 *
	 * @since 0.7.3.1
	 *
	 * @param Press_Sync $plugin The Press Sync plugin.
	 */
	public function __construct( Press_Sync $plugin ) {

		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Add the hooks.
	 *
	 * @since 0.7.3.1
	 */
	public function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'register_post_sync_meta_box' ) );
	}

	/**
	 * Register the meta box to display the Post Sync.
	 *
	 * @since 0.7.3.1
	 */
	public function register_post_sync_meta_box() {
		add_meta_box( 'post-sync', __( 'Post Sync', 'press-sync' ), array( $this, 'display_post_sync_meta_box' ), array( 'post' ), 'side', 'high' );
	}

	/**
	 * The HTML output for the Post Sync metabox.
	 *
	 * @since 0.7.3.1
	 */
	public function display_post_sync_meta_box() {

		// Check to see if the site is connected. If so, check to see if the post is in sync.
		if ( $this->plugin->check_connection() ) :

			$sync_status = $this->get_post_sync_status();

			if ( ! $sync_status ) {
				esc_html_e( 'There is a problem with this post on your remote site. It may have been deleted. Please check to make sure.', 'press-sync' );
			} else {
				esc_html_e( 'Status!', 'press-sync' );
			}
			?>
			<a class="button press-sync">Sync</a>
		<?php else : ?>
		<p><?php esc_html_e( 'Press Sync is not connected.', 'press-sync' ); ?><a href=""><?php esc_html_e( 'Check your credentials.', 'press-sync' ); ?></a></p>
		<?php
			endif;
	}

	/**
	 * Get the individual Post 
	 */
	public function get_post_sync_status() {

		$press_sync_post_id = get_post_meta( get_the_ID(), 'press_sync_post_id', true );
		$press_sync_source  = get_post_meta( get_the_ID(), 'press_sync_source', true );

		if ( ! $press_sync_post_id || ! $press_sync_source ) {
			return false;
		}

		$api_url = trailingslashit( $press_sync_source ) . 'wp-json/press-sync/v1/status/' . get_post_meta( get_the_ID(), 'press_sync_post_id', true );

		$response = wp_remote_get( $api_url, array( 
			'timeout' => 30
		) );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), 1 );

		return $data;
	}
}
