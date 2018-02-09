<?php
namespace Press_Sync\validators;

/**
 * Class AbstractValidator
 *
 * @package Press_Sync\validators
 */
abstract class AbstractValidator {
	protected $source_data;
	protected $destination_data;

	public function __construct( $args = array() ) {
		foreach ( $args as $key => $value ) {
			$this->{$key} = $value;
		}
	}

	public function validate() {
		$this->get_source_data();
		$this->get_destination_data();
	}

	/**
	 * Compare two items to see if they are the same.
	 *
	 * @param $item1
	 * @param $item2
	 *
	 * @return bool
	 * @since NEXT
	 */
	public function compare( $item1, $item2 ) {
		return $item1 === $item2;
	}

	public function __get( $key ) {
		if ( isset( $this->key ) ) {
			return $this->key;
		}
	}
}
