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
}
