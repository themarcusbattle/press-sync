<?php
/**
 * Class Press_Sync_API_Abstract_Route
 */
abstract class Press_Sync_API_Abstract_Route extends WP_REST_Controller {
	/**
	 * Namespace for the API.
	 *
	 * @var string
	 */
	protected $namespace = 'press-sync/v1';

	/**
	 * Press_Sync_API_Validator
	 *
	 * @var $validator
	 */
	protected $validator;

	/**
	 * Press_Sync_Data_Synchronizer
	 *
	 * @var $synchronizer
	 */
	protected $synchronizer;
}
