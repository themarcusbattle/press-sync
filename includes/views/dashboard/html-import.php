<div class="wrap press-sync">
    <?php \WDS\PressSync\PressSyncPlugin::init()->include_page( 'dashboard/nav' ); ?>
	<form class="form" method="post" action="options.php">
		<?php settings_fields( 'press-sync-import' ); ?>
		<?php do_settings_sections( 'press-sync-import' ); ?>
		<table class="form-table">
			<tr valign="top">
				<td colspan="2">
                    <p><strong>Settings below this line may affect performance if altered.</strong></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Post Content Match Threshold</th>
				<td>
					<input type="number" min="0" max="100" name="press_sync_content_threshold" value="<?php echo esc_attr( get_option( 'press_sync_content_threshold' ) ?: '0' ); ?>" />%
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

