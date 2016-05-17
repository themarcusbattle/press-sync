window.PressSync = ( function( window, document, $ ) {

	var app = {};

	app.init = function() {
		$(document).on( 'click', '.press-sync-button', app.pressSyncButton );
	};

	app.pressSyncButton = function( click_event ) {

		$.ajax({
			method: "POST",
			url: press_sync.ajax_url,
			data: {
				action: 'sync_wp_data',
			}
		}).done(function( msg ) {
			alert( "Data Saved: " + msg );
		});

	}

	$( document ).ready( app.init );

	return app;

} )( window, document, jQuery );