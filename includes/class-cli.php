<?php
/**
 * CLI Support for Press Sync.
 *
 * @since 0.1.0
 */
class Press_Sync_CLI {

	/**
	 * Parent plugin class.
	 *
	 * @var   Press_Sync
	 * @since 0.1.0
	 */
	protected $plugin = null;

	/**
	 * The constructor.
	 *
	 * @param Press_Sync $plugin The Press Sync plugin.
	 */
	public function __construct( Press_Sync $plugin ) {

		$this->plugin = $plugin;

		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		// Register the CLI Commands.
		WP_CLI::add_command( 'press-sync posts', array( $this, 'sync_posts' ) );
		WP_CLI::add_command( 'press-sync media', array( $this, 'sync_media' ) );
		WP_CLI::add_command( 'press-sync pages', array( $this, 'sync_pages' ) );
		WP_CLI::add_command( 'press-sync users', array( $this, 'sync_users' ) );
	}

	/**
	 * Synchronize posts.
	 *
	 * @param array $args       The arguments.
	 * @param array $assoc_args The associative arugments.
	 *
	 * @synopsis --remote_domain=<remote_domain> --remote_press_sync_key=<remote_press_sync_key>
	 */
	public function sync_posts( $args, $assoc_args ) {
		$response = $this->plugin->sync_content( 'post', $assoc_args );
	}

	/**
	 * Synchronize media.
	 *
	 * @param array $args       The arguments.
	 * @param array $assoc_args The associative arugments.
	 *
	 * @synopsis --remote_domain=<remote_domain> --remote_press_sync_key=<remote_press_sync_key>
	 */
	public function sync_media( $args, $assoc_args ) {
		$response = $this->plugin->sync_content( 'media', $assoc_args );
	}

	/**
	 * Synchronize pages.
	 *
	 * @param array $args       The arguments.
	 * @param array $assoc_args The associative arugments.
	 *
	 * @synopsis --remote_domain=<remote_domain> --remote_press_sync_key=<remote_press_sync_key>
	 */
	public function sync_pages( $args, $assoc_args ) {
		$response = $this->plugin->sync_content( 'page', $assoc_args );
	}

	/**
	 * Synchronize users.
	 *
	 * @param array $args       The arguments.
	 * @param array $assoc_args The associative arugments.
	 *
	 * @synopsis --remote_domain=<remote_domain> --remote_press_sync_key=<remote_press_sync_key>
	 */
	public function sync_users( $args, $assoc_args ) {
		$response = $this->plugin->sync_content( 'user', $assoc_args );
	}
}
