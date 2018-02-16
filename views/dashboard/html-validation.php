<div class="wrap about-wrap press-sync">
	<?php \Press_Sync\Press_Sync::init()->include_page( 'dashboard/nav' ); ?>
	<div class="feature-section one-col">
		<div class="col">
			<p class="lead-description">Validate Your Press Sync Data</p>
		</div>
	</div>
	<hr />
	<h3>Validation Tasks</h3>
	<form class="form" method="post" action="options.php">
		<?php settings_fields( 'press-sync-validation' ); ?>
		<?php do_settings_sections( 'press-sync-validation' ); ?>
		<table class="form-table">
			<tbody>
			<?php foreach ( \Press_Sync\Validation::get_valid_types() as $key => $args ) : ?>
				<tr valign="top">
					<td style="padding: 0">
						<?php submit_button( sprintf( __( 'Validate %s', 'press-sync' ), $args['label'] ), 'primary', \Press_Sync\Validation::VALIDATION_OPTION, true ); ?>
					</td>
					<td style="padding: 0">
						<?php echo esc_html( $args['description'] ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
			<?php if ( \Press_Sync\Validation::is_validating() ): ?>
				<tr valign="top">
					<th scope="row"><h3>Validation Results</h3></th>
					<td>
						<?php
						try {
							$comparison_results = \Press_Sync\Validation::get_validation_results();
							echo \Press_Sync\Dashboard::format_validation( $comparison_results );
						} catch ( \Exception $e ) { ?>
							<p class="error">
								There was a problem running the requested validation: <?php echo esc_html( $e->getMessage() ); ?>
							</p>
						<?php } ?>
					</td>
				</tr>
			<?php endif; ?>
			</tfoot>
		</table>
	</form>
	<hr />
</div>
