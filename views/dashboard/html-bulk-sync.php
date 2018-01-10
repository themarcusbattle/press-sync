<div class="wrap about-wrap press-sync">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<p>&nbsp;</p>
    <?php \WDS\PressSync\PressSyncPlugin::init()->include_page( 'dashboard/nav' ); ?>
	<div class="feature-section one-col">
		<div class="col">
			<p class="lead-description">This tool allows you to synchronize this entire site (or a portion of it) with another WordPress site.</p>
			<p style="text-align: center;">Enter your settings below. Save the changes. Press "Sync" to trigger Press Sync.</p>
			<?php if ( Press_Sync::check_connection() ) : ?>
				<p style="text-align: center;"><button class="press-sync-button button button-primary button-large" style="min-width: 150px;">Sync</button></p>
			<?php else : ?>
				<p style="text-align: center;"><strong>Check your <a href="?page=press-sync&amp;tab=credentials">remote Press Sync key</a>. You are not connected to the remote site.</strong></p>
			<?php endif; ?>
			<div class="progress-stats" style="display: none; text-align: center; padding: 10px 0;">
				Loading...
			</div>
			<div class="progress-bar-wrapper" style="height: 24px; display: none; width: 100%; background-color: #DDD; border-radius: 6px; overflow: hidden; box-sizing: border-box;">
				<div class="progress-bar" style="height:24px; width: 0px; background-color: #666; color: #fff; line-height: 24px; padding: 0 10px;"></div>
			</div>
		</div>
	</div>
	<hr />
	<h3>Bulk Sync Settings</h3>
	<form class="form" method="post" action="options.php">
		<?php settings_fields( 'press-sync-bulk-sync' ); ?>
		<?php do_settings_sections( 'press-sync-bulk-sync' ); ?>
		<div style="max-width: 800px;">
			
		</div>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Sync Method</th>
				<td>
					<select name="ps_sync_method" required>
						<option value="">--</option>
						<option value="push" <?php selected( get_option( 'ps_sync_method' ), 'push' ); ?>>Push</option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Objects to Sync</th>
				<td>
					<select name="ps_objects_to_sync" required>
						<option value="">--</option>
						<?php foreach ( Press_Sync::objects_to_sync() as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( get_option( 'ps_objects_to_sync' ), $key ); ?>><?php echo esc_attr( $value ); ?></option>
						<?php endforeach; ?>
					</select>
					<p>Define the WP objects you want to synchronize with the remote site.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">WP Options to Sync</th>
				<td>
					<input type="text" name="ps_options_to_sync" value="<?php echo esc_attr( get_option( 'ps_options_to_sync' ) ); ?>" />
					<p>The comma-separated list of WP options you want to synchronize when "Objects To Sync" is "Options".</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Duplicate Action</th>
				<td>
					<select name="ps_duplicate_action" required>
						<option value="">--</option>
						<option value="sync" <?php selected( get_option( 'ps_duplicate_action' ), 'sync' ); ?>>Sync</option>
						<option value="skip" <?php selected( get_option( 'ps_duplicate_action' ), 'skip' ); ?>>Skip</option>
					</select>
					<p>How do you want to handle non-synced duplicates? The "sync" option will give a non-synced duplicate a press sync ID to be synced for the future.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Force Update?</th>
				<td>
					<select name="ps_force_update" required>
						<option value="">--</option>
						<option value="1" <?php selected( get_option( 'ps_force_update' ), 1 ); ?>>Yes</option>
						<option value="0" <?php selected( get_option( 'ps_force_update' ), 0 ); ?>>No</option>
					</select>
					<p>Force the content on the remote site to be overwritten when the sync method is "push".</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Ignore Comments?</th>
				<td>
					<select name="ps_ignore_comments" required>
						<option value="">--</option>
						<option value="1" <?php selected( get_option( 'ps_ignore_comments' ), 1 ); ?>>Yes</option>
						<option value="0" <?php selected( get_option( 'ps_ignore_comments' ), 0 ); ?>>No</option>
					</select>
					<p>Checking this box ommits comments from being synced to the remote site.</p>
				</td>
			</tr>
		</table>
		<?php submit_button( 'Save Bulk Settings' ); ?>
	</form>
	<hr />
</div>
