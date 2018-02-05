<?php

namespace Press_Sync\validators;

class Validation_API {

	protected $route = 'validators';
	protected $endpoint;

	/**
	 * Get data from the destination site.
	 *
	 * @since NEXT
	 */
	public function get_destintation_data( $args = array() ) {
		$data = $this->get_remote_data();
		echo '<pre>', print_r($data, true); die;
	}

	private function get_remote_data( $args = array() ) {
		$path = "/{$this->route}/{$this->endpoint}/";
		$url  = \Press_Sync\Press_Sync::init()->get_remote_url( '', $path, $args );
		echo '<pre>', print_r($url, true); die;
	}
}
