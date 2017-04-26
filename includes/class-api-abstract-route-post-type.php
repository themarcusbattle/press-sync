<?php

/**
 * Class Press_Sync_Route_Post_Type
 */
abstract class Press_Sync_API_Abstract_Route_Post_Type extends WP_REST_Controller {
	/**
	 * @var Press_Sync_API_Validator
	 */
	protected $validator;

	/**
	 * @var
	 */
	protected $synchronizer;
}
