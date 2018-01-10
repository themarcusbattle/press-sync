<div class="wrap about-wrap press-sync">
    <?php \Press_Sync\Press_Sync::init()->include_page( 'dashboard/nav' ); ?>
	<form class="form" method="post" action="options.php">
		<?php settings_fields( 'press-sync' ); ?>
		<?php do_settings_sections( 'press-sync' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Press Sync Key</th>
				<td>
					<input type="text" name="ps_key" value="<?php echo esc_attr( get_option( 'ps_key' ) ); ?>" />
					<p>This secure key is used to authenticate requests to your site. Without it, your site can't synchronize individual posts or receive content.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Remote Domain</th>
				<td>
					<input type="text" name="ps_remote_domain" value="<?php echo esc_attr( get_option( 'ps_remote_domain' ) ); ?>" required />
					<p>The domain of the remote site that you want to push/pull data from/to.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Remote URL Arguments (Advanced)</th>
				<td>
					<input type="text" name="ps_remote_query_args" value="<?php echo esc_attr( get_option( 'ps_remote_query_args' ) ); ?>" required />
					<p>Specify additional arguments as a GET query string (starting with <code>?</code>).</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Remote Press Sync Key</th>
				<td>
					<input type="text" name="ps_remote_key" value="<?php echo esc_attr( get_option( 'ps_remote_key' ) ); ?>" required />
					<p>The unique key that allows you to communicate with the remote site.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Status</th>
				<td>
					<?php if ( \Press_Sync\Press_Sync::check_connection() ) : ?>
						<span style="color: green;">Connected</span>
					<?php else : ?>
						Not connected. Please check your remote secret key and domain for incorrect spellings.
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
