<?php
if ( ! apply_filters( 'press_sync_show_advanced_options', false ) ) {
    wp_die();
}
?>
<div class="wrap about-wrap press-sync">
    <?php \Press_Sync\Press_Sync::init()->include_page( 'dashboard/nav' ); ?>
	<form class="form" method="post" action="options.php">
		<?php settings_fields( 'press-sync-advanced' ); ?>
		<?php do_settings_sections( 'press-sync-advanced' ); ?>
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
				<th scope="row">Find and Fix Term Relationships</th>
				<td>
				<input type="checkbox" name="ps_fix_terms" <?php checked( get_option( 'ps_fix_terms' ) ); ?> value="1" />
					<span>This will <strong>only</strong> attempt to find and fix term/taxonomy relationships.</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Sync Post Since Date</th>
				<td>
					<input type="input" name="ps_delta_date" value="<?php echo esc_attr( get_option( 'ps_delta_date' ) ); ?>" />
					<span>Only posts modified after this date (EST) will be synced.</span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Short Terms</th>
				<td>
					<input type="checkbox" name="ps_short_terms" <?php checked( get_option( 'ps_short_terms' ) ); ?> value="1" />
					<span>
					If you've already synced terms and taxonomies to the remote site, this option can speed up the transfer of posts and mitigate
					syncing problems associated with one-to-many post-term relationships and larger-than-average post bodies.
					</span>
				</td>
			</tr>
            <tr>
                <th scope="row">Page Size</th>
                <td>
                    <input type="number" name="ps_page_size" min="1" max="100" value="<?php echo esc_attr( get_option( 'ps_page_size' ) ); ?>" />
                    <p>The size of each batch sent, default and recommendd is 5.</p>
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
			<tr valign="top">
				<th scope="row">Post Content Match Threshold</th>
				<td>
					<input type="number" min="0" max="100" name="ps_content_threshold" value="<?php echo esc_attr( get_option( 'ps_content_threshold' ) ?: '0' ); ?>" />%
                    <p>
                        A threshold &gt; 0% will result in a post_content check for duplicates upon import. If the duplicate post_content fields are
                        not as similar as the threshold value, they will be treated as <strong>non-duplicates</strong> and a new post will be created.
                    </p>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
<script>
jQuery(document).ready(function(){
    jQuery('input[name="ps_delta_date"]').datepicker();
});
</script>
