window.PressSync = ( function( window, document, $ ) {

	var app = {
		PAGE_SIZE: 1,
		LOG_LIMIT: 20, // Limit how many log entries to show, previous logs will be discarded.
		log_index: 0, // Count how many logs have processed.
		times: [], // Array of times used to calculate time remaining average more accurately.
		elCache: {},
		logFileURL: null
	};

	/**
	 * Initialize the JS app for Press Sync.
	 *
	 * Handles registering elements to the element Cache and binding inital listeners.
	 *
	 * @since 0.1.0
	 */
	app.init = function() {
		app.elCache.syncButton   = $('#press-sync-button');
		app.elCache.cancelButton = $('#press-sync-cancel-button');
		app.elCache.status       = $('.progress-stats');
		app.elCache.bulkSettings = $('#press-sync-bulk-settings');
		app.elCache.logView      = $('#press-sync-log-view' );
		app.elCache.logs         = $('#press-sync-logs');
		app.elCache.downloadLog  = $('#press-sync-download-log');

		app.elCache.syncButton.on( 'click', app.pressSyncButton );
		app.elCache.cancelButton.on( 'click', app.cancelSync );
		app.elCache.downloadLog.on( 'click', app.downloadLog );
	};

	/**
	 * Handles the Press Sync "Sync" button.
	 *
	 * @since NEXT
	 * @param Object click_event The click event from the listener.
	 */
	app.pressSyncButton = function( click_event ) {
		app.running = true;

		// @TODO probably should have this in a method similar to app.cleanup.
		app.elCache.syncButton.hide();
		app.elCache.cancelButton.show();
		app.elCache.bulkSettings.hide();
		app.elCache.logView.show();
		app.elCache.logs.val('');
		app.loadProgressBar();
		return;
	};

	/**
	 * Set the app.running flag to false to stop the current sync process.
	 *
	 * @since NEXT
	 */
	app.cancelSync = function() {
		app.running = false;
	};

	/**
	 * Update the timing array and smooth out timing samples.
	 *
	 * @since NEXT
	 * @param Number remaining_time The most recent remaining time estimate.
	 */
	app.updateTiming = function( remaining_time ) {
		app.times.push( remaining_time );

		// Limit sample size.
		if ( 100 < app.times.length ) {
			app.times.shift();
		}

		app.times.map( function ( e ) {
			return e += e;
		} );

		app.times = smooth( app.times, 0.85 );
	};

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

			if ( response.data.page_size ) {
				app.PAGE_SIZE = response.data.page_size;
			}

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
			var time_left_suffix = 'hours';
			var remaining_time   = ( ( ( total_objects - total_objects_processed ) / app.PAGE_SIZE ) * request_time );
			app.updateTiming( remaining_time );
			remaining_time = remaining_time / app.times.length;
			remaining_time = remaining_time / 60 / 60;

			// Shift to minutes.
			if ( 1 > remaining_time ) {
				remaining_time   = remaining_time * 60;
				time_left_suffix = 'minutes';
			}

			// Round to two decimal places, mostly.
			remaining_time   = Math.round( remaining_time * 100 ) / 100;
			progress_string += ' (' + [ 'Estimated time remaining:', remaining_time, time_left_suffix ].join(' ') + ' )';
		}

		$('.progress-stats').text( progress_string );
	};

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
			if ( ! app.running ) {
				app.cleanup( 'Sync canceled by user.' );
				return;
			}

			app.updateProgressBar(
				response.data.ps_objects_to_sync,
				response.data.total_objects_processed,
				response.data.total_objects,
				( new Date().getTime() - start_time ) / 1000 // Convert request time from milliseconds to seconds.
			);

			app.Log( response );

			// Bail if we're done.
			if ( response.data.total_objects_processed >= response.data.total_objects ) {
				// Start the next batch at page 1.
				if ( next_args && next_args.order_to_sync_all && next_args.order_to_sync_all.length ) {
					return app.syncData( 1, null, next_args );
				}

				app.cleanup( 'Sync completed!' );
				return;
			}

			app.syncData( response.data.next_page, response.data.ps_objects_to_sync, next_args );
		});
	}

	/**
	 * Logs messages from the remote server to the log window.
	 *
	 * @since NEXT
	 * @param Object response The response from the AJAX request.
	 */
	app.Log = function( response ) {
		if ( ! response.data ) {
			return;
		}

		var loglines = [];

		try {
			var logs = response.data.log;
			for ( var i = 0; i < logs.length; i++) {
				loglines.push( logs[i] );
			}

			loglines.push("\n---BATCH END ---\n");
			app.elCache.logs.val( app.elCache.logs.val() + loglines.join("\n") );
		} catch ( e ) {
			console.warn( "Could not log data, response: " + e );
			console.warn( response.data );
		}
	};

	/**
	 * Cleanup the view so it's back to a state similar to when we first
	 * visit the page.
	 *
	 * @since NEXT
	 * @param string message The message to display under the progress bar.
	 */
	app.cleanup = function( message ){
		app.elCache.syncButton.show();
		app.elCache.cancelButton.hide();
		app.elCache.bulkSettings.show();
		app.elCache.status.text( message );
		createLogFile();
		app.elCache.downloadLog.show();
	};

	/**
	 * Click handler to download the log file.
	 *
	 * @since NEXT
	 */
	app.downloadLog = function() {
		app.elCache.downloadLog.attr( 'href', app.logFileURL );
	};

	$( document ).ready( app.init );

	return app;

	// Private methods.

	/**
	 * Smooth a set of values.
	 *
	 * We use this to smooth out the timings in the array of request times to give a more accurate
	 * time estimation.
	 *
	 * Source: https://stackoverflow.com/q/32788836/1169389
	 */
	function smooth(values, alpha) {
		var weighted = average(values) * alpha;
		var smoothed = [];
		for (var i in values) {
			var curr = values[i];
			var prev = smoothed[i - 1] || values[values.length - 1];
			var next = curr || values[0];
			var improved = Number(average([weighted, prev, curr, next]).toFixed(2));
			smoothed.push(improved);
		}
		return smoothed;
	}

	/**
	 * Gets the averate of a set of data.
	 *
	 * Source: https://stackoverflow.com/q/32788836/1169389
	 */
	function average(data) {
		var sum = data.reduce(function(sum, value) {
			return sum + value;
		}, 0);
		var avg = sum / data.length;
		return avg;
	}

	/**
	 * Creates a downloadable file using the Javascript Blob object.
	 *
	 * Source: https://stackoverflow.com/a/21016088/1169389
	 */
	function createLogFile() {
		var text = app.elCache.logs.val();
		var data = new Blob([text], {type: 'text/plain'});

		if ( null !== app.logFileURL ) {
			window.URL.revokeObjectURL( app.logFileURL );
		}

		app.logFileURL = window.URL.createObjectURL(data);
	}
} )( window, document, jQuery );
