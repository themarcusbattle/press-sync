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

	app.updateProgressBar = function( objects_to_sync, total_objects_processed, total_objects, request_time ) {

		var progress_complete = ( total_objects_processed / total_objects ) * 100;
		var percent_complete  = Math.floor( progress_complete );

		if ( percent_complete > 100 ) {
			percent_complete = 100;
		}

		$('.progress-bar').css('width', progress_complete + '%' ).text( percent_complete + '%' );

		var progress_string = total_objects_processed + '/' + total_objects + ' ' + objects_to_sync + ' synced';

		if ( request_time ) {
			// Estimate time remaining.
			var remaining_time = ( ( ( total_objects - total_objects_processed ) / 5 ) * request_time ) / 60 / 60;
				var time_left_suffix = 'hours';

				if ( 1 > remaining_time ) {
					remaining_time = remaining_time * 60;
					time_left_suffix = 'minutes';
				}

				// Round to two decimal places, mostly.
				remaining_time = Math.round( remaining_time * 100 ) / 100;

				progress_string += ' (' + [ 'Estimated time remaining:', remaining_time, time_left_suffix ].join(' ') + ')';
			}

		$('.progress-stats').text( progress_string );
	}

	app.syncData = function( paged, objects_to_sync ) {

		var start_time = new Date().getTime();

		$.ajax({
			method: "POST",
			url: press_sync.ajax_url,
			data: {
				action: 'sync_wp_data',
				paged: paged,
				objects_to_sync: objects_to_sync
			}
		}).done(function( response ) {
			// Convert request time from milliseconds to seconds.
			var request_time = ( new Date().getTime() - start_time ) / 1000;
			app.updateProgressBar( response.data.objects_to_sync, response.data.total_objects_processed, response.data.total_objects, request_time );

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
