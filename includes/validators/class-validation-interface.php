<?php
/**
 * Validation interface.
 *
 * @package PressSync
 */

namespace Press_Sync\validators;

/**
 * Define an interface that all Validators should implement.
 */
interface Validation_Interface {
	/**
	 * Method to build full comparison of results.
	 *
	 * This should return an array that follows a standard model of:
	 * array(
	 *    'what' => array(
	 *        'key' => 'row',
	 *    )
	 * )
	 *
	 * Where:
	 * - 'what' Will be used as the result section heading.
	 * - 'key' Is currently unused, should be a unique key for this row of data.
	 * - 'row' A formatted message about the results for the comparison.
	 *
	 * Example:
	 * array(
	 *     'counts' => array(
	 *         'published_posts' => '✅ Published posts count is 24,331 vs 24,331.',
	 *         'draft_posts'     => '❌ Draft posts count is 5 vs 3.',
	 *     ),
	 *     'samples' => array(
	 *         177232 => '✅ The post "Some Test Post" matches 1:1 with the destination site.',
	 *         175300 => '❌ The post "Another Post" differs between source and destination: <detailed description of errors>',
	 *     )
	 * )
	 *
	 * @since NEXT
	 *
	 * @return array
	 */
	public function compare_results();

	/**
	 * This method should gather data from the source site for comparison.
	 *
	 * @since NEXT
	 */
	public function get_source_data();

	/**
	 * This method should gather data from the destination site for comparison.
	 *
	 * @since NEXT
	 */
	public function get_destintation_data();

	/**
	 * Register all routes for getting data for this type of object.
	 */
	public static function register_api_endpoints();
}
