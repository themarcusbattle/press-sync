	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<p>The easiest way to synchronize content between two WordPress sites.</p>
	<h2 class="nav-tab-wrapper">
        <a href="?page=press-sync" class="nav-tab sync <?php echo active_tab(); ?> " data-div-name="migrate-tab">Sync</a>
		<a href="?page=press-sync&amp;tab=export" class="nav-tab export <?php echo active_tab( 'export' ); ?> " data-div-name="export-tab">Advanced Export</a>
		<a href="?page=press-sync&amp;tab=import" class="nav-tab import <?php echo active_tab( 'import' ); ?> " data-div-name="import-tab">Advanced Import</a>
		<a href="?page=press-sync&amp;tab=settings" class="nav-tab settings <?php echo active_tab( 'settings' ); ?> " data-div-name="settings-tab">Settings</a>
	</h2>
<?php

function active_tab( $tab = '' ) {
    $selected = '';

    if ( ! isset( $_GET['tab'] ) && ! $tab ) {
        return 'nav-tab-active';
    }

    $selected = filter_var( $_GET['tab'], FILTER_SANITIZE_STRING );

    return $selected === $tab ? 'nav-tab-active' : '';
}
