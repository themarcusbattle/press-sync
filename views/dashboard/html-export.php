<?php
if ( ! apply_filters( 'press_sync_show_advanced_options', false ) ) {
    wp_die();
}
?>
<div class="wrap about-wrap press-sync">
    <?php \Press_Sync\Press_Sync::init()->include_page( 'dashboard/nav' ); ?>
	<form class="form" method="post" action="options.php">
		<?php settings_fields( 'press-sync-export' ); ?>
		<?php do_settings_sections( 'press-sync-export' ); ?>
		<table class="form-table">
            <tr>
                <th scope="row">Testing Post ID</th>
                <td>
                    <input type="number" name="ps_testing_post" value="<?php echo esc_attr( get_option( 'ps_testing_post' ) ); ?>" />
                    <p>Test your Press Sync connection against a single post by specifying the ID here.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Don't Sync Media Assets</th>
                <td>
                    <input type="checkbox" name="ps_skip_assets" <?php checked( get_option( 'ps_skip_assets' ) ); ?> value="1" />
                    <p>Attachments will still be synced, however their file assets will <strong>NOT</strong> be copied to the remote site.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Preserve Post-type Object IDs</th>
                <td>
                <input type="checkbox" name="ps_preserve_ids" <?php checked( get_option( 'ps_preserve_ids' ) ); ?> value="1" />
                    <span>Enable this option to maintain post IDs. <strong>N.B.:</strong> this can cause issues if you're syncing
                    to a site that has content that was not synced with Press Sync due to ID collisions, use with care.</span>
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
                    <input type="number" name="ps_request_buffer_time" value="<?php echo esc_attr( get_option( 'ps_request_buffer_time' ) ); ?>" />
                    <p>This is the time in seconds to buffer between requests.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Start at Object n</th>
                <td>
                    <input type="number" name="ps_start_object_offset" value="<?php echo esc_attr( get_option( 'ps_start_object_offset' ) ); ?>" />
                    <p>Start the batch at this object index "n" instead of starting at zero.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Sync Missing</th>
                <td>
                <input type="checkbox" name="ps_only_sync_missing" <?php checked( get_option( 'ps_only_sync_missing' ) ); ?> value="1" />
                    <span>Search for and sync missing objects, if possible.</span>
                </td>
            </tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>

