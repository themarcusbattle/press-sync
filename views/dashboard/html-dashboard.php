<div class="wrap press-sync cmb2-options-page">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<h2 class="nav-tab-wrapper">
		<a href="#" class="nav-tab nav-tab-active sync" data-div-name="migrate-tab">Sync</a>
		<a href="?page=press-sync&amp;tab=settings" class="nav-tab settings" data-div-name="settings-tab">Settings</a>
	</h2>
	<?php cmb2_metabox_form( 'press_sync_dashboard_metabox', 'press-sync-options' ); ?>
	<h2>Sync Data</h2>
	<?php if ( Press_Sync::check_connection() ): ?>
		<button class="press-sync-button">Sync</button>
	<?php else: ?>
		<p>Check your PressSync key. You are not connected to the target server.</p>
	<?php endif; ?>
	<div class="progress-stats" style="display: none;">
		Loading...
	</div>
	<div class="progress-bar-wrapper" style="height: 24px; display: none; width: 100%; background-color: #DDD; border-radius: 6px; overflow: hidden; box-sizing: border-box;">
		<div class="progress-bar" style="height:24px; width: 0px; background-color: #666; color: #fff; line-height: 24px; padding: 0 10px;"></div>
	</div>

</div>
