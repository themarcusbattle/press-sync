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

			if ( 'all' == response.data.objects_to_sync ) {
				app.syncAll();
			} else {
				app.syncData( 1, response.data.objects_to_sync );
			}
			
		});

	}

	app.syncAll = function() {

		$.ajax({
			method: "POST",
			url: press_sync.ajax_url,
			data: {
				action: 'get_order_to_sync_all',
			}
		}).done(function( response ) {

			if ( ! response.success ) {
				alert( 'There was a connection error. We could not determine the order to sync all objects.' );
				return;
			}

			var order_to_sync_all = response.data;

			for ( var i = 0, length = order_to_sync_all.length; i < length; i++ ) {
				app.syncData( 1, order_to_sync_all[i] );
			}

		});
	}

	app.updateProgressBar = function( objects_to_sync, total_objects_processed, total_objects ) {

		var progress_complete = ( total_objects_processed / total_objects ) * 100;
		var percent_complete  = Math.ceil( progress_complete );
		
		if ( percent_complete > 100 ) {
			percent_complete = 100;
		}

		$('.progress-bar').css('width', progress_complete + '%' ).text( percent_complete + '%' );
		$('.progress-stats').text( total_objects_processed + '/' + total_objects + ' ' + objects_to_sync + ' synced' );
	}

	app.syncData = function( paged, objects_to_sync ) {

		$.ajax({
			method: "POST",
			url: press_sync.ajax_url,
			data: {
				action: 'sync_wp_data',
				paged: paged,
				objects_to_sync: objects_to_sync
			}
		}).done(function( response ) {

			app.updateProgressBar( response.data.objects_to_sync, response.data.total_objects_processed, response.data.total_objects );

			if ( response.data.total_objects_processed >= response.data.total_objects ) {
				$('.press-sync-button').show();
				$('.progress-stats').text('Sync completed!');
			} else {
				app.syncData( response.data.next_page, response.data.objects_to_sync );
			}

		});

	}

	$( document ).ready( app.init );

	return app;

} )( window, document, jQuery );
