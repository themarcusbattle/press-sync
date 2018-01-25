<div class="wrap about-wrap press-sync">
    <?php \Press_Sync\Press_Sync::init()->include_page( 'dashboard/nav' ); ?>
	<div class="feature-section one-col">
		<div class="col">
			<p class="lead-description">Validate Your Press Sync Data Against the Remote Site</p>
			<p style="text-align: center;"></p>
		</div>
	</div>
	<hr />
	<h3>Bulk Sync Settings</h3>
	<form class="form" method="post" action="options.php">
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Ignore Comments?</th>
				<td>
				</td>
			</tr>
		</table>
		<?php submit_button( 'Save Bulk Settings' ); ?>
	</form>
	<hr />
</div>
