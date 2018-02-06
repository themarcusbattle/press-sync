<?php
namespace Press_Sync\api\route;

/**
 * Class AbstractRoute
 *
 * @package Press_Sync\api\route
 * @since   NEXT
 */
abstract class AbstractRoute extends \WP_REST_Controller {
	/**
	 * Namespace for this route.
	 *
	 * @var string
	 * @since NEXT
	 */
	protected $namespace = 'press-sync/v1';

	/**
	 * Concrete classes should implement methods that hook into WordPress's rest_api_init event, at minimum.
	 *
	 * @since NEXT
	 * @return void
	 */
	abstract public function register_hooks();

	/**
	 * Validate the supplied press_sync_key by the sending site.
	 * Target site can't receive data without a valid press_sync_key.
	 *
	 * @since 0.1.0
	 * @return bool
	 */
	public function validate_sync_key() {
		// @TODO Check for valid nonce.
		$press_sync_key_from_remote = isset( $_REQUEST['press_sync_key'] ) ? filter_var( $_REQUEST['press_sync_key'], FILTER_SANITIZE_STRING ) : '';
		$press_sync_key             = get_option( 'ps_key' );

		return $press_sync_key && ( $press_sync_key_from_remote === $press_sync_key );
	}
}
