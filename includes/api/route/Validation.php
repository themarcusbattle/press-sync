<?php
namespace Press_Sync\api\route;

use Press_Sync\api\route\validation\Post;
use Press_Sync\api\route\validation\User;
use Press_Sync\api\route\validation\Taxonomy;

/**
 * Class Validation
 *
 * @package Press_Sync\api\route
 */
class Validation extends \WP_REST_Controller {
	/**
	 * The endpoint for the validator.
	 *
	 * This should be set in your extending class and will make the API
	 * request route something like /press-sync/v1/{$route}/{$endpoint}.
	 *
	 * @since NEXT
	 * @var string
	 */
	protected static $endpoint;

	/**
	 * @var
	 */
	protected $routes = array(
		User::class,
		Taxonomy::class,
		Post::class,
	);

	/**
	 * Validation constructor.
	 */
	public function __construct() {
		$this->rest_base = 'validation';
	}

	/**
	 * Registers API endpoints for the extending class.
	 *
	 * This registers an API endpoint at /namespace/route/endpoint/ based on the extending
	 * class's properties. The class should implement a static public method called
	 * get_api_response that can parse the request parameters and return the desired data.
	 *
	 * @since NEXT
	 */
	public function register_routes() {
		foreach ( $this->routes as $route ) {
			/* @var AbstractRoute $class */
			$class = new $route();
			$class->register_hooks();
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
	public function get_remote_data( $request ) {
		$route    = static::$route;
		$endpoint = static::$endpoint;

		$url = API::get_remote_url( '', "{$route}/{$endpoint}", [
			'request' => $request,
		] );

		$response = API::get_remote_response( $url );

		if ( empty( $response['body']['success'] ) ) {
			return [];
		}

		return $response['body']['data'];
	}
}
