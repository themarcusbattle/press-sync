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
	 * Routes to register with the WP API.
	 *
	 * This should be an array where the keys are endpoints to be registered
	 * under {$this->namspace}/{$this->rest_base}/{$endpoint}. If you don't
	 * specify an endpoint, the route will point to the REST base.
	 *
	 * Example:
	 * ```
	 * $this->namespace      = 'filemanager/v1';
	 * $this->rest_base      = 'files';
	 * $this->routes[]       = [ 'callback'=> [ $this, 'get_info' ] ];
	 * $this->routes['list'] = [ 'callback' => [ $this, 'list_items'] ];
	 * ```
	 * This registers two endpoints:
	 * - /filemanager/v1/files/ - This uses the get_info callback.
	 * - /filemanager/v1/files/list - This uses the list_items callback.
	 *
	 * Any configuration items that can be passed to the third parameter of `register_rest_route`
	 * may be used in the configuration array for each endpoint.
	 *
	 * @since NEXT
	 * @var array
	 */
	protected $routes = array();

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
		$press_sync_key_from_remote = '';

		if ( isset( $_REQUEST['press_sync_key'] ) ) {
			$press_sync_key_from_remote = filter_var( $_REQUEST['press_sync_key'], FILTER_SANITIZE_STRING );
		}

		$press_sync_key = get_option( 'ps_key' );

		return $press_sync_key && ( $press_sync_key === $press_sync_key_from_remote );
	}

	/**
	 * Registers routes to the WP API.
	 *
	 * @since NEXT
	 */
	public function register_routes() {
		$defaults = array(
			'methods'             => array( 'GET' ),
			'callback'            => '__return_false',
			'permission_callback' => array( $this, 'validate_sync_key' ),
			'args'                => array(
				'press_sync_key' => array(
					'required' => true,
				),
			),
		);

		foreach ( $this->routes as $endpoint => $route_config ) {
			if ( is_numeric( $endpoint ) ) {
				$endpoint = '';
			}

			register_rest_route(
				$this->namespace,
				"{$this->rest_base}/{$endpoint}",
				wp_parse_args( $route_config, $defaults )
			);
		}
	}

	/**
	 * Get remote data from an API request.
	 *
	 * @since NEXT
	 *
	 * @param  string $request The requested datapoint.
	 *
	 * @return array
	 */
	public function get_data( $request ) {
		$request = ltrim( $request, '/' );
		$url     = \Press_Sync\API::get_remote_url( '', "{$this->rest_base}/{$request}", [
			'request' => $request,
		] );

		$response = \Press_Sync\API::get_remote_response( $url );

		if ( empty( $response['body']['success'] ) ) {
			return [];
		}

		return $response['body']['data'];
	}
}
