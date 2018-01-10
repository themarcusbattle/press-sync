<div class="wrap press-sync">
    <?php \WDS\PressSync\PressSyncPlugin::init()->include_page( 'dashboard/nav' ); ?>
	<form class="form" method="post" action="options.php">
		<?php settings_fields( 'press-sync-settings' ); ?>
		<?php do_settings_sections( 'press-sync-settings' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Sync Key</th>
				<td>
					<input type="text" name="press_sync_key" value="<?php echo esc_attr( get_option( 'press_sync_key' ) ); ?>" />
					<p>This secure key is used to authenticate requests to your site. Without it, press sync won't work.</p>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
