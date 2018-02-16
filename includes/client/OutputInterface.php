<?php
namespace Press_Sync\client\output;

/**
 * Interface OutputInterface
 *
 * @package Press_Sync\client\output
 */
interface OutputInterface {
	/**
	 * Render data to the client.
	 *
	 * @since NEXT
	 * @return mixed
	 */
	public function render();
}
