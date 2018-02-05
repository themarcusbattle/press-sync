<?php
namespace Press_Sync\api\route;

/**
 * Class AbstractRoute
 *
 * @package Press_Sync\api\route
 */
/**
 * Class AbstractRoute
 *
 * @package Press_Sync\api\route
 */
abstract class AbstractRoute extends \WP_REST_Controller {
	/**
	 * AbstractRoute constructor.
	 */
	public function __construct() {
		$this->namespace = 'press-sync/v1';
	}

	/**
	 *
	 */
	public function register_hooks() {
	}
}
