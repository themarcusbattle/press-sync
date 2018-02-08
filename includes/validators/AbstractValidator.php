<?php
namespace Press_Sync\validators;

/**
 * Class AbstractValidator
 *
 * @package Press_Sync\validators
 */
abstract class AbstractValidator {
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
}
