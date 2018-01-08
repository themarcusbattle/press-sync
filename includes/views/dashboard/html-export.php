<div class="wrap press-sync">
    <?php \WDS\PressSync\PressSyncPlugin::init()->include_page( 'dashboard/nav' ); ?>
	<form class="form" method="post" action="options.php">
		<?php settings_fields( 'press-sync-export' ); ?>
		<?php do_settings_sections( 'press-sync-export' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">WP Options</th>
				<td>
					<input type="text" name="press_sync_options" value="<?php echo esc_attr( get_option( 'press_sync_options' ) ); ?>" />
					<p>The comma-separated list of WP options you want to synchronize when "Objects To Sync" is "Options".</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Ignore Comments?</th>
				<td>
					<select name="press_sync_ignore_comments">
						<option value="">--</option>
						<option value="1" <?php selected( get_option( 'press_sync_ignore_comments' ), 1 ); ?>>Yes</option>
						<option value="0" <?php selected( get_option( 'press_sync_ignore_comments' ), 0 ); ?>>No</option>
					</select>
					<p>Checking this box ommits comments from being synced to the remote site.</p>
				</td>
			</tr>
            <tr>
                <th scope="row">Testing Post ID</th>
                <td>
                    <input type="number" name="press_sync_testing_post" value="<?php echo esc_attr( get_option( 'press_sync_testing_post' ) ); ?>" />
                    <p>Test your Press Sync connection against a single post by specifying the ID here.</p>
                </td>
            </tr>
			<tr valign="top">
				<td colspan="2">
                    <p><strong>Settings below this line may affect performance if altered.</strong></p>
				</td>
			</tr>
            <tr valign="top">
                <th scope="row">Request Buffer Time</th>
                <td>
                    <input type="number" name="press_sync_request_buffer_time" value="<?php echo esc_attr( get_option( 'press_sync_request_buffer_time' ) ); ?>" />
                    <p>This is the time in seconds to buffer between requests.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Start at Object n</th>
                <td>
                    <input type="number" name="press_sync_start_object_offset" value="<?php echo esc_attr( get_option( 'press_sync_start_object_offset' ) ); ?>" />
                    <p>Start the batch at this object index "n" instead of starting at zero.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Sync Missing</th>
                <td>
                <input type="checkbox" name="press_sync_only_sync_missing" <?php checked( get_option( 'press_sync_only_sync_missing' ) ); ?> value="1" />
                    <span>Search for and sync missing objects, if possible.</span>
                </td>
            </tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<h2>Sync Data</h2>
	<?php if ( \WDS\PressSync\PressSyncPlugin::check_connection() ) : ?>
		<button class="press-sync-button">Sync</button>
	<?php else : ?>
		<p>Check your PressSync key. You are not connected to the target server.</p>
	<?php endif; ?>
	<div class="progress-stats" style="display: none;">
		Loading...
	</div>
	<div class="progress-bar-wrapper" style="height: 24px; display: none; width: 100%; background-color: #DDD; border-radius: 6px; overflow: hidden; box-sizing: border-box;">
		<div class="progress-bar" style="height:24px; width: 0px; background-color: #666; color: #fff; line-height: 24px; padding: 0 10px;"></div>
	</div>
</div>

