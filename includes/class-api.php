<?php

/**
 * Class Press_Sync_API
 */
class Press_Sync_API extends WP_REST_Controller {

	/**
	 * Parent plugin class.
	 *
	 * @var   Press_Sync
	 * @since 0.1.0
	 */
	protected $plugin = null;

	/**
	 * Prefix for meta keys
	 *
	 * @var string
	 * @since  0.1.0
	 */
	protected $prefix = 'press_sync_api_';

	/**
	 * @var
	 */
	private $routes;

	/**
	 * @var Press_Sync_API_Validator
	 */
	protected $validator;

	/**
	 * @var Press_Sync_Post_Synchronizer
	 */
	protected $post_synchronizer;

	/**
	 * @var Press_Sync_Media_Synchronizer
	 */
	protected $media_synchronizer;

	/**
	 * Constructor.
	 *
	 * @since  0.1.0
	 *
	 * @param  Press_Sync $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin             = $plugin;
		$this->validator          = new Press_Sync_API_Validator( $plugin );
		$this->post_synchronizer  = new Press_Sync_Post_Synchronizer();
		$this->media_synchronizer = new Press_Sync_Media_Synchronizer();
		$this->routes = array(
			new Press_Sync_API_Route_Post( $this->validator, $this->post_synchronizer ),
			new Press_Sync_API_Route_Page( $this->validator, $this->post_synchronizer ),
			new Press_Sync_API_Route_Attachment( $this->validator, $this->media_synchronizer ),
			new Press_Sync_API_Route_User( $this->validator ),
			new Press_Sync_API_Route_Status( $this->validator ),
			new Press_Sync_API_Route_Sync( $this->validator, $this->post_synchronizer ),
		);

		$this->hooks();
	}

	/**
	 * Add our hooks.
	 *
	 * @since  0.1.0
	 */
	public function hooks() {
		foreach ( $this->routes as $route ) {
			add_action( 'rest_api_init', array( $route, 'register_routes' ) );
		}
	}
}
