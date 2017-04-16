window.PressSync = ( function( window, document, $ ) {

	var app = {};

	app.init = function() {
		$(document).on( 'click', '.press-sync-button', app.pressSyncButton );
	};

	app.pressSyncButton = function( click_event ) {

		app.loadProgressBar();
		return;

	}

	app.loadProgressBar = function() {

		$('.press-sync-button').hide();
		$('.progress-bar-wrapper,.progress-stats').fadeIn();

		$.ajax({
			method: "POST",
			url: press_sync.ajax_url,
			data: {
				action: 'get_objects_to_sync_count',
			}
		}).done(function( response ) {
			app.updateProgressBar( response.data.objects_to_sync, 0, response.data.total_objects );
			app.syncData(1);
		});

	}

	app.updateProgressBar = function( objects_to_sync, total_objects_processed, total_objects ) {

		var progress_complete = ( total_objects_processed / total_objects ) * 100;

		$('.progress-bar').css('width', progress_complete + '%' ).text( Math.ceil( progress_complete ) + '%' );
		$('.progress-stats').text( total_objects_processed + '/' + total_objects + ' ' + objects_to_sync + ' synced' );
	}

	app.syncData = function( paged ) {

		$.ajax({
			method: "POST",
			url: press_sync.ajax_url,
			data: {
				action: 'sync_wp_data',
				paged: paged,
			}
		}).done(function( response ) {

			app.updateProgressBar( response.data.objects_to_sync, response.data.total_objects_processed, response.data.total_objects );

			if ( response.data.total_objects_processed >= response.data.total_objects ) {
				$('.press-sync-button').show();
				$('.progress-stats').text('Sync completed!');
			} else {
				app.syncData( response.data.next_page );
			}

		});

	}

	$( document ).ready( app.init );

	return app;

} )( window, document, jQuery );
