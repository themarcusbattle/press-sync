<?php

class Press_Sync_API_Validator {
	/**
	 * Validate the supplied press_sync_key by the sending server.
	 * Target server can't receive data without a valid press_sync_key.
	 *
	 * @since 0.1.0
	 */
	public function validate_sync_key() {

		$press_sync_key_from_remote = isset( $_REQUEST['press_sync_key'] ) ? $_REQUEST['press_sync_key'] : '';
		$press_sync_key = $this->plugin->press_sync_option('press_sync_key');

		if ( ! $press_sync_key || ( $press_sync_key_from_remote != $press_sync_key ) ) {
			return false;
		}

		return true;

	}
}
