<div class="wrap press-sync">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<p>The easiest way to synchronize content between two WordPress sites.</p>
	<h2 class="nav-tab-wrapper">
		<a href="#" class="nav-tab nav-tab-active sync" data-div-name="migrate-tab">Sync</a>
		<a href="?page=press-sync&amp;tab=settings" class="nav-tab settings" data-div-name="settings-tab">Settings</a>
	</h2>
	<form class="form" method="post" action="options.php">
		<?php settings_fields( 'press-sync-options' ); ?>
		<?php do_settings_sections( 'press-sync-options' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Remote Domain</th>
				<td>
					<input type="text" name="remote_domain" value="<?php echo esc_attr( get_option( 'remote_domain' ) ); ?>" />
					<p>The domain of the remote site that you want to push/pull data from/to.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Remote Press Sync Key</th>
				<td>
					<input type="text" name="remote_press_sync_key" value="<?php echo esc_attr( get_option( 'remote_press_sync_key' ) ); ?>" />
					<p>The unique key that allows you to communicate with the remote site.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Sync Method</th>
				<td>
					<select name="sync_method">
						<option value="">--</option>
						<option value="push" <?php selected( get_option( 'sync_method' ), 'push' ); ?>>Push</option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Objects to Sync</th>
				<td>
					<select name="objects_to_sync">
						<option value="">--</option>
						<?php foreach ( \WDS\PressSync\PressSyncPlugin::objects_to_sync() as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( get_option( 'objects_to_sync' ), $key ); ?>><?php echo esc_attr( $value ); ?></option>
						<?php endforeach; ?>
					</select>
					<p>Define the WP objects you want to synchronize with the remote site.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">WP Options</th>
				<td>
					<input type="text" name="options" value="<?php echo esc_attr( get_option( 'options' ) ); ?>" />
					<p>The comma-separated list of WP options you want to synchronize when "Objects To Sync" is "Options".</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Duplicate Action</th>
				<td>
					<select name="duplicate_action">
						<option value="">--</option>
						<option value="sync" <?php selected( get_option( 'duplicate_action' ), 'sync' ); ?>>Sync</option>
						<option value="skip" <?php selected( get_option( 'duplicate_action' ), 'skip' ); ?>>Skip</option>
					</select>
					<p>How do you want to handle non-synced duplicates? The "sync" option will give a non-synced duplicate a press sync ID to be synced for the future.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Force Update?</th>
				<td>
					<select name="force_update">
						<option value="">--</option>
						<option value="1" <?php selected( get_option( 'force_update' ), 1 ); ?>>Yes</option>
						<option value="0" <?php selected( get_option( 'force_update' ), 0 ); ?>>No</option>
					</select>
					<p>Force the content on the remote site to be overwritten when the sync method is "push".</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Ignore Comments?</th>
				<td>
					<select name="ignore_comments">
						<option value="">--</option>
						<option value="1" <?php selected( get_option( 'ignore_comments' ), 1 ); ?>>Yes</option>
						<option value="0" <?php selected( get_option( 'ignore_comments' ), 0 ); ?>>No</option>
					</select>
					<p>Checking this box ommits comments from being synced to the remote site.</p>
				</td>
			</tr>
            <tr valign="top">
                <th scope="row">Request Buffer Time</th>
                <td>
                    <input type="number" name="request_buffer_time" value="<?php echo esc_attr( get_option( 'request_buffer_time' ) ); ?>" />
                    <p>This is the time in seconds to buffer between requests.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Start at Object n</th>
                <td>
                    <input type="number" name="start_object_offset" value="<?php echo esc_attr( get_option( 'start_object_offset' ) ); ?>" />
                    <p>Start the batch at this object index "n" instead of starting at zero.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Sync Missing</th>
                <td>
                <input type="checkbox" name="only_sync_missing" <?php checked( get_option( 'only_sync_missing' ) ); ?> value="1" />
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
