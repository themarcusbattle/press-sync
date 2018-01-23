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

			// Start or batch.
			app.syncData( 1, null, {
				order_to_sync_all: response.data,
			} );
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

			// Shift to minutes.
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

	/**
	 * Syncs data to the remote site.
	 *
	 * @since 0.1.0
	 * @param int    paged           The page to sync.
	 * @param string objects_to_sync The object type to sync, may be null if next_args is defined.
	 * @param object next_args       The arguments to use when syncing a batch of different object types.
	 */
	app.syncData = function( paged, objects_to_sync, next_args ) {
		// "Timing, I'm getting used to it."
		var start_time = new Date().getTime();

		// We're syncing all - shift the first object type off the order array.
		if ( ! objects_to_sync && next_args.order_to_sync_all ) {
			objects_to_sync = next_args.order_to_sync_all.shift();
		}

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
				// Start the next batch at page 1.
				if ( next_args.order_to_sync_all && next_args.order_to_sync_all.length ) {
					return app.syncData( 1, null, next_args );
				}

				$('.press-sync-button').show();
				$('.progress-stats').text('Sync completed!');
				return;
			}

			app.syncData( response.data.next_page, response.data.objects_to_sync, next_args );
		});

	}

	$( document ).ready( app.init );

	return app;

} )( window, document, jQuery );
