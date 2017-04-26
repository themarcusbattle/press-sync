<?php

/**
 * Class Press_Sync_API_Validator
 */
class Press_Sync_API_Validator {
	/**
	 * Press_Sync_API_Validator constructor.
	 *
	 * @param Press_Sync $plugin
	 */
	public function __construct( Press_Sync $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Validate the supplied press_sync_key by the sending server.
	 * Target server can't receive data without a valid press_sync_key.
	 *
	 * @since 0.1.0
	 */
	public function validate_sync_key() {
		$remote_key = filter_input( $this->get_request_type(), 'press_sync_key', FILTER_SANITIZE_STRING );
		$local_key  = $this->plugin->press_sync_option( 'press_sync_key' );

		return $local_key && $local_key === $remote_key;
	}

	/**
	 * Get the press_sync_key request type.
	 *
	 * @return int
	 */
	public function get_request_type() {
		return isset( $_POST['press_sync_key'] ) ? INPUT_POST : INPUT_GET; // @codingStandardsIgnoreLine
	}
}
