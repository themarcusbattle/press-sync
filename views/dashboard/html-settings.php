<div class="wrap press-sync cmb2-options-page">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<h2 class="nav-tab-wrapper">
		<a href="?page=press-sync" class="nav-tab sync" data-div-name="migrate-tab">Sync</a>
		<a href="?page=press-sync&amp;tab=settings" class="nav-tab nav-tab-active settings" data-div-name="settings-tab">Settings</a>
		<!-- <a href="#" class="nav-tab js-action-link help" data-div-name="help-tab">Help</a> -->
	</h2>
	<?php cmb2_metabox_form( 'press_sync_dashboard_settings_metabox', 'press-sync-options' ); ?>
</div>
