<?php
namespace Press_Sync\validation;

/*
 * @package Press_Sync\validation
 */
class User {
	/**
	 * Number of sample records to get.
	 *
	 * @since NEXT
	 * @var int
	 */
	protected $sample_count = 0;

	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'sample_count' => 5,
		) );

		foreach ( $args as $key => $value ) {
			if ( isset( $this->{$key} ) ) {
				$this->{$key} = $value;
			}
		}
	}

	/**
	 * Get the number of users in the WordPress install.
	 */
	public function get_count() {
		$counts            = count_users();
		$prepared          = $counts['avail_roles'];
		$prepared['total'] = $counts['total_users'];

		return $prepared;
	}

	/**
	 * Returns a random sample of data for validation.
	 *
	 * @since NEXT
	 * @return array
	 */
	public function get_samples( $request = null ) {
		if ( $request ) {
			$source_users = $request->get_param( 'source_users' );
			return $this->find_sample_matches( $source_users );
		}

		$count   = absint( $this->sample_count ) ?: 5;
		$users   = get_users();
		$samples = array();

		for ( $count; $count--; ) {
			$offset = rand(0, count( $users ) ) - 1;
			$samples[] = current( array_slice( $users, $offset, 1 ) );
		}

		return $samples;
	}

    private function find_sample_matches( $samples ) {
        $results = array();
        $meta_key = 'press_sync_user_id';

        if ( is_multisite() ) {
            global $blog_id;
            $meta_key = "press_sync_{$blog_id}_user_id";
        }

        foreach ( $samples as $args ) {
            // Strongest match is all three matching.
            $user_args = array(
                'meta_key'   => $meta_key,
                'meta_value' => $args['ID'],
                'login'      => $args['user_login'],
                'user_email' => $args['user_email'],
            );

            $user_args = array_filter( $user_args );

            $query = new \WP_User_Query( $user_args );
            $users = $query->get_results();

            if ( ! $users ) {
                unset( $user_args['meta_key'] );
                unset( $user_args['meta_value'] );
            }

            while ( empty( $users ) && count( $user_args ) ) {
                $query = new \WP_User_Query( $user_args );
                $users = $query->get_results();

                if ( empty( $users ) ) {
                    array_shift( $user_args );
                }
            }

            if ( ! count( $users ) ) {
                $user = [];
            } else {
                $user = (array) $users[0];
            }

            $user['source_data']    = $args;
            $user['matched_fields'] = $user_args;
            $results[]              = $user;
        }

        return $results;
    }
}
