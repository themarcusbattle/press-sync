<?php
namespace Press_Sync\validators;

/**
 * Class AbstractValidator
 *
 * @package Press_Sync\validators
 */
abstract class AbstractValidator {
	/**
	 * The arguments used to create this class.
	 *
	 * @since NEXT
	 * @var array
	 */
	protected $args = array();

	/**
	 * Data from the source site.
	 *
	 * @var array
	 * @since NEXT
	 */
	protected $source_data;

	/**
	 * Data from the destination site.
	 *
	 * @var array
	 * @since NEXT
	 */
	protected $destination_data;

	/**
	 * Gets all validation and comparison data.
	 *
	 * @since NEXT
	 * @return array
	 */
	abstract public function validate();

	/**
	 * Setup the validator.
	 *
	 * @since NEXT
	 * @param array $args {
	 *     Array of arguments for the validator.
	 *
	 *     @type array format       Formatting characters to wrap mis/matches for display.
	 * }
	 *
	 * @throws \InvalidArgumentException When a required argument is missing.
	 */
	public function __construct( $args ) {
		$this->args = $args;

		if ( ! isset( $args['format'] ) ) {
			throw new \InvalidArgumentException( __( get_called_class() . ' missing required argument "format"!', 'press-sync' ) );
		}
	}

	/**
	 * Magic getter.
	 *
	 * @since NEXT
	 * @param  string $key The property key to grab.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( isset( $this->key ) ) {
			return $this->key;
		}
	}

	/**
	 * Compares source and destination data.
	 *
	 * @since NEXT
	 * @param  array $source      The source dataset.
	 * @param  array $destination The destination dataset.
	 * @return array
	 */
	abstract public function get_comparison_data( array $source, array $destination );

	/**
	 * Determine if two values are the same and wrap them in appropriate formatting.
	 *
	 * @since NEXT
	 * @param  mixed    $count   The first count to compare.
	 * @param  mixed    $compare The count to compare against the first.
	 * @return string
	 */
	protected function apply_diff_to_values( $count, $compare ) {
		$format = $this->args['format'];
		$pre    = $format['match_open_wrap'];
		$post   = $format['match_close_wrap'];

		if ( $count !== $compare ) {
			$pre  = $format['mismatch_open_wrap'];
			$post = $format['mismatch_close_wrap'];
		}

		return "{$pre}{$compare}{$post}";
	}
}
