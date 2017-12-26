<?php

namespace VMN\GEG\PressSync;

/**
 * CLI Support for Press Sync.
 *
 * @since 0.1.0
 */
class CLI {

	/**
	 * Parent plugin class.
	 *
	 * @var   PressSyncPlugin
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
		WP_CLI::add_command( 'press-sync options', array( $this, 'sync_options' ) );
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

		$response = $this->plugin->sync_content( 'post', $assoc_args, true );

		$this->return_response( $response );
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

		$this->return_response( $response );
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

		$this->return_response( $response );
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

		$this->return_response( $response );
	}

	/**
	 * Synchronize users.
	 *
	 * @param array $args       The arguments.
	 * @param array $assoc_args The associative arugments.
	 *
	 * @synopsis --remote_domain=<remote_domain> --remote_press_sync_key=<remote_press_sync_key> --options=<options>
	 */
	public function sync_options( $args, $assoc_args ) {

		$this->plugin->prepare_options( $assoc_args['options'] );

		$response = $this->plugin->sync_content( 'options', $assoc_args );

		$this->return_response( $response );
	}

	/**
	 * Return the response after synchronizing content.
	 *
	 * @param array $response The response after synchronization.
	 */
	private function return_response( $response = array() ) {

		$total_objects            = isset( $response['total_objects'] ) ? (int) $response['total_objects'] : 0;
		$total_objects_processed  = isset( $response['total_objects_processed'] ) ? (int) $response['total_objects_processed'] : 0;

		WP_CLI::line( '' ); // Insert a blank line.

		if ( $total_objects === $total_objects_processed ) {
			WP_CLI::success( 'Successfully synced ' . $total_objects . ' objects.' );
		} else {
			WP_CLI::error( 'There was a porblem. All of the content didn\'t sync.' );
		}
	}
}
