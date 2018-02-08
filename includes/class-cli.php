<?php

namespace Press_Sync;

use Press_Sync\client\cli\AbstractCliCommand;
use Press_Sync\client\cli\command\Validate;

/**
 * CLI Support for Press Sync.
 *
 * @since 0.1.0
 */
class CLI {

	/**
	 * Parent plugin class.
	 *
	 * @var   Press_Sync
	 * @since 0.1.0
	 */
	protected $plugin = null;

	/**
	 * Commands registered to this plugin.
	 *
	 * @TODO Refactor everything currently into the constructor into standalone classes and add them to this array.
	 *
	 * @var array
	 * @since NEXT
	 */
	protected $commands = array(
		Validate::class,
	);

	/**
	 * The constructor.
	 *
	 * @param Press_Sync $plugin The Press Sync plugin.
	 */
	public function __construct( Press_Sync $plugin ) {

		$this->plugin = $plugin;

		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		// Register the CLI Commands.
		\WP_CLI::add_command( 'press-sync all', array( $this, 'sync_all' ) );
		\WP_CLI::add_command( 'press-sync posts', array( $this, 'sync_posts' ) );
		\WP_CLI::add_command( 'press-sync media', array( $this, 'sync_media' ) );
		\WP_CLI::add_command( 'press-sync pages', array( $this, 'sync_pages' ) );
		\WP_CLI::add_command( 'press-sync users', array( $this, 'sync_users' ) );
		\WP_CLI::add_command( 'press-sync options', array( $this, 'sync_options' ) );
	}

	/**
	 * Initialize concrete instances of AbstractCliCommand objects and register those commands w/ WP-CLI.
	 *
	 * @since NEXT
	 */
	public function init_commands() {
		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		foreach ( $this->commands as $command ) {
			/* @var AbstractCliCommand $class */
			$class = new $command();
			$class->register_command();
		}
	}

	/**
	 * Synchronize ALL content.
	 *
	 * @since 0.6.1
	 *
	 * @param array $args       The arguments.
	 * @param array $assoc_args The associative arugments.
	 *
	 * @synopsis [--remote_domain=<remote_domain>] [--remote_press_sync_key=<remote_press_sync_key>] [--local_folder=<local_folder>]
	 */
	public function sync_all( $args, $assoc_args ) {

		// Get all of the objects to sync in the order that we need them.
		$order_to_sync_all = apply_filters( 'press_sync_order_to_sync_all', array() );

		foreach ( $order_to_sync_all as $wp_object ) {
			$assoc_args['objects_to_sync'] = $wp_object;
			$response = $this->plugin->sync_object( $wp_object, $assoc_args, 1, false, true );
			$this->return_response( $response );
		}

		\WP_CLI::line( 'Syncing of all objects is complete.' );
	}

	/**
	 * Synchronize posts.
	 *
	 * @param array $args       The arguments.
	 * @param array $assoc_args The associative arugments.
	 *
	 * @synopsis [--remote_domain=<remote_domain>] [--remote_press_sync_key=<remote_press_sync_key>] [--local_folder=<local_folder>]
	 */
	public function sync_posts( $args, $assoc_args ) {

		$response = $this->plugin->sync_object( 'post', $assoc_args, 1, false, true );

		$this->return_response( $response );
	}

	/**
	 * Synchronize media.
	 *
	 * @param array $args       The arguments.
	 * @param array $assoc_args The associative arugments.
	 *
	 * @synopsis [--remote_domain=<remote_domain>] [--remote_press_sync_key=<remote_press_sync_key>] [--local_folder=<local_folder>]
	 */
	public function sync_media( $args, $assoc_args ) {

		$response = $this->plugin->sync_object( 'attachment', $assoc_args, 1, false, true );

		$this->return_response( $response );
	}

	/**
	 * Synchronize pages.
	 *
	 * @param array $args       The arguments.
	 * @param array $assoc_args The associative arugments.
	 *
	 * @synopsis [--remote_domain=<remote_domain>] [--remote_press_sync_key=<remote_press_sync_key>] [--local_folder=<local_folder>]
	 */
	public function sync_pages( $args, $assoc_args ) {

		$response = $this->plugin->sync_object( 'page', $assoc_args, 1, false, true );

		$this->return_response( $response );
	}

	/**
	 * Synchronize users.
	 *
	 * @param array $args       The arguments.
	 * @param array $assoc_args The associative arugments.
	 *
	 * @synopsis [--remote_domain=<remote_domain>] [--remote_press_sync_key=<remote_press_sync_key>] [--local_folder=<local_folder>]
	 */
	public function sync_users( $args, $assoc_args ) {

		$response = $this->plugin->sync_object( 'user', $assoc_args, 1, false, true );

		$this->return_response( $response );
	}

	/**
	 * Synchronize users.
	 *
	 * @param array $args       The arguments.
	 * @param array $assoc_args The associative arugments.
	 *
	 * @synopsis [--remote_domain=<remote_domain>] [--remote_press_sync_key=<remote_press_sync_key>] [--options=<options>] [--local_folder=<local_folder>]
	 */
	public function sync_options( $args, $assoc_args ) {

		$this->plugin->prepare_options( $assoc_args['options'] );

		$response = $this->plugin->sync_content( 'option', $assoc_args, 1, false, true );

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

		\WP_CLI::line( '' ); // Insert a blank line.

		if ( $total_objects === $total_objects_processed ) {
			\WP_CLI::success( 'Successfully synced ' . $total_objects . ' objects.' );
		} else {
			\WP_CLI::error( 'There was a porblem. All of the content didn\'t sync.' );
		}
	}
}
