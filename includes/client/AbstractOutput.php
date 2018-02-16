<?php
namespace Press_Sync\client\output;

/**
 * Class AbstractOutput
 *
 * @package Press_Sync\client\output
 */
abstract class AbstractOutput implements OutputInterface {
	/**
	 * Data to output.
	 *
	 * @var array $data
	 */
	public $data;

	/**
	 * AbstractOutput constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data ) {
		$this->data = $data;
	}

	/**
	 * Get an icon based on whether a result was (bool) true or not.
	 *
	 * @since NEXT
	 *
	 * @param  bool $result The result to test.
	 *
	 * @return string
	 */
	protected function get_result_icon( $result ) {
		return ( (bool) $result ) === true ? '✅' : '❌';
	}
}
