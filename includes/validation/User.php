<?php
namespace Press_Sync\validation;

/*
 * @package Press_Sync\validation
 */
class User {
	/**
	 * Get the number of users in the WordPress install.
	 */
	public function get_count() {
		$counts            = count_users();
		$prepared          = $counts['avail_roles'];
		$prepared['total'] = $counts['total_users'];

		return $prepared;
	}
}
