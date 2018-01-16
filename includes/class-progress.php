<?php

namespace Press_Sync;

/**
 * Progress Loader for Press Sync.
 *
 * @since 0.1.2
 */
class Progress {

	/**
	 * Parent plugin class.
	 *
	 * @var   Press_Sync
	 * @since 0.1.0
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Press_Sync $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin      = $plugin;
		$this->cli_enabled = class_exists( '\WP_CLI' );
	}

	/**
	 * Begin the progress indication.
	 *
	 * @param string  $label The label to supply to the progress indicator.
	 * @param integer $count The total number of items that will be processed.
	 */
	public function start( $label = 'Syncing content', $count = 0 ) {

		if ( ! $this->cli_enabled ) {
			return;
		}

		$this->progress = \WP_CLI\Utils\make_progress_bar( $label, $count );
	}

	/**
	 * Update the progress that some objects have been processed.
	 */
	public function tick() {

		if ( ! $this->cli_enabled ) {
			return;
		}

		$this->progress->tick();
	}

	/**
	 * Updates the progress that the process is complete.
	 */
	public function finish() {

		if ( ! $this->cli_enabled ) {
			return;
		}

		$this->progress->finish();
	}
}
