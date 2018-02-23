<?php

namespace Press_Sync;
?>
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<p>&nbsp;</p>
	<h2 class="nav-tab-wrapper">
		<a href="?page=press-sync&amp;tab=dashboard" class="nav-tab dashboard <?php echo active_tab( 'dashboard' ); ?>" data-div-name="dashboard-tab">Dashboard</a>
		<!-- <a href="?page=press-sync&amp;tab=post-sync" class="nav-tab post-sync" data-div-name="post-sync-tab">Post Sync</a> -->
		<a href="?page=press-sync&amp;tab=bulk-sync" class="nav-tab bulk-sync <?php echo active_tab( 'bulk-sync' ); ?>" data-div-name="bulk-sync-tab">Bulk Sync</a>
		<a href="?page=press-sync&amp;tab=credentials" class="nav-tab credentials <?php echo active_tab( 'credentials' ); ?>" data-div-name="credentials-tab">Credentials</a>
		<a href="?page=press-sync&amp;tab=help" class="nav-tab help <?php echo active_tab( 'help' ); ?>" data-div-name="help-tab">Help</a>
		<a href="?page=press-sync&amp;tab=advanced" class="nav-tab advanced <?php echo active_tab( 'advanced' ); ?> " data-div-name="advanced-tab">Advanced</a>
	</h2>
<?php

function active_tab( $tab = '' ) {
    $selected = '';

    if ( ! isset( $_GET['tab'] ) && 'dashboard' === $tab ) {
        return 'nav-tab-active';
    }

    $selected = filter_var( $_GET['tab'], FILTER_SANITIZE_STRING );

    return $selected === $tab ? 'nav-tab-active' : '';
}
